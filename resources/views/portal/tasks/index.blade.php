@extends('layouts.app', ['title' => 'Tarefas & Follow-ups'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                <span>Fila Operacional</span>
            </div>
            <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white tracking-tight">Tarefas Comerciais & Follow-up Diário</h2>
            <p class="text-sm text-slate-400 mt-1">Lista priorizada gerada pelas regras de recompra, cotações sem resposta e alertas manuais.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-2xl bg-slate-900/80 border border-slate-800 flex items-center gap-3 shadow-lg">
                <i data-lucide="bell-ring" class="w-5 h-5 text-amber-400"></i>
                <div class="text-xs">
                    <span class="text-slate-400 block">Total na Fila</span>
                    <span class="text-white font-bold font-display text-sm">{{ $tasks->total() }} Tarefas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Meraki UI Status Tabs -->
    <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800/80 w-fit backdrop-blur-xl">
        <a href="{{ route('portal.tasks', ['status' => 'pending']) }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $status === 'pending' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
            <span>Pendentes</span>
        </a>
        <a href="{{ route('portal.tasks', ['status' => 'completed']) }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $status === 'completed' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
            <span>Concluídas</span>
        </a>
        <a href="{{ route('portal.tasks', ['status' => 'all']) }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $status === 'all' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
            <span>Todas</span>
        </a>
    </div>

    <!-- Meraki UI Tasks Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($tasks as $task)
            <div class="group bg-gradient-to-b from-slate-900/90 to-slate-950/90 backdrop-blur-xl border border-slate-800/80 hover:border-indigo-500/40 rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:shadow-indigo-950/20 transition-all flex flex-col justify-between">
                <div>
                    <!-- Header with Priority & Due Date -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-bold {{ $task->priority === 'urgent' || $task->priority === 'high' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $task->priority === 'urgent' || $task->priority === 'high' ? 'bg-rose-400' : 'bg-indigo-400' }}"></span>
                            Prioridade {{ strtoupper($task->priority) }}
                        </span>
                        <span class="text-xs font-mono {{ $task->isOverdue() ? 'text-rose-400 font-bold' : 'text-slate-500' }}">
                            {{ $task->due_date ? $task->due_date->format('d/m/Y H:i') : 'Sem prazo' }}
                        </span>
                    </div>

                    <!-- Title & Description -->
                    <h3 class="font-display font-bold text-base text-white leading-snug group-hover:text-indigo-300 transition-colors">
                        {{ $task->title }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        {{ $task->description }}
                    </p>

                    <!-- Client / Contact Info -->
                    @if($task->company || $task->contact)
                        <div class="mt-5 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-400 space-y-1.5">
                            @if($task->company)
                                <div class="flex items-center gap-1.5 text-slate-300 font-medium">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-400"></i>
                                    <span>{{ $task->company->trade_name ?? $task->company->name }}</span>
                                </div>
                            @endif
                            @if($task->contact)
                                <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-800/60">
                                    <span class="text-slate-400">{{ $task->contact->name }}</span>
                                    <span class="text-emerald-400 font-mono">{{ $task->contact->phone }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Footer Action -->
                <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $task->status === 'completed' ? 'text-emerald-400' : 'text-amber-400' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $task->status === 'completed' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                        {{ $task->status === 'completed' ? 'Concluída' : 'Pendente' }}
                    </span>

                    @if($task->status !== 'completed')
                        <form method="POST" action="{{ route('portal.tasks.complete', $task) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-xs font-semibold transition-all flex items-center gap-1.5 shadow-sm">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Concluir</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center rounded-3xl bg-slate-900/40 border border-slate-800/80">
                <div class="w-14 h-14 rounded-2xl bg-slate-800/80 flex items-center justify-center mx-auto text-slate-500 mb-3">
                    <i data-lucide="check-circle-2" class="w-6 h-6 text-emerald-400"></i>
                </div>
                <h3 class="font-display font-bold text-base text-white">Nenhuma tarefa pendente</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Você está em dia com todos os follow-ups e avisos da sua carteira comercial.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-4">{{ $tasks->links() }}</div>
</div>
@endsection
