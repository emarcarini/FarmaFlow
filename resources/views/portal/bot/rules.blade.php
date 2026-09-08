@extends('layouts.app', ['title' => 'Regras de Atendimento do Robô IA'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header Hero -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-700 border border-emerald-400/30 flex items-center justify-center text-white shadow-lg shadow-emerald-600/25">
                    <i data-lucide="sparkles" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="font-display font-extrabold text-2xl md:text-3xl text-slate-900 dark:text-white tracking-tight">
                            Regras de Atendimento & Humanização
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                            {{ $totalActive }}/{{ $totalRules }} Ativas
                        </span>
                    </div>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Ligue e desligue regras comportamentais no botão ou crie suas próprias instruções comerciais em português puro.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('portal.bot.settings') }}" class="px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white font-semibold text-xs transition-all flex items-center gap-2 shadow-sm">
                <i data-lucide="sliders" class="w-4 h-4 text-indigo-500"></i>
                <span>Configurar Delay & Sliders</span>
            </a>

            <button type="button" onclick="openNewRuleModal()" class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/25 transition-all flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Nova Regra Personalizada</span>
            </button>
        </div>
    </div>

    <!-- Session Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3 shadow-lg shadow-emerald-950/20">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 flex-shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm flex items-center gap-3 shadow-lg shadow-rose-950/20">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-400 flex-shrink-0"></i>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Search & Category Filters Bar -->
    <div class="space-y-4">
        <div class="flex flex-col md:flex-row items-center gap-4 justify-between">
            <!-- Search Input -->
            <form method="GET" action="{{ route('portal.bot.rules') }}" class="w-full md:w-96 relative">
                @if($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                <input type="text" name="q" value="{{ $search }}" placeholder="Pesquisar por regra ou palavra-chave..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-all shadow-sm">
            </form>

            <div class="text-xs text-slate-400 flex items-center gap-2">
                <span>Exibindo <strong>{{ $rules->count() }}</strong> regras</span>
                @if($selectedCategory || $search)
                    <a href="{{ route('portal.bot.rules') }}" class="text-indigo-400 hover:underline flex items-center gap-1">
                        <i data-lucide="x" class="w-3 h-3"></i>
                        <span>Limpar filtros</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Categories Badges -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <a href="{{ route('portal.bot.rules', array_filter(['q' => $search])) }}" 
                class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ empty($selectedCategory) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 hover:text-white hover:bg-slate-700' }}">
                <i data-lucide="grid" class="w-3.5 h-3.5"></i>
                <span>Todas as Regras</span>
                <span class="text-[10px] opacity-75 font-mono">({{ $totalRules }})</span>
            </a>

            @foreach($categories as $catKey => $cat)
                <a href="{{ route('portal.bot.rules', array_filter(['category' => $catKey, 'q' => $search])) }}" 
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ $selectedCategory === $catKey ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 hover:text-white hover:bg-slate-700' }}">
                    <i data-lucide="{{ $cat['icon'] }}" class="w-3.5 h-3.5"></i>
                    <span>{{ $cat['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Rules Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($rules as $rule)
            @php
                $cat = $categories[$rule->category] ?? [
                    'label' => 'Geral',
                    'icon' => 'sparkles',
                    'badge' => 'Regra',
                    'color' => 'indigo',
                ];
            @endphp
            <div id="rule-card-{{ $rule->id }}" class="flex flex-col justify-between bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 shadow-lg hover:border-slate-700 transition-all space-y-4">
                <div class="space-y-3">
                    <!-- Top Category & Toggle -->
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            <i data-lucide="{{ $cat['icon'] }}" class="w-3 h-3 text-indigo-400"></i>
                            <span>{{ $cat['label'] }}</span>
                        </span>

                        <!-- Switch Toggle Liga/Desliga -->
                        <div class="flex items-center gap-2">
                            <span id="rule-status-label-{{ $rule->id }}" class="text-[11px] font-bold {{ $rule->is_active ? 'text-emerald-400' : 'text-slate-500' }}">
                                {{ $rule->is_active ? 'Ativada' : 'Desativada' }}
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" onchange="toggleRule({{ $rule->id }})" id="toggle-rule-{{ $rule->id }}" 
                                    class="sr-only peer" {{ $rule->is_active ? 'checked' : '' }}>
                                <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Title -->
                    <h3 class="font-display font-bold text-sm text-slate-900 dark:text-white leading-snug">
                        {{ $rule->title }}
                    </h3>

                    <!-- Content / Prompt Instruction -->
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-950/60 p-3.5 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                        "{{ $rule->content }}"
                    </p>
                </div>

                <!-- Footer info & delete if custom -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800/80 text-[11px] text-slate-400">
                    <span class="font-mono text-[10px]">
                        @if($rule->is_preset)
                            <span class="text-indigo-400 font-bold">Catálogo Padrão</span>
                        @else
                            <span class="text-emerald-400 font-bold">Criada pelo Representante</span>
                        @endif
                    </span>

                    @if(!$rule->is_preset)
                        <form method="POST" action="{{ route('portal.bot.rules.destroy', $rule) }}" onsubmit="return confirm('Deseja realmente excluir esta regra personalizada?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Excluir regra" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-all">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white dark:bg-slate-900/60 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                    <i data-lucide="inbox" class="w-6 h-6"></i>
                </div>
                <h4 class="font-bold text-slate-200 text-sm">Nenhuma regra encontrada com este filtro</h4>
                <p class="text-xs text-slate-400">Tente buscar por outro termo ou limpe os filtros de categoria.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal: Criar Nova Regra Personalizada -->
<div id="modal-new-rule" onclick="if(event.target === this) closeNewRuleModal()" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-2xl space-y-6">
        <button type="button" onclick="closeNewRuleModal()" class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
                <i data-lucide="plus-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-display font-bold text-lg text-white">Criar Nova Regra Personalizada</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Escreva a instrução em português simples. O robô IA incorporará essa regra imediatamente no atendimento de WhatsApp.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('portal.bot.rules.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Título da Regra <span class="text-rose-400">*</span></label>
                <input type="text" name="title" required placeholder="Ex: Avisar sobre promoção de Soro na compra de Amoxicilina" value="{{ old('title') }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 outline-none placeholder-slate-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Categoria</label>
                <select name="category" class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 outline-none">
                    <option value="custom">Regras Personalizadas do Representante</option>
                    @foreach($categories as $catKey => $cat)
                        <option value="{{ $catKey }}">{{ $cat['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Instrução Exata para a IA seguir no WhatsApp <span class="text-rose-400">*</span></label>
                <textarea name="content" rows="4" required placeholder="Ex: Sempre que o cliente cotar Amoxicilina, pergunte se ele quer incluir caixas de Soro Fisiológico ou Ibuprofeno para aproveitar o mesmo frete da distribuidora."
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:border-indigo-500 outline-none placeholder-slate-500 leading-relaxed resize-none">{{ old('content') }}</textarea>
                <p class="text-[10px] text-slate-400 mt-1">Dica: seja específico quanto aos produtos, condições ou tom que o assistente deve adotar.</p>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                <input type="checkbox" name="is_active" id="modal_is_active" value="1" checked
                    class="w-4 h-4 rounded text-emerald-600 bg-slate-900 border-slate-700 focus:ring-emerald-500">
                <label for="modal_is_active" class="text-xs text-slate-300 cursor-pointer select-none">
                    <strong>Ativar regra imediatamente</strong> no WhatsApp
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" onclick="closeNewRuleModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/25 transition-all flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Salvar e Ativar Regra</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden transition-all transform duration-300">
    <div class="px-4 py-3 rounded-2xl bg-slate-900 border border-slate-700 text-white text-xs font-semibold shadow-2xl flex items-center gap-3">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
        <span id="toast-message">Regra atualizada com sucesso!</span>
    </div>
</div>

<script>
    function openNewRuleModal() {
        const modal = document.getElementById('modal-new-rule');
        if (modal) modal.classList.remove('hidden');
    }

    function closeNewRuleModal() {
        const modal = document.getElementById('modal-new-rule');
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeNewRuleModal();
    });

    // AJAX Toggle Liga/Desliga Instantâneo
    function toggleRule(ruleId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const statusLabel = document.getElementById('rule-status-label-' + ruleId);

        fetch('/regras/' + ruleId + '/toggle', {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (statusLabel) {
                    statusLabel.innerText = data.is_active ? 'Ativada' : 'Desativada';
                    statusLabel.className = 'text-[11px] font-bold ' + (data.is_active ? 'text-emerald-400' : 'text-slate-500');
                }
                showToast(data.message);
            }
        })
        .catch(err => {
            console.error('Erro ao alternar regra:', err);
            showToast('Erro ao alternar regra. Tente novamente.');
        });
    }

    function showToast(msg) {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toast-message');
        if (toast && toastMsg) {
            toastMsg.innerText = msg;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
    }
</script>
@endsection
