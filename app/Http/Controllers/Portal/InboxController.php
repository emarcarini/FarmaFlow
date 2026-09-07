<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Representative;
use App\Services\WhatsApp\EvolutionApiService;
use App\Services\WhatsApp\HumanHandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function __construct(
        protected EvolutionApiService $whatsappService,
        protected HumanHandoverService $handoverService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $isRep = $user->isRepresentative();
        $repId = $user->representative?->id;

        $filter = $request->input('filter', 'active'); // 'active', 'archived', 'unread'
        $search = trim($request->input('q', ''));
        $selectedId = $request->input('conversation_id');
        $contactId = $request->input('contact_id');

        // Se o usuário selecionou um contato específico (ex: vindo do modal de contatos)
        if ($contactId && !$selectedId) {
            $contact = Contact::find($contactId);
            if ($contact) {
                $targetRep = Representative::where('whatsapp_instance', 'farmaflow')->first() ?? Representative::first();
                $conversation = Conversation::firstOrCreate(
                    [
                        'contact_id' => $contact->id,
                        'channel' => 'whatsapp',
                    ],
                    [
                        'representative_id' => $contact->representative_id ?? $targetRep?->id,
                        'status' => 'ai_handling',
                        'is_archived' => false,
                        'last_message_at' => now(),
                    ]
                );

                if ($conversation->is_archived) {
                    $conversation->unarchive();
                }

                $selectedId = $conversation->id;
            }
        }

        // Base Query de Conversas
        $query = Conversation::query();

        if ($isRep && $repId) {
            $query->where('representative_id', $repId);
        }

        // Filtro de Arquivamento
        if ($filter === 'archived') {
            $query->where('is_archived', true);
        } else {
            $query->where('is_archived', false);
        }

        // Lista APENAS conversas reais com mensagens (a menos que seja a conversa recém-aberta pelo modal)
        if ($selectedId) {
            $query->where(function ($q) use ($selectedId) {
                $q->whereHas('messages')
                  ->orWhere('id', $selectedId);
            });
        } else {
            $query->whereHas('messages');
        }

        // Filtro de Busca (por nome de contato, telefone ou mensagem)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('messages', function ($mq) use ($search) {
                    $mq->where('content', 'like', "%{$search}%");
                });
            });
        }

        // Eager loading e ordenação temporal
        $conversations = $query->with([
            'contact.company',
            'messages' => fn($q) => $q->latest()->take(1),
        ])->orderBy('last_message_at', 'desc')->get();

        // Contadores para abas/chips
        $archivedCountQuery = Conversation::where('is_archived', true)->whereHas('messages');
        $activeCountQuery = Conversation::where('is_archived', false)->whereHas('messages');
        if ($isRep && $repId) {
            $archivedCountQuery->where('representative_id', $repId);
            $activeCountQuery->where('representative_id', $repId);
        }
        $archivedCount = $archivedCountQuery->count();
        $activeCount = $activeCountQuery->count();

        // Conversa Ativa
        $activeQuery = Conversation::query();
        if ($isRep && $repId) {
            $activeQuery->where('representative_id', $repId);
        }

        $activeConversation = $selectedId
            ? $activeQuery->with(['contact.company', 'messages'])->find($selectedId)
            : $conversations->first();

        if ($activeConversation && !$selectedId && $conversations->isNotEmpty()) {
            $activeConversation->load(['contact.company', 'messages']);
        }

        // Contatos iniciais para o modal de contatos
        $initialContacts = Contact::with('company')
            ->orderBy('name', 'asc')
            ->take(50)
            ->get();

        $totalContactsCount = Contact::count();

        return view('portal.inbox.index', compact(
            'conversations',
            'activeConversation',
            'filter',
            'search',
            'archivedCount',
            'activeCount',
            'initialContacts',
            'totalContactsCount'
        ));
    }

    /**
     * Iniciar conversa a partir do Modal de Contatos.
     */
    public function startConversation(Request $request): RedirectResponse
    {
        $request->validate([
            'contact_id' => ['required', 'exists:contacts,id'],
        ]);

        $contact = Contact::findOrFail($request->input('contact_id'));
        $targetRep = Representative::where('whatsapp_instance', 'farmaflow')->first() ?? Representative::first();

        $conversation = Conversation::firstOrCreate(
            [
                'contact_id' => $contact->id,
                'channel' => 'whatsapp',
            ],
            [
                'representative_id' => $contact->representative_id ?? $targetRep?->id,
                'status' => 'ai_handling',
                'is_archived' => false,
                'last_message_at' => now(),
            ]
        );

        if ($conversation->is_archived) {
            $conversation->unarchive();
        }

        return redirect()->route('portal.inbox', ['conversation_id' => $conversation->id]);
    }

    /**
     * Buscar contatos via AJAX para o Modal de Contatos.
     */
    public function contacts(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        $query = Contact::with('company')->orderBy('name', 'asc');

        if (!empty($q)) {
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%")
                   ->orWhereHas('company', function ($cq) use ($q) {
                       $cq->where('name', 'like', "%{$q}%")
                          ->orWhere('trade_name', 'like', "%{$q}%");
                   });
            });
        }

        $contacts = $query->take(60)->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'company' => $c->company?->trade_name ?? $c->company?->name,
                'avatar' => strtoupper(substr($c->name ?? 'C', 0, 2)),
            ];
        });

        return response()->json($contacts);
    }

    /**
     * Enviar mensagem no WhatsApp.
     */
    public function sendMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $conversation->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a esta conversa.');
        }

        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $content = trim($request->input('content'));

        // Salvar mensagem de saída como Representante
        $conversation->messages()->create([
            'direction' => 'outbound',
            'sender_type' => 'representative',
            'content' => $content,
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        // Se a conversa estava arquivada, desarquiva automaticamente
        if ($conversation->is_archived) {
            $conversation->unarchive();
        }

        $conversation->update(['last_message_at' => now()]);

        // Configura a instância farmaflow
        $this->whatsappService->forRepresentative($conversation->representative);

        // Enviar via Evolution API
        if ($conversation->contact?->phone) {
            $this->whatsappService->sendTextMessage($conversation->contact->phone, $content);
        }

        return redirect()->route('portal.inbox', ['conversation_id' => $conversation->id])
            ->with('success', 'Mensagem enviada com sucesso!');
    }

    /**
     * Arquivar conversa.
     */
    public function archive(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $conversation->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a esta conversa.');
        }

        $conversation->archive();

        return redirect()->route('portal.inbox')
            ->with('success', 'Conversa arquivada com sucesso!');
    }

    /**
     * Desarquivar conversa.
     */
    public function unarchive(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $conversation->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a esta conversa.');
        }

        $conversation->unarchive();

        return redirect()->route('portal.inbox', ['conversation_id' => $conversation->id])
            ->with('success', 'Conversa desarquivada com sucesso!');
    }

    /**
     * Alternar pausa ou retomada do robô IA.
     */
    public function toggleHandover(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $conversation->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a esta conversa.');
        }

        if ($conversation->isAiHandling()) {
            $this->handoverService->triggerHandover($conversation, 'Assumido manualmente pelo representante no painel.');
            $msg = 'Atendimento assumido! A IA foi pausada para este contato.';
        } else {
            $this->handoverService->resumeAi($conversation);
            $msg = 'Atendimento automático da IA reativado com sucesso!';
        }

        return redirect()->route('portal.inbox', ['conversation_id' => $conversation->id])
            ->with('success', $msg);
    }
}
