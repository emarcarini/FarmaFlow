@extends('layouts.app', ['title' => 'Inbox WhatsApp'])

@section('content')
<div class="max-w-7xl mx-auto h-[calc(100vh-8rem)] flex flex-col">
    <!-- Inbox Container -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 bg-slate-900/80 backdrop-blur-2xl border border-slate-800/80 rounded-3xl overflow-hidden shadow-2xl">
        <!-- Left: Conversations List (4 cols) -->
        <div class="lg:col-span-4 border-r border-slate-800/80 flex flex-col bg-slate-950/40">
            <!-- Search & Filters Header -->
            <div class="p-4 border-b border-slate-800/80">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display font-bold text-base text-white">Conversas WhatsApp</h3>
                    <span class="text-xs text-slate-500">{{ $conversations->count() }} chats</span>
                </div>
            </div>

            <!-- Conversations Feed -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-800/40">
                @forelse($conversations as $conv)
                    <a href="{{ route('portal.inbox', ['conversation_id' => $conv->id]) }}"
                        class="block p-4 transition-all hover:bg-slate-800/40 {{ $activeConversation?->id === $conv->id ? 'bg-indigo-600/15 border-l-4 border-indigo-500' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-xs text-indigo-400 flex-shrink-0">
                                    {{ substr($conv->contact?->name ?? 'CL', 0, 2) }}
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-sm font-semibold text-white truncate">{{ $conv->contact?->name ?? 'Contato' }}</h4>
                                    <p class="text-xs text-slate-400 truncate">{{ $conv->contact?->company?->trade_name ?? $conv->contact?->phone }}</p>
                                    <p class="text-xs text-slate-500 truncate mt-1">
                                        {{ $conv->messages->first()?->content ?? 'Sem mensagens recentes' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span class="text-[10px] text-slate-500">
                                    {{ $conv->last_message_at ? $conv->last_message_at->format('H:i') : '' }}
                                </span>
                                @if($conv->isHumanTakeover())
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        Humano
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        Robô IA
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-xs text-slate-500">
                        Nenhuma conversa registrada ainda.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Active Chat View (8 cols) -->
        <div class="lg:col-span-8 flex flex-col bg-slate-900/30">
            @if($activeConversation)
                <!-- Chat Header -->
                <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/80 backdrop-blur-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-xs text-indigo-400">
                            {{ substr($activeConversation->contact?->name ?? 'CL', 0, 2) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">{{ $activeConversation->contact?->name }}</h4>
                            <p class="text-xs text-slate-400">
                                {{ $activeConversation->contact?->company?->trade_name ?? 'Cliente Avulso' }} • {{ $activeConversation->contact?->phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Handover Controls -->
                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('portal.inbox.handover', $activeConversation) }}">
                            @csrf
                            @if($activeConversation->isAiHandling())
                                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 transition-all flex items-center gap-1.5">
                                    <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                    <span>Assumir Atendimento (Pausar Robô)</span>
                                </button>
                            @else
                                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-300 border border-emerald-500/30 transition-all flex items-center gap-1.5">
                                    <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                                    <span>Retomar IA Automática</span>
                                </button>
                            @endif
                        </form>
                    </div>
                </div>

                <!-- Messages Thread -->
                <div class="flex-1 p-6 overflow-y-auto space-y-4">
                    @forelse($activeConversation->messages as $msg)
                        @php
                            $isInbound = $msg->direction === 'inbound';
                        @endphp
                        <div class="flex flex-col {{ $isInbound ? 'items-start' : 'items-end' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] text-slate-500 font-medium">
                                    {{ $isInbound ? $activeConversation->contact?->name : ($msg->sender_type === 'ai' ? 'Agente IA' : 'Você') }}
                                </span>
                                <span class="text-[10px] text-slate-600">{{ $msg->created_at->format('H:i') }}</span>
                            </div>
                            <div class="max-w-lg p-3.5 rounded-2xl text-sm leading-relaxed {{ $isInbound ? 'bg-slate-800/90 text-slate-200 border border-slate-700/60 rounded-tl-sm' : ($msg->sender_type === 'ai' ? 'bg-indigo-600 text-white rounded-tr-sm shadow-md' : 'bg-slate-700 text-white rounded-tr-sm') }}">
                                {{ $msg->content }}
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex items-center justify-center text-xs text-slate-500">
                            Nenhuma mensagem registrada nesta conversa.
                        </div>
                    @endforelse
                </div>

                <!-- Message Input Bar -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                    <form method="POST" action="{{ route('portal.inbox.send', $activeConversation) }}" class="flex items-center gap-3">
                        @csrf
                        <input type="text" name="content" placeholder="Digite uma mensagem como representante humano..." required
                            class="flex-1 py-3 px-4 bg-slate-900 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                            <span>Enviar</span>
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            @else
                <div class="h-full flex items-center justify-center text-sm text-slate-500">
                    Selecione uma conversa ao lado para visualizar o chat.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
