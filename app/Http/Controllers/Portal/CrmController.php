<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomerTag;
use App\Services\CRM\CustomerScoreService;
use App\Services\CRM\TimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CrmController extends Controller
{
    public function __construct(
        protected TimelineService $timelineService,
        protected CustomerScoreService $scoreService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $isRep = $user->isRepresentative();
        $repId = $user->representative?->id;

        $search = $request->input('search');
        $segment = $request->input('segment');
        $status = $request->input('status');
        $tagId = $request->input('tag');

        $query = Company::query();

        // Isolamento de dados por representante
        if ($isRep && $repId) {
            $query->where('representative_id', $repId);
        }

        $companies = $query
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('trade_name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhereHas('contacts', fn($qc) => $qc->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($segment, fn($q, $segment) => $q->where('segment', $segment))
            ->when($status, fn($q, $status) => $q->where('status', $status))
            ->when($tagId, fn($q, $tagId) => $q->whereHas('tags', fn($qt) => $qt->where('customer_tags.id', $tagId)))
            ->with(['contacts', 'tags', 'score', 'representative'])
            ->latest()
            ->paginate(15);

        $tags = CustomerTag::all();
        $segmentsQuery = Company::select('segment')->distinct()->whereNotNull('segment');
        if ($isRep && $repId) {
            $segmentsQuery->where('representative_id', $repId);
        }
        $segments = $segmentsQuery->pluck('segment');

        return view('portal.crm.index', compact('companies', 'tags', 'segments', 'search', 'segment', 'status', 'tagId'));
    }

    public function show(Company $company): View
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $company->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a este cliente da carteira.');
        }

        $company->load(['contacts.consentPreferences', 'tags', 'score', 'representative', 'orders.items.product', 'quotes']);

        // Recalcular score se não existir
        if (!$company->score) {
            $company->setRelation('score', $this->scoreService->calculateScore($company));
        }

        $timeline = $this->timelineService->getCustomerTimeline($company);

        return view('portal.crm.show', compact('company', 'timeline'));
    }

    /**
     * Adicionar telefone/contato autorizado para a empresa (CNPJ).
     */
    public function storeContact(Request $request, Company $company): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $company->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a este cliente.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:25'],
            'role_position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'O nome do contato é obrigatório.',
            'phone.required' => 'O telefone autorizado é obrigatório.',
            'phone.min' => 'Informe um telefone válido com DDD (mínimo 10 dígitos).',
        ]);

        $cleanPhone = preg_replace('/\D+/', '', $request->input('phone'));
        if (strlen($cleanPhone) < 10) {
            return back()->withErrors(['phone' => 'O telefone deve conter DDD e número válido.'])->withInput();
        }

        // Se for marcado como principal, remove is_primary dos outros
        if ($request->boolean('is_primary')) {
            $company->contacts()->update(['is_primary' => false]);
        }

        $contact = $company->contacts()->create([
            'representative_id' => $company->representative_id,
            'name' => trim($request->input('name')),
            'phone' => $cleanPhone,
            'role_position' => trim($request->input('role_position') ?: 'Comprador'),
            'email' => trim($request->input('email', '')),
            'is_primary' => $request->boolean('is_primary') || $company->contacts()->count() === 0,
            'is_authorized' => true,
            'notes' => $request->input('notes'),
        ]);

        // Cria preferência de consentimento ativa para WhatsApp
        \App\Models\ConsentPreference::firstOrCreate(
            ['contact_id' => $contact->id, 'channel' => 'whatsapp'],
            ['company_id' => $company->id, 'is_opted_out' => false]
        );

        return redirect()->route('portal.crm.show', $company)
            ->with('success', "Telefone autorizado ({$contact->phone}) adicionado com sucesso para o CNPJ {$company->document}!");
    }

    /**
     * Excluir telefone autorizado da empresa (CNPJ).
     * Regra estrita: O cliente deve ter obrigatoriamente pelo menos 1 telefone.
     */
    public function destroyContact(Company $company, Contact $contact): RedirectResponse
    {
        $user = Auth::user();
        if ($user->isRepresentative() && $company->representative_id !== $user->representative?->id) {
            abort(403, 'Acesso não autorizado a este cliente.');
        }

        if ($contact->company_id !== $company->id) {
            abort(404, 'Contato não pertence a esta empresa.');
        }

        // REGRA OBRIGATÓRIA: O cliente deve possuir pelo menos 1 telefone autorizado para o CNPJ
        $totalContacts = $company->contacts()->count();
        if ($totalContacts <= 1) {
            return redirect()->route('portal.crm.show', $company)
                ->with('error', 'Não é possível excluir: o cliente deve possuir obrigatoriamente pelo menos 1 telefone autorizado para tratar sobre o CNPJ.');
        }

        $deletedPhone = $contact->phone;
        $wasPrimary = $contact->is_primary;

        $contact->delete();

        // Se o excluído era o principal, define o primeiro restante como principal
        if ($wasPrimary) {
            $remainingFirst = $company->contacts()->first();
            $remainingFirst?->update(['is_primary' => true]);
        }

        return redirect()->route('portal.crm.show', $company)
            ->with('success', "Telefone autorizado ({$deletedPhone}) excluído com sucesso!");
    }
}
