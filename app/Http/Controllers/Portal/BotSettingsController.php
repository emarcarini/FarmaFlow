<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\BotRule;
use App\Models\BotSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BotSettingsController extends Controller
{
    /**
     * Mapeamento de categorias amigáveis com rótulos e ícones.
     */
    public static function getCategories(): array
    {
        return [
            'ritmo' => [
                'label' => 'Ritmo & Digitação',
                'icon' => 'clock',
                'badge' => 'Tempo & Presença',
                'color' => 'indigo',
            ],
            'linguagem' => [
                'label' => 'Tom de Voz & Oralidade',
                'icon' => 'message-square',
                'badge' => 'Comunicação',
                'color' => 'cyan',
            ],
            'balcao' => [
                'label' => 'Postura de Vendas & Balcão',
                'icon' => 'store',
                'badge' => 'Comercial',
                'color' => 'emerald',
            ],
            'relacionamento' => [
                'label' => 'Memória & Relacionamento',
                'icon' => 'heart-handshake',
                'badge' => 'Fidelização',
                'color' => 'teal',
            ],
            'situacional' => [
                'label' => 'Inteligência Situacional',
                'icon' => 'sparkles',
                'badge' => 'Adaptação',
                'color' => 'purple',
            ],
            'farmaceutica' => [
                'label' => 'Psicologia B2B Farmacêutica',
                'icon' => 'pill',
                'badge' => 'Farmácia',
                'color' => 'amber',
            ],
            'estetica' => [
                'label' => 'Formatação & Estética WhatsApp',
                'icon' => 'palette',
                'badge' => 'Visual',
                'color' => 'blue',
            ],
            'conexao' => [
                'label' => 'Conexão & Cortesia',
                'icon' => 'smile',
                'badge' => 'Empatia',
                'color' => 'pink',
            ],
            'negociacao' => [
                'label' => 'Negociação & Conflito',
                'icon' => 'shield-check',
                'badge' => 'Estratégia',
                'color' => 'rose',
            ],
            'autonomia' => [
                'label' => 'Autonomia & Controles',
                'icon' => 'sliders',
                'badge' => 'Operacional',
                'color' => 'orange',
            ],
            'custom' => [
                'label' => 'Regras Personalizadas',
                'icon' => 'user-check',
                'badge' => 'Criadas por Você',
                'color' => 'emerald',
            ],
        ];
    }

    /**
     * Exibir a página de Configurações Operacionais do Robô (/configuracoes/bot).
     */
    public function indexSettings(): View
    {
        $settings = BotSetting::all()->keyBy('key');
        return view('portal.bot.settings', compact('settings'));
    }

    /**
     * Atualizar as configurações operacionais do robô.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'typing_delay_enabled' => ['nullable', 'boolean'],
            'typing_delay_min' => ['required', 'integer', 'min:1', 'max:60'],
            'typing_delay_max' => ['required', 'integer', 'min:1', 'max:120'],
            'split_long_messages' => ['nullable', 'boolean'],
            'auto_pause_on_human_reply' => ['nullable', 'boolean'],
            'pause_duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'large_order_alert_threshold' => ['required', 'numeric', 'min:0'],
            'max_autonomous_discount_pct' => ['required', 'numeric', 'min:0', 'max:30'],
            'prohibited_words' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['typing_delay_min'] > $validated['typing_delay_max']) {
            return back()->withErrors(['typing_delay_min' => 'O tempo mínimo de digitação não pode ser maior que o tempo máximo.'])->withInput();
        }

        BotSetting::set('typing_delay_enabled', $request->boolean('typing_delay_enabled') ? '1' : '0');
        BotSetting::set('typing_delay_min', $validated['typing_delay_min']);
        BotSetting::set('typing_delay_max', $validated['typing_delay_max']);
        BotSetting::set('split_long_messages', $request->boolean('split_long_messages') ? '1' : '0');
        BotSetting::set('auto_pause_on_human_reply', $request->boolean('auto_pause_on_human_reply') ? '1' : '0');
        BotSetting::set('pause_duration_minutes', $validated['pause_duration_minutes']);
        BotSetting::set('large_order_alert_threshold', $validated['large_order_alert_threshold']);
        BotSetting::set('max_autonomous_discount_pct', $validated['max_autonomous_discount_pct']);
        BotSetting::set('prohibited_words', trim($validated['prohibited_words'] ?? ''));

        return redirect()->route('portal.bot.settings')
            ->with('success', 'Configurações operacionais do robô salvas com sucesso! As alterações já estão ativas.');
    }

    /**
     * Exibir a página de Regras de Atendimento (/regras).
     */
    public function indexRules(Request $request): View
    {
        $categories = self::getCategories();
        $selectedCategory = $request->query('category');
        $search = trim($request->query('q', ''));

        $query = BotRule::query()->orderBy('priority', 'asc')->orderBy('id', 'asc');

        if (!empty($selectedCategory) && isset($categories[$selectedCategory])) {
            $query->where('category', $selectedCategory);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $rules = $query->get();
        $totalActive = BotRule::where('is_active', true)->count();
        $totalRules = BotRule::count();

        return view('portal.bot.rules', compact('rules', 'categories', 'selectedCategory', 'search', 'totalActive', 'totalRules'));
    }

    /**
     * Criar nova regra personalizada pelo representante ou administrador.
     */
    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'O título da regra é obrigatório.',
            'content.required' => 'A instrução para o robô é obrigatória.',
        ]);

        $user = Auth::user();

        $rule = BotRule::create([
            'title' => trim($validated['title']),
            'content' => trim($validated['content']),
            'category' => $validated['category'] ?: 'custom',
            'is_preset' => false,
            'representative_id' => $user->representative?->id,
            'is_active' => $request->boolean('is_active', true),
            'priority' => 10,
        ]);

        return redirect()->route('portal.bot.rules')
            ->with('success', "Nova regra personalizada '{$rule->title}' criada e ativada com sucesso!");
    }

    /**
     * Alternar estado Liga/Desliga (Toggle) de uma regra via AJAX ou POST.
     */
    public function toggleRule(BotRule $rule, Request $request): JsonResponse|RedirectResponse
    {
        $rule->is_active = !$rule->is_active;
        $rule->save();

        $statusText = $rule->is_active ? 'ativada' : 'desativada';
        $message = "Regra '{$rule->title}' foi {$statusText} com sucesso!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $rule->is_active,
                'message' => $message,
                'total_active' => BotRule::where('is_active', true)->count(),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Atualizar uma regra existente.
     */
    public function updateRule(Request $request, BotRule $rule): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule->update([
            'title' => trim($validated['title']),
            'content' => trim($validated['content']),
            'category' => $validated['category'] ?: $rule->category,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('portal.bot.rules')
            ->with('success', "Regra '{$rule->title}' atualizada com sucesso!");
    }

    /**
     * Excluir uma regra personalizada.
     */
    public function destroyRule(BotRule $rule): RedirectResponse
    {
        if ($rule->is_preset) {
            return back()->with('error', 'Regras do catálogo padrão não podem ser excluídas, apenas desativadas no botão Liga/Desliga.');
        }

        $title = $rule->title;
        $rule->delete();

        return redirect()->route('portal.bot.rules')
            ->with('success', "Regra personalizada '{$title}' excluída com sucesso!");
    }
}
