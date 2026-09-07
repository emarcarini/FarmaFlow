@extends('layouts.app', ['title' => 'Tarefas & Follow-ups'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight">Tarefas Comerciais & Fila Diária de Follow-up</h2>
            <p class="text-sm text-slate-400 mt-1">Lista priorizada gerada pelas automações de recompras, cotações paradas e ações manuais.</p>
        </div>
    </div>

    <!-- Status Filter -->
    <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 w-fit">
        <a href="{{ route('portal.tasks', ['status' => 'pending']) }}"
            class="px-5 py-2 rounded-xl text-xs font-semibold transition-all {{ $status === 'pending' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
            Pendentes
        </a>
        <a href="{{ route('portal.tasks', ['status' => 'completed']) }}"
            class="px-5 py-2 rounded-xl text-xs font-semibold transition-all {{ $status === 'completed' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
            Concluídas
        </a>
        <a href="{{ route('portal.tasks', ['status' => 'all']) }}"
            class="px-5 py-2 rounded-xl text-xs font-semibold transition-all {{ $status === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
            Todas
        </a>
    </div>

    <!-- Tasks Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($tasks as $task)
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all">
                <div>
                    <!-- Header with Priority & Due Date -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold {{ $task->priority === 'urgent' || $task->priority === 'high' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' }}">
                            Prioridade {{ strtoupper($task->priority) }}
                        </span>
                        <span class="text-xs font-mono {{ $task->isOverdue() ? 'text-red-400 font-bold' : 'text-slate-500' }}">
                            {{ $task->due_date ? $task->due_date->format('d/m/Y H:i') : 'Sem prazo' }}
                        </span>
                    </div>

                    <!-- Title -->
                    <h3 class="font-display font-bold text-base text-white leading-snug">{{ $task->title }}</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">{{ $task->description }}</p>

                    <!-- Client Info -->
                    @if($task->company || $task->contact)
                        <div class="mt-4 pt-4 border-t border-slate-800/80 text-xs text-slate-400">
                            <p><span class="font-semibold text-slate-300">Empresa:</span> {{ $task->company?->trade_name ?? $task->company?->name ?? 'Geral' }}</p>
                            @if($task->contact)
                                <p><span class="font-semibold text-slate-300">Contato:</span> {{ $task->contact->name }} ({{ $task->contact->phone }})</p>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Footer Action -->
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-semibold {{ $task->status === 'completed' ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ $task->status === 'completed' ? '✓ Concluída' : '● Pendente' }}
                    </span>

                    @if($task->status !== 'completed')
                        <form method="POST" action="{{ route('portal.tasks.complete', $task) }}">
                            @csrf
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition-all flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Concluir</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-sm text-slate-500">
                Nenhuma tarefa pendente nesta visualização.
            </div>
        @endforelse
    </div>

    <div class="pt-4">{{ $tasks->links() }}</div>
</div>
@endsection
