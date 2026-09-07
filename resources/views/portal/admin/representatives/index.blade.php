@extends('layouts.app', ['title' => 'Gestão de Representantes — Admin'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header & Action Button -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                <span>Painel de Controle Administrativo</span>
            </div>
            <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white tracking-tight">Gestão da Equipe de Representantes</h2>
            <p class="text-sm text-slate-400 mt-1">Cadastre, edite limites, bloqueie e configure instâncias dedicadas de WhatsApp para cada vendedor.</p>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="openCreateRepModal()" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white text-sm font-semibold transition-all shadow-lg shadow-indigo-600/25 flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Novo Representante</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">Total de Vendedores</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="font-display font-extrabold text-2xl text-white">{{ $totalReps }}</span>
                <span class="text-xs text-slate-500">cadastrados</span>
            </div>
        </div>

        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">Representantes Ativos</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                    <i data-lucide="user-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="font-display font-extrabold text-2xl text-emerald-400">{{ $activeReps }}</span>
                <span class="text-xs text-slate-500">operando agora</span>
            </div>
        </div>

        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">Bloqueados / Inativos</span>
                <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center">
                    <i data-lucide="user-x" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="font-display font-extrabold text-2xl {{ $blockedReps > 0 ? 'text-rose-400' : 'text-slate-400' }}">{{ $blockedReps }}</span>
                <span class="text-xs text-slate-500">acesso suspenso</span>
            </div>
        </div>

        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400">Faturamento Global</span>
                <div class="w-8 h-8 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-1">
                <span class="text-xs font-bold text-slate-400">R$</span>
                <span class="font-display font-extrabold text-xl text-white">{{ number_format($totalRevenue, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="p-5 rounded-3xl bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 shadow-xl">
        <form method="GET" action="{{ route('admin.representatives.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="relative md:col-span-6">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nome, email, código, telefone ou instância WhatsApp..."
                    class="w-full pl-11 pr-4 py-3 bg-slate-950/70 border border-slate-800 rounded-2xl text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all">
            </div>

            <div class="md:col-span-3">
                <div class="relative">
                    <i data-lucide="filter" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <select name="status" class="w-full pl-11 pr-8 py-3 bg-slate-950/70 border border-slate-800 rounded-2xl text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="">Todos os Status</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Apenas Ativos</option>
                        <option value="0" {{ $status === '0' ? 'selected' : '' }}>Apenas Bloqueados</option>
                    </select>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="flex-1 py-3 px-5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white rounded-2xl font-semibold text-sm transition-all shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Filtrar</span>
                </button>
                @if($search || $status !== null)
                    <a href="{{ route('admin.representatives.index') }}" class="py-3 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-2xl font-semibold text-sm transition-all flex items-center justify-center" title="Limpar Filtros">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Representatives Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($representatives as $rep)
            <div class="group bg-gradient-to-b from-slate-900/90 to-slate-950/90 backdrop-blur-xl border {{ $rep->is_active ? 'border-slate-800/80 hover:border-indigo-500/40' : 'border-rose-900/40 opacity-75' }} rounded-3xl p-6 shadow-xl transition-all flex flex-col justify-between">
                <div>
                    <!-- Top Badges -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold {{ $rep->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $rep->is_active ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                            {{ $rep->is_active ? 'Ativo & Liberado' : 'Acesso Bloqueado' }}
                        </span>

                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-xl bg-slate-800 text-indigo-300 border border-slate-700 font-mono">
                            {{ $rep->code }}
                        </span>
                    </div>

                    <!-- Rep Identity -->
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-950 to-slate-800 border border-indigo-500/30 flex items-center justify-center font-display font-extrabold text-base text-indigo-300 flex-shrink-0">
                            {{ strtoupper(substr($rep->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-display font-bold text-base text-white truncate">{{ $rep->name }}</h3>
                            <p class="text-xs text-slate-400 truncate mt-0.5">{{ $rep->email }}</p>
                            <p class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $rep->phone ?? 'Sem telefone' }}</p>
                        </div>
                    </div>

                    <!-- Dedicated WhatsApp Instance Card -->
                    <div class="mt-5 p-4 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-300 flex items-center gap-1.5">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-400"></i>
                                <span>Instância WhatsApp:</span>
                            </span>
                            <span class="font-mono text-xs text-indigo-300 bg-indigo-500/10 px-2 py-0.5 rounded-lg border border-indigo-500/20">
                                {{ $rep->getEffectiveWhatsAppInstance() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/60">
                            <span>Status:</span>
                            <span class="font-bold {{ $rep->whatsapp_status === 'open' ? 'text-emerald-400' : 'text-slate-400' }}">
                                {{ $rep->whatsapp_status === 'open' ? '● Conectado' : '● Desconectado' }}
                            </span>
                        </div>
                    </div>

                    <!-- Commercial Scope & Metrics -->
                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-500 block uppercase font-bold">Clientes</span>
                            <span class="font-display font-bold text-base text-white">{{ $rep->companies_count }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-500 block uppercase font-bold">Pedidos</span>
                            <span class="font-display font-bold text-base text-white">{{ $rep->orders_count }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between px-1 text-[11px] text-slate-400">
                        <span>Comissão: <strong class="text-slate-200">{{ $rep->commission_rate }}%</strong></span>
                        <span>Alçada Máx: <strong class="text-slate-200">{{ $rep->max_discount_pct }}%</strong></span>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between gap-2">
                    <button onclick='openEditRepModal(@json($rep))' 
                        class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-all flex items-center gap-1.5" title="Editar Representante">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Editar</span>
                    </button>

                    <div class="flex items-center gap-1.5">
                        <form method="POST" action="{{ route('admin.representatives.toggle', $rep) }}">
                            @csrf
                            <button type="submit" 
                                class="px-3 py-2 rounded-xl {{ $rep->is_active ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }} text-xs font-semibold transition-all"
                                title="{{ $rep->is_active ? 'Bloquear Acesso' : 'Desbloquear Acesso' }}">
                                <i data-lucide="{{ $rep->is_active ? 'lock' : 'unlock' }}" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.representatives.destroy', $rep) }}" onsubmit="return confirm('Tem certeza que deseja excluir este representante? Os clientes serão desvinculados.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition-all" title="Excluir Representante">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center rounded-3xl bg-slate-900/40 border border-slate-800/80">
                <div class="w-14 h-14 rounded-2xl bg-slate-800/80 flex items-center justify-center mx-auto text-slate-500 mb-3">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <h3 class="font-display font-bold text-base text-white">Nenhum representante encontrado</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Cadastre novos vendedores para expandir sua operação comercial.</p>
                <button onclick="openCreateRepModal()" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-all">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Cadastrar Primeiro Representante</span>
                </button>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-4">{{ $representatives->links() }}</div>
</div>

<!-- Modal: Criar Novo Representante -->
<div id="modal-create-rep" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 max-w-lg w-full shadow-2xl space-y-6 relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeCreateRepModal()" class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                <i data-lucide="user-plus" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-display font-bold text-lg text-white">Cadastrar Representante</h3>
                <p class="text-xs text-slate-400">Criar conta de acesso, limites e instância de WhatsApp.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.representatives.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold text-slate-300 block mb-1">Nome Completo *</label>
                <input type="text" name="name" required placeholder="Ex: Juliana Mendes"
                    class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Email de Acesso *</label>
                    <input type="email" name="email" required placeholder="juliana@comercial.com.br"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Senha Provisória *</label>
                    <input type="password" name="password" required placeholder="Mínimo 6 dígitos"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Telefone / WhatsApp</label>
                    <input type="text" name="phone" placeholder="5511999998888"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Código (Opcional)</label>
                    <input type="text" name="code" placeholder="REP-02"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Comissão Padrão (%)</label>
                    <input type="number" step="0.1" name="commission_rate" value="5.0"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Alçada Máx. Desconto (%)</label>
                    <input type="number" step="0.1" name="max_discount_pct" value="15.0"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-300 block mb-1">Nome da Instância WhatsApp (Evolution API)</label>
                <input type="text" name="whatsapp_instance" placeholder="Deixe em branco para gerar automaticamente"
                    class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <p class="text-[11px] text-slate-500 mt-1">Identificador exclusivo que o assistente usará na Evolution API.</p>
            </div>

            <div class="pt-3 flex gap-3">
                <button type="button" onclick="closeCreateRepModal()" class="flex-1 py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-all shadow-lg shadow-indigo-600/25">
                    Salvar Representante
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Representante -->
<div id="modal-edit-rep" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 max-w-lg w-full shadow-2xl space-y-6 relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeEditRepModal()" class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                <i data-lucide="edit-3" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-display font-bold text-lg text-white">Editar Representante</h3>
                <p class="text-xs text-slate-400">Atualizar limites comerciais e dados de acesso.</p>
            </div>
        </div>

        <form id="edit-rep-form" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="text-xs font-semibold text-slate-300 block mb-1">Nome Completo *</label>
                <input type="text" id="edit-name" name="name" required
                    class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Email de Acesso *</label>
                    <input type="email" id="edit-email" name="email" required
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Nova Senha (opcional)</label>
                    <input type="password" name="password" placeholder="Preencha só se quiser alterar"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-300 block mb-1">Telefone / WhatsApp</label>
                <input type="text" id="edit-phone" name="phone"
                    class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Comissão (%)</label>
                    <input type="number" step="0.1" id="edit-commission" name="commission_rate"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300 block mb-1">Alçada Máx. Desconto (%)</label>
                    <input type="number" step="0.1" id="edit-discount" name="max_discount_pct"
                        class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-300 block mb-1">Instância WhatsApp</label>
                <input type="text" id="edit-instance" name="whatsapp_instance"
                    class="w-full px-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="pt-3 flex gap-3">
                <button type="button" onclick="closeEditRepModal()" class="flex-1 py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-all shadow-lg shadow-indigo-600/25">
                    Atualizar Dados
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCreateRepModal() {
        document.getElementById('modal-create-rep').classList.remove('hidden');
    }

    function closeCreateRepModal() {
        document.getElementById('modal-create-rep').classList.add('hidden');
    }

    function openEditRepModal(rep) {
        const form = document.getElementById('edit-rep-form');
        form.action = `/admin/representantes/${rep.id}`;
        
        document.getElementById('edit-name').value = rep.name || '';
        document.getElementById('edit-email').value = rep.email || '';
        document.getElementById('edit-phone').value = rep.phone || '';
        document.getElementById('edit-commission').value = rep.commission_rate || '5.0';
        document.getElementById('edit-discount').value = rep.max_discount_pct || '15.0';
        document.getElementById('edit-instance').value = rep.whatsapp_instance || '';

        document.getElementById('modal-edit-rep').classList.remove('hidden');
    }

    function closeEditRepModal() {
        document.getElementById('modal-edit-rep').classList.add('hidden');
    }
</script>
@endpush
@endsection
