<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use App\Models\Representative;
use App\Models\User;
use App\Services\WhatsApp\EvolutionApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RepresentativeAdminController extends Controller
{
    public function __construct(
        protected EvolutionApiService $evolutionApi
    ) {}

    /**
     * Listagem completa de representantes para o Administrador.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Representative::query()
            ->with(['user'])
            ->withCount(['companies', 'quotes', 'orders', 'tasks'])
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp_instance', 'like', "%{$search}%");
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('is_active', $status === 'active' || $status === '1');
            });

        $representatives = $query->latest()->paginate(12);

        // Métricas globais da equipe
        $totalReps = Representative::count();
        $activeReps = Representative::where('is_active', true)->count();
        $blockedReps = Representative::where('is_active', false)->count();
        $totalCompanies = Company::count();
        $totalRevenue = (float) Order::whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total_amount');

        return view('portal.admin.representatives.index', compact(
            'representatives',
            'totalReps',
            'activeReps',
            'blockedReps',
            'totalCompanies',
            'totalRevenue',
            'search',
            'status'
        ));
    }

    /**
     * Cadastrar um novo representante comercial com login e instância de WhatsApp dedicada.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:30'],
            'code' => ['nullable', 'string', 'max:50', 'unique:representatives,code'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'whatsapp_instance' => ['nullable', 'string', 'max:100', 'unique:representatives,whatsapp_instance'],
        ]);

        DB::transaction(function () use ($validated) {
            // 1. Cria usuário de login
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'representative',
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            // 2. Define código do representante se não informado
            $code = $validated['code'] ?? 'REP-' . str_pad(Representative::max('id') + 1, 3, '0', STR_PAD_LEFT);
            $instanceName = $validated['whatsapp_instance'] ?? 'rep_' . Str::slug($validated['name'], '_');

            // 3. Cria perfil comercial do representante
            Representative::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'code' => $code,
                'commission_rate' => $validated['commission_rate'] ?? 5.00,
                'max_discount_pct' => $validated['max_discount_pct'] ?? 15.00,
                'whatsapp_instance' => $instanceName,
                'whatsapp_status' => 'disconnected',
                'is_active' => true,
                'settings' => [
                    'bot_name' => 'Assistente Comercial de ' . explode(' ', $validated['name'])[0],
                    'allow_ai_quotes' => true,
                    'notify_on_handover' => true,
                ],
            ]);
        });

        return redirect()->route('admin.representatives.index')
            ->with('success', 'Representante cadastrado com sucesso! Instância de WhatsApp configurada.');
    }

    /**
     * Atualizar dados e limites comerciais de um representante.
     */
    public function update(Request $request, Representative $representative): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $representative->user_id],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:30'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'whatsapp_instance' => ['nullable', 'string', 'max:100', 'unique:representatives,whatsapp_instance,' . $representative->id],
        ]);

        DB::transaction(function () use ($validated, $representative) {
            // Atualiza User
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            if ($representative->user) {
                $representative->user->update($userData);
            }

            // Atualiza Representative
            $representative->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'commission_rate' => $validated['commission_rate'] ?? $representative->commission_rate,
                'max_discount_pct' => $validated['max_discount_pct'] ?? $representative->max_discount_pct,
                'whatsapp_instance' => $validated['whatsapp_instance'] ?? $representative->whatsapp_instance,
            ]);
        });

        return redirect()->route('admin.representatives.index')
            ->with('success', 'Representante atualizado com sucesso!');
    }

    /**
     * Alternar status de Ativo / Bloqueado do representante.
     */
    public function toggleStatus(Representative $representative): RedirectResponse
    {
        $newStatus = !$representative->is_active;

        DB::transaction(function () use ($representative, $newStatus) {
            $representative->update(['is_active' => $newStatus]);

            if ($representative->user) {
                $representative->user->update(['is_active' => $newStatus]);
            }
        });

        $actionText = $newStatus ? 'desbloqueado e ativado' : 'bloqueado e desativado';

        return redirect()->route('admin.representatives.index')
            ->with('success', "Representante {$representative->name} foi {$actionText} com sucesso.");
    }

    /**
     * Excluir representante do sistema.
     */
    public function destroy(Representative $representative): RedirectResponse
    {
        DB::transaction(function () use ($representative) {
            $user = $representative->user;

            // Desvincula clientes para não perder histórico
            Company::where('representative_id', $representative->id)->update(['representative_id' => null]);

            $representative->delete();

            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('admin.representatives.index')
            ->with('success', 'Representante excluído com sucesso da base de dados.');
    }
}
