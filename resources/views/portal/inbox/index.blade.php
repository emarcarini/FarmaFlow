@extends('layouts.app', ['title' => 'Inbox WhatsApp'])

@section('content')
<div class="max-w-7xl mx-auto h-[calc(100vh-8.5rem)] flex flex-col">
    <!-- HeroUI Split Inbox Container -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 heroui-card rounded-3xl overflow-hidden shadow-sm dark:shadow-2xl">
        <!-- Left: Conversations Feed (4 cols) -->
        <div class="lg:col-span-4 border-r border-slate-200/80 dark:border-slate-800/80 flex flex-col bg-slate-50/50 dark:bg-slate-950/50">
            <!-- Header & Search -->
            <div class="p-4 border-b border-slate-200/80 dark:border-slate-800/80 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Inbox WhatsApp</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
                        {{ $conversations->count() }} contatos
                    </span>
                </div>
            </div>

            <!-- Conversations Feed List -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/40">
                @forelse($conversations as $conv)
                    <a href="{{ route('portal.inbox', ['conversation_id' => $conv->id]) }}"
                        class="block p-4 transition-all hover:bg-slate-100/80 dark:hover:bg-slate-900/60 {{ $activeConversation?->id === $conv->id ? 'bg-indigo-500/10 dark:bg-indigo-600/15 border-l-4 border-indigo-600' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 dark:bg-slate-900 border border-indigo-500/20 dark:border-slate-800 flex items-center justify-center font-bold text-xs text-indigo-600 dark:text-indigo-400 flex-shrink-0 shadow-sm">
                                    {{ strtoupper(substr($conv->contact?->name ?? 'CL', 0, 2)) }}
                                </div>
                                <div class="truncate">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ $conv->contact?->name ?? 'Contato' }}</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $conv->contact?->company?->trade_name ?? $conv->contact?->phone }}</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 truncate mt-1">
                                        {{ $conv->messages->first()?->content ?? 'Sem mensagens recentes' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                                <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500">
                                    {{ $conv->last_message_at ? $conv->last_message_at->format('H:i') : '' }}
                                </span>
                                @if($conv->isHumanTakeover())
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/20 dark:border-amber-500/30">
                                        Humano
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/20 dark:border-emerald-500/30">
                                        Robô IA
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-12 text-center text-xs text-slate-400 dark:text-slate-500">
                        <i data-lucide="message-square" class="w-8 h-8 text-slate-300 dark:text-slate-700 mx-auto mb-2"></i>
                        Nenhuma conversa registrada ainda.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Active Chat Window (8 cols) -->
        <div class="lg:col-span-8 flex flex-col bg-slate-50/20 dark:bg-slate-900/20">
            @if($activeConversation)
                <!-- Chat Topbar Header -->
                <div class="p-4 border-b border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between bg-white/80 dark:bg-slate-950/70 backdrop-blur-xl">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-600/10 dark:bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center font-bold text-xs text-indigo-600 dark:text-indigo-400 shadow-sm">
                            {{ strtoupper(substr($activeConversation->contact?->name ?? 'CL', 0, 2)) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ $activeConversation->contact?->name }}</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $activeConversation->contact?->company?->trade_name ?? 'Cliente Avulso' }} • <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $activeConversation->contact?->phone }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Handover Controls -->
                    <div class="flex items-center gap-3">
                        <form method="POST" action="/inbox/{{ $activeConversation->id }}/handover">
                            @csrf
                            @if($activeConversation->isAiHandling())
                                <button type="submit" class="px-3.5 py-2 rounded-2xl text-xs font-bold bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/20 dark:border-amber-500/30 transition-all flex items-center gap-2 shadow-sm">
                                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                    <span>Assumir Atendimento (Pausar Robô)</span>
                                </button>
                            @else
                                <button type="submit" class="px-3.5 py-2 rounded-2xl text-xs font-bold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/20 dark:border-emerald-500/30 transition-all flex items-center gap-2 shadow-sm">
                                    <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                                    <span>Retomar Robô IA (Gemini)</span>
                                </button>
                            @endif
                        </form>
                    </div>
                </div>

                <!-- Messages Thread -->
                <div class="flex-1 p-6 overflow-y-auto space-y-4" id="messages-container">
                    @forelse($activeConversation->messages as $msg)
                        @php
                            $isInbound = $msg->direction === 'inbound';
                        @endphp
                        <div class="flex flex-col {{ $isInbound ? 'items-start' : 'items-end' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">
                                    {{ $isInbound ? $activeConversation->contact?->name : ($msg->sender_type === 'ai' ? '🤖 Agente IA (Gemini)' : '👤 Você (Representante)') }}
                                </span>
                                <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500">{{ $msg->created_at->format('H:i') }}</span>
                            </div>
                            <div class="max-w-lg p-4 rounded-3xl text-sm leading-relaxed {{ $isInbound ? 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 rounded-tl-sm shadow-sm' : ($msg->sender_type === 'ai' ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-tr-sm shadow-lg shadow-indigo-600/20' : 'bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white rounded-tr-sm border border-slate-300 dark:border-slate-700/60') }}">
                                {{ $msg->content }}
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex items-center justify-center text-xs text-slate-400 dark:text-slate-500">
                            Nenhuma mensagem registrada nesta conversa.
                        </div>
                    @endforelse
                </div>

                <!-- Quick Smart Chips -->
                <div class="px-4 py-2 bg-slate-100 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800/60 flex items-center gap-2 overflow-x-auto text-[11px]">
                    <span class="text-slate-400 dark:text-slate-500 font-bold flex-shrink-0">Sugestões:</span>
                    <button type="button" onclick="insertMsg('Olá! Segue nossa tabela especial com descontos progressivos por volume.')" class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap transition-all shadow-sm">
                        📦 Enviar Condições de Volume
                    </button>
                    <button type="button" onclick="insertMsg('Temos uma campanha ativa de inverno com preço especial para analgésicos acima de 100 unidades.')" class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap transition-all shadow-sm">
                        🏷️ Divulgar Campanha Inverno
                    </button>
                </div>

                <!-- Message Input Bar -->
                <div class="p-4 border-t border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-950">
                    <form method="POST" action="/inbox/{{ $activeConversation->id }}/send" class="flex items-center gap-3">
                        @csrf
                        <input type="text" name="content" id="msg-input" placeholder="Digite uma resposta como representante..." required
                            class="flex-1 py-3 px-4 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold transition-all shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                            <span>Enviar</span>
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            @else
                <div class="h-full flex flex-col items-center justify-center text-sm text-slate-500 p-8">
                    <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-600 mb-4 shadow-sm">
                        <i data-lucide="message-square" class="w-8 h-8"></i>
                    </div>
                    <p class="font-bold text-slate-800 dark:text-slate-300">Nenhuma conversa selecionada</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Selecione um contato na lista à esquerda para interagir.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function insertMsg(text) {
        const input = document.getElementById('msg-input');
        if (input) {
            input.value = text;
            input.focus();
        }
    }

    // Scroll automático para a última mensagem
    document.addEventListener('DOMContentLoaded', () => {
        const msgContainer = document.getElementById('messages-container');
        if (msgContainer) {
            msgContainer.scrollTop = msgContainer.scrollHeight;
        }

        // Auto-refresh suave do Inbox a cada 6 segundos para checar novas mensagens do WhatsApp
        setInterval(() => {
            const inputField = document.getElementById('msg-input');
            // Não recarrega se o usuário estiver digitando no campo de texto
            if (inputField && document.activeElement === inputField && inputField.value.trim() !== '') {
                return;
            }

            fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Atualiza a lista de conversas
                const newFeed = doc.querySelector('.lg\\:col-span-4 .overflow-y-auto');
                const curFeed = document.querySelector('.lg\\:col-span-4 .overflow-y-auto');
                if (newFeed && curFeed && newFeed.innerHTML !== curFeed.innerHTML) {
                    curFeed.innerHTML = newFeed.innerHTML;
                    lucide.createIcons();
                }

                // Atualiza as mensagens se houver uma conversa aberta
                const newMessages = doc.getElementById('messages-container');
                const curMessages = document.getElementById('messages-container');
                if (newMessages && curMessages && newMessages.innerHTML !== curMessages.innerHTML) {
                    curMessages.innerHTML = newMessages.innerHTML;
                    curMessages.scrollTop = curMessages.scrollHeight;
                    lucide.createIcons();
                }
            })
            .catch(() => {});
        }, 5000);
    });
</script>
@endsection
