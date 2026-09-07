<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsApp\EvolutionApiService;
use App\Services\WhatsApp\HumanHandoverService;
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

        $selectedId = $request->input('conversation_id');

        $query = Conversation::query();

        // Isolamento de dados por representante
        if ($isRep && $repId) {
            $query->where('representative_id', $repId);
        }

        $conversations = $query->with(['contact.company', 'messages' => fn($q) => $q->latest()->take(1)])
            ->orderBy('last_message_at', 'desc')
            ->get();

        $activeQuery = Conversation::query();
        if ($isRep && $repId) {
            $activeQuery->where('representative_id', $repId);
        }

        $activeConversation = $selectedId
            ? $activeQuery->with(['contact.company', 'messages'])->find($selectedId)
            : $conversations->first();

        if ($activeConversation && !$selectedId && $conversations->isNotEmpty()) {
            $activeConversation->load('messages');
        }

        return view('portal.inbox.index', compact('conversations', 'activeConversation'));
    }

    public function sendMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $conversation->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a esta conversa.');
        }

        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $content = $request->input('content');

        // Salvar mensagem de saída como Representante
        $conversation->messages()->create([
            'direction' => 'outbound',
            'sender_type' => 'representative',
            'content' => $content,
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Configura a instância correta do representante na Evolution API
        $this->whatsappService->forRepresentative($conversation->representative);

        // Enviar via Evolution API
        if ($conversation->contact?->phone) {
            $this->whatsappService->sendTextMessage($conversation->contact->phone, $content);
        }

        return redirect()->route('portal.inbox', ['conversation_id' => $conversation->id])
            ->with('success', 'Mensagem enviada com sucesso!');
    }

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
