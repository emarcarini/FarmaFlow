@extends('layouts.app', ['title' => 'Inbox WhatsApp'])

@section('content')
<div class="max-w-[1600px] mx-auto h-[calc(100vh-7.5rem)] flex flex-col">
    <!-- WhatsApp Web Shell Container -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 rounded-3xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#111b21]">

        <!-- ========================================================================= -->
        <!-- LEFT PANEL: CONVERSATIONS FEED (4 COLS)                                    -->
        <!-- ========================================================================= -->
        <div class="lg:col-span-4 border-r border-slate-200 dark:border-slate-800/80 flex flex-col bg-slate-50/60 dark:bg-[#111b21] h-full">

            <!-- 1. WhatsApp Topbar -->
            <div class="p-3.5 px-4 bg-slate-100/90 dark:bg-[#202c33] border-b border-slate-200 dark:border-slate-800/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 border border-emerald-400/40 flex items-center justify-center font-bold text-xs text-white shadow-sm">
                            {{ strtoupper(substr(auth()->user()->name ?? 'EM', 0, 2)) }}
                        </div>
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white dark:border-[#202c33]" title="Online"></span>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-sm text-slate-900 dark:text-[#e9edef] leading-tight">WhatsApp Comercial</h3>
                        <p class="text-[11px] text-slate-500 dark:text-[#8696a0]">FarmaFlow • {{ auth()->user()->name }}</p>
                    </div>
                </div>

                <!-- Action Icons (WhatsApp Web Style) -->
                <div class="flex items-center gap-1.5">
                    <!-- Botão: Regras do Bot IA -->
                    <button type="button" onclick="openBotRulesModal()" title="Instruções & Regras do Bot IA"
                        class="p-2 rounded-xl text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-800/50 transition-all flex items-center gap-1 text-xs font-bold shadow-sm">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Regras IA</span>
                    </button>

                    <!-- Botão: Contatos (Nova Conversa) -->
                    <button type="button" onclick="openContactsModal()" title="Abrir Lista de Contatos / Nova Conversa"
                        class="p-2 rounded-xl text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 border border-emerald-200/60 dark:border-emerald-800/50 transition-all flex items-center gap-1 text-xs font-bold shadow-sm">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Contatos</span>
                    </button>
                </div>
            </div>

            <!-- 2. Search Box & Filter Chips -->
            <div class="p-3 bg-white dark:bg-[#111b21] border-b border-slate-200/80 dark:border-slate-800/60 space-y-2.5">
                <!-- Search Input -->
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 dark:text-[#8696a0] absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" id="chat-search-input" onkeyup="filterConversationsLocal(this.value)" placeholder="Pesquisar ou começar uma nova conversa"
                        value="{{ $search }}"
                        class="w-full pl-10 pr-8 py-2 bg-slate-100 dark:bg-[#202c33] border-none rounded-xl text-xs text-slate-900 dark:text-[#d1d7db] placeholder-slate-400 dark:placeholder-[#8696a0] focus:outline-none focus:ring-2 focus:ring-emerald-500/50 transition-all">
                    @if($search)
                        <a href="{{ route('portal.inbox', ['filter' => $filter]) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>

                <!-- Filter Chips (Todas / Arquivadas) -->
                <div class="flex items-center gap-1.5 text-xs">
                    <a href="{{ route('portal.inbox', ['filter' => 'active']) }}"
                        class="px-3 py-1 rounded-full font-semibold transition-all {{ $filter === 'active' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'text-slate-500 dark:text-[#8696a0] hover:bg-slate-100 dark:hover:bg-[#202c33]' }}">
                        Todas <span class="text-[10px] opacity-80">({{ $activeCount }})</span>
                    </a>

                    <a href="{{ route('portal.inbox', ['filter' => 'archived']) }}"
                        class="px-3 py-1 rounded-full font-semibold transition-all {{ $filter === 'archived' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'text-slate-500 dark:text-[#8696a0] hover:bg-slate-100 dark:hover:bg-[#202c33]' }}">
                        <i data-lucide="archive" class="w-3 h-3 inline-block mr-1"></i>
                        Arquivadas <span class="text-[10px] opacity-80">({{ $archivedCount }})</span>
                    </a>
                </div>
            </div>

            <!-- 3. Conversations Feed List -->
            <div id="conversations-list-container" class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/40">
                @if($filter === 'archived')
                    <div class="p-3 bg-amber-500/10 border-b border-amber-500/20 text-xs text-amber-600 dark:text-amber-400 flex items-center justify-between">
                        <span class="flex items-center gap-1.5 font-bold">
                            <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                            Conversas Arquivadas
                        </span>
                        <a href="{{ route('portal.inbox', ['filter' => 'active']) }}" class="text-[11px] underline hover:text-amber-500">Voltar para Ativas</a>
                    </div>
                @endif

                @forelse($conversations as $conv)
                    @php
                        $lastMsg = $conv->messages->first();
                        $isOutbound = $lastMsg && $lastMsg->direction === 'outbound';
                        $isActive = $activeConversation?->id === $conv->id;
                    @endphp
                    <div class="chat-item group relative transition-all {{ $isActive ? 'bg-slate-200/80 dark:bg-[#2a3942]' : 'hover:bg-slate-100/70 dark:hover:bg-[#202c33]' }}"
                        data-name="{{ strtolower($conv->contact?->name ?? '') }}"
                        data-phone="{{ $conv->contact?->phone ?? '' }}">
                        <a href="{{ route('portal.inbox', ['conversation_id' => $conv->id, 'filter' => $filter]) }}" class="block p-3.5">
                            <div class="flex items-center gap-3">
                                <!-- Avatar -->
                                <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-[#202c33] border border-slate-300 dark:border-slate-700/60 flex items-center justify-center font-bold text-sm text-slate-700 dark:text-[#d1d7db] flex-shrink-0 shadow-sm">
                                    {{ strtoupper(substr($conv->contact?->name ?? 'C', 0, 2)) }}
                                </div>

                                <!-- Contact Info & Message Preview -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline justify-between mb-0.5">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-[#e9edef] truncate">
                                            {{ $conv->contact?->name ?? 'Cliente WhatsApp' }}
                                        </h4>
                                        <span class="text-[11px] font-mono text-slate-400 dark:text-[#8696a0] flex-shrink-0 ml-2">
                                            {{ $conv->last_message_at ? $conv->last_message_at->format('H:i') : '' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between gap-1">
                                        <div class="flex items-center gap-1 min-w-0 text-xs text-slate-500 dark:text-[#8696a0] truncate">
                                            @if($isOutbound)
                                                <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0"></i>
                                            @endif
                                            <span class="truncate">
                                                {{ $lastMsg?->content ?? 'Conversa iniciada' }}
                                            </span>
                                        </div>

                                        <!-- Badge: Status IA / Humano -->
                                        @if($conv->isHumanTakeover())
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex-shrink-0">
                                                Humano
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex-shrink-0">
                                                Robô IA
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>

                        <!-- Quick Action: Arquivar / Desarquivar (Hover) -->
                        <div class="absolute right-3 bottom-2 hidden group-hover:flex items-center gap-1 z-10">
                            @if($conv->is_archived)
                                <form method="POST" action="/inbox/{{ $conv->id }}/unarchive">
                                    @csrf
                                    <button type="submit" title="Desarquivar Conversa" class="p-1.5 rounded-lg bg-white dark:bg-[#111b21] shadow border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:text-emerald-500">
                                        <i data-lucide="archive-restore" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="/inbox/{{ $conv->id }}/archive">
                                    @csrf
                                    <button type="submit" title="Arquivar Conversa" class="p-1.5 rounded-lg bg-white dark:bg-[#111b21] shadow border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:text-amber-500">
                                        <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-xs text-slate-400 dark:text-[#8696a0] space-y-3">
                        <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-[#202c33] flex items-center justify-center mx-auto text-slate-400 dark:text-slate-500 shadow-inner">
                            <i data-lucide="message-square" class="w-7 h-7"></i>
                        </div>
                        <p class="font-bold text-slate-700 dark:text-slate-300">
                            {{ $filter === 'archived' ? 'Nenhuma conversa arquivada.' : 'Nenhuma conversa ativa no momento.' }}
                        </p>
                        <p class="text-[11px] text-slate-400 dark:text-[#8696a0] max-w-xs mx-auto">
                            Abra a lista de contatos para iniciar um novo atendimento ou aguarde novas mensagens recebidas no WhatsApp.
                        </p>
                        <button type="button" onclick="openContactsModal()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all inline-flex items-center gap-2">
                            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                            <span>Ver Lista de Contatos</span>
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- RIGHT PANEL: WHATSAPP WEB CHAT WINDOW (8 COLS)                            -->
        <!-- ========================================================================= -->
        <div class="lg:col-span-8 flex flex-col bg-slate-100/60 dark:bg-[#0c1317] h-full relative">

            @if($activeConversation)
                <!-- 1. Chat Topbar Header (WhatsApp Web Style) -->
                <div class="p-3 px-4 bg-slate-100/90 dark:bg-[#202c33] border-b border-slate-200 dark:border-slate-800/60 flex items-center justify-between z-10">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-full bg-emerald-600/10 dark:bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center font-bold text-xs text-emerald-600 dark:text-emerald-400 shadow-sm">
                            {{ strtoupper(substr($activeConversation->contact?->name ?? 'CL', 0, 2)) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-[#e9edef] leading-tight flex items-center gap-2">
                                <span>{{ $activeConversation->contact?->name }}</span>
                                @if($activeConversation->contact?->company)
                                    <span class="text-[10px] font-normal px-2 py-0.5 rounded-full bg-slate-200 dark:bg-[#111b21] text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                                        {{ $activeConversation->contact->company->trade_name ?? $activeConversation->contact->company->name }}
                                    </span>
                                @endif
                            </h4>
                            <p class="text-[11px] text-slate-500 dark:text-[#8696a0] flex items-center gap-1.5 mt-0.5">
                                <span class="font-mono">{{ $activeConversation->contact?->phone }}</span>
                                <span>•</span>
                                @if($activeConversation->isHumanTakeover())
                                    <span class="text-amber-500 font-bold flex items-center gap-1">
                                        <i data-lucide="user" class="w-3 h-3"></i> Atendimento Humano
                                    </span>
                                @else
                                    <span class="text-emerald-500 font-bold flex items-center gap-1">
                                        <i data-lucide="bot" class="w-3 h-3"></i> IA Respondendo
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Topbar Controls -->
                    <div class="flex items-center gap-2">
                        <!-- Toggle Handover (Assumir / Retomar Robô) -->
                        <form method="POST" action="/inbox/{{ $activeConversation->id }}/handover">
                            @csrf
                            @if($activeConversation->isAiHandling())
                                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30 transition-all flex items-center gap-1.5 shadow-sm">
                                    <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                                    <span>Pausar Robô IA</span>
                                </button>
                            @else
                                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30 transition-all flex items-center gap-1.5 shadow-sm">
                                    <i data-lucide="play-circle" class="w-3.5 h-3.5"></i>
                                    <span>Retomar Robô IA</span>
                                </button>
                            @endif
                        </form>

                        <!-- Botão Arquivar / Desarquivar -->
                        @if($activeConversation->is_archived)
                            <form method="POST" action="/inbox/{{ $activeConversation->id }}/unarchive">
                                @csrf
                                <button type="submit" title="Desarquivar Conversa" class="p-2 rounded-xl text-slate-500 hover:text-emerald-500 hover:bg-slate-200 dark:hover:bg-[#111b21] transition-all">
                                    <i data-lucide="archive-restore" class="w-4 h-4"></i>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="/inbox/{{ $activeConversation->id }}/archive">
                                @csrf
                                <button type="submit" title="Arquivar Conversa" class="p-2 rounded-xl text-slate-500 hover:text-amber-500 hover:bg-slate-200 dark:hover:bg-[#111b21] transition-all">
                                    <i data-lucide="archive" class="w-4 h-4"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- 2. Chat Messages Thread (WhatsApp Web Bubble Styling) -->
                <div class="flex-1 p-4 md:p-6 overflow-y-auto space-y-3 bg-[#efeae2]/30 dark:bg-[#0b141a]/60 relative" id="messages-container">
                    <!-- WhatsApp subtle pattern overlay -->
                    <div class="space-y-3 relative z-10">
                        @forelse($activeConversation->messages as $msg)
                            @php
                                $isInbound = $msg->direction === 'inbound';
                            @endphp
                            <div class="flex flex-col {{ $isInbound ? 'items-start' : 'items-end' }}">
                                <div class="max-w-md md:max-w-lg p-3 px-4 rounded-2xl text-xs md:text-sm leading-relaxed shadow-sm relative {{ $isInbound ? 'bg-white dark:bg-[#202c33] text-slate-900 dark:text-[#e9edef] rounded-tl-none border border-slate-200/50 dark:border-transparent' : ($msg->sender_type === 'ai' ? 'bg-[#005c4b] text-white rounded-tr-none shadow-md' : 'bg-indigo-600 dark:bg-[#005c4b] text-white rounded-tr-none shadow-md') }}">
                                    <!-- Sender label for AI or Rep -->
                                    @if(!$isInbound)
                                        <div class="text-[10px] font-bold text-emerald-200 dark:text-[#53bdeb] mb-1 flex items-center gap-1">
                                            @if($msg->sender_type === 'ai')
                                                <i data-lucide="bot" class="w-3 h-3"></i> Assistente IA (Gemini)
                                            @else
                                                <i data-lucide="user" class="w-3 h-3"></i> Você (Emmanuel)
                                            @endif
                                        </div>
                                    @endif

                                    <p class="whitespace-pre-wrap select-text">{!! nl2br(e($msg->content)) !!}</p>

                                    <!-- Time & Status Checks -->
                                    <div class="flex items-center justify-end gap-1 mt-1 text-[10px] opacity-75">
                                        <span>{{ $msg->created_at->format('H:i') }}</span>
                                        @if(!$isInbound)
                                            <i data-lucide="check-check" class="w-3 h-3 text-cyan-300 dark:text-[#53bdeb]"></i>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="h-64 flex flex-col items-center justify-center text-xs text-slate-400 dark:text-[#8696a0] space-y-2">
                                <div class="w-12 h-12 rounded-full bg-slate-200/60 dark:bg-[#202c33] flex items-center justify-center">
                                    <i data-lucide="messages-square" class="w-6 h-6 text-emerald-500"></i>
                                </div>
                                <p class="font-bold text-slate-700 dark:text-slate-300">Inicie uma conversa com {{ $activeConversation->contact?->name }}</p>
                                <p class="text-[11px] text-slate-400">Digite uma mensagem abaixo ou selecione uma sugestão rápida.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 3. Smart Commercial Suggestion Chips -->
                <div class="px-4 py-2 bg-slate-100/90 dark:bg-[#202c33]/80 border-t border-slate-200 dark:border-slate-800/60 flex items-center gap-2 overflow-x-auto text-[11px]">
                    <span class="text-slate-400 dark:text-[#8696a0] font-bold flex-shrink-0">Sugestões Rápidas:</span>
                    <button type="button" onclick="insertMsg('Olá! Segue nossa tabela especial com descontos progressivos por volume.')" class="px-2.5 py-1 rounded-xl bg-white dark:bg-[#111b21] hover:bg-slate-50 dark:hover:bg-[#2a3942] border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap transition-all shadow-sm">
                        📦 Tabela de Volume
                    </button>
                    <button type="button" onclick="insertMsg('Temos uma campanha ativa de inverno com preço especial para analgésicos acima de 100 unidades.')" class="px-2.5 py-1 rounded-xl bg-white dark:bg-[#111b21] hover:bg-slate-50 dark:hover:bg-[#2a3942] border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap transition-all shadow-sm">
                        🏷️ Campanha Inverno
                    </button>
                    <button type="button" onclick="insertMsg('Posso formalizar a cotação com prazo de pagamento para 28 dias no boleto?')" class="px-2.5 py-1 rounded-xl bg-white dark:bg-[#111b21] hover:bg-slate-50 dark:hover:bg-[#2a3942] border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap transition-all shadow-sm">
                        💳 Condição Faturada (28d)
                    </button>
                </div>

                <!-- 4. WhatsApp Message Input Bar -->
                <div class="p-3 px-4 border-t border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#202c33] z-10">
                    <form method="POST" action="/inbox/{{ $activeConversation->id }}/send" class="flex items-center gap-3">
                        @csrf
                        <button type="button" onclick="insertMsg('😊 ')" title="Emoji" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                            <i data-lucide="smile" class="w-5 h-5"></i>
                        </button>

                        <input type="text" name="content" id="msg-input" placeholder="Mensagem..." required autofocus
                            class="flex-1 py-3 px-4 bg-slate-100 dark:bg-[#2a3942] border-none rounded-2xl text-slate-900 dark:text-[#d1d7db] placeholder-slate-400 dark:placeholder-[#8696a0] text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all">

                        <button type="submit" class="p-3 px-5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition-all shadow-lg shadow-emerald-600/30 flex items-center gap-2">
                            <span>Enviar</span>
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

            @else
                <!-- WhatsApp Web Signature Empty Screen (No Active Chat) -->
                <div class="h-full flex flex-col items-center justify-center text-center p-8 space-y-6">
                    <div class="w-24 h-24 rounded-full bg-emerald-500/10 dark:bg-[#202c33] border border-emerald-500/20 dark:border-slate-700/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-xl">
                        <i data-lucide="message-circle" class="w-12 h-12"></i>
                    </div>

                    <div class="max-w-md space-y-2">
                        <h3 class="text-xl font-display font-bold text-slate-900 dark:text-[#e9edef]">WhatsApp Comercial FarmaFlow</h3>
                        <p class="text-xs text-slate-500 dark:text-[#8696a0] leading-relaxed">
                            Envie cotações e atenda seus clientes com o Assistente Comercial do Google Gemini, integrado à instância dedicada <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">farmaflow</span>.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" onclick="openContactsModal()" class="px-5 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/25 transition-all flex items-center gap-2">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            <span>Abrir Lista de Contatos</span>
                        </button>

                        <button type="button" onclick="openBotRulesModal()" class="px-5 py-2.5 rounded-2xl bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 font-bold text-xs transition-all flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                            <span>Regras da IA</span>
                        </button>
                    </div>

                    <div class="pt-8 text-[11px] text-slate-400 dark:text-[#667781] flex items-center gap-1.5 border-t border-slate-200 dark:border-slate-800/60 max-w-xs">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                        <span>Criptografia de ponta a ponta conectada ao Evolution API</span>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: LISTA DE CONTATOS (NOVA CONVERSA) ESTILO WHATSAPP WEB             -->
<!-- ========================================================================= -->
<div id="contacts-modal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111b21] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-xl w-full shadow-2xl space-y-4 relative flex flex-col max-h-[85vh]">
        <!-- Header -->
        <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800/80">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-base text-slate-900 dark:text-[#e9edef]">Nova Conversa • Contatos</h3>
                    <p class="text-xs text-slate-500 dark:text-[#8696a0]">{{ $totalContactsCount }} contatos sincronizados</p>
                </div>
            </div>
            <button onclick="closeContactsModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#202c33] transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="contact-modal-search" oninput="searchContactsAjax(this.value)" placeholder="Pesquisar contato por nome, telefone ou empresa..."
                class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-[#202c33] border-none rounded-2xl text-xs text-slate-900 dark:text-[#d1d7db] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <!-- Contacts Scrollable List -->
        <div id="contacts-modal-list" class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/40 pr-1 space-y-1">
            @foreach($initialContacts as $c)
                <form method="POST" action="{{ route('portal.inbox.start') }}">
                    @csrf
                    <input type="hidden" name="contact_id" value="{{ $c->id }}">
                    <button type="submit" class="w-full p-3 rounded-2xl hover:bg-slate-100 dark:hover:bg-[#202c33] flex items-center justify-between text-left transition-all group">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                {{ strtoupper(substr($c->name ?? 'C', 0, 2)) }}
                            </div>
                            <div class="truncate">
                                <h5 class="text-xs font-bold text-slate-900 dark:text-[#e9edef] truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                    {{ $c->name }}
                                </h5>
                                <p class="text-[11px] text-slate-500 dark:text-[#8696a0] font-mono truncate">
                                    {{ $c->phone }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if($c->company)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-[#111b21] text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 max-w-[120px] truncate">
                                    {{ $c->company->trade_name ?? $c->company->name }}
                                </span>
                            @endif
                            <i data-lucide="message-square-plus" class="w-4 h-4 text-slate-400 group-hover:text-emerald-500 transition-colors"></i>
                        </div>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: REGRAS E INSTRUÇÕES DO BOT IA (GOOGLE GEMINI)                     -->
<!-- ========================================================================= -->
<div id="bot-rules-modal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111b21] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 max-w-2xl w-full shadow-2xl space-y-5 relative flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800/80">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="sparkles" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-[#e9edef]">Regras & Instruções do Bot IA</h3>
                    <p class="text-xs text-slate-500 dark:text-[#8696a0]">Diretrizes comerciais que o assistente segue obrigatoriamente no WhatsApp.</p>
                </div>
            </div>
            <button onclick="closeBotRulesModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#202c33] transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Add New Rule Form -->
        <form id="add-rule-form" onsubmit="submitNewRule(event)" class="p-4 rounded-2xl bg-slate-50 dark:bg-[#202c33]/70 border border-slate-200 dark:border-slate-700/60 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-500"></i>
                    Adicionar Nova Instrução / Regra
                </span>
                <span class="text-[10px] text-slate-400">Aplicada em tempo real pelo Gemini</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                <input type="text" id="new-rule-title" placeholder="Título (ex: Frete Cortesia)"
                    class="md:col-span-1 px-3 py-2 bg-white dark:bg-[#111b21] border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <input type="text" id="new-rule-content" required placeholder="Instrução (ex: Oferecer frete grátis para compras acima de R$ 800)"
                    class="md:col-span-2 px-3 py-2 bg-white dark:bg-[#111b21] border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Salvar Regra</span>
                </button>
            </div>
        </form>

        <!-- Current Rules List -->
        <div class="flex-1 overflow-y-auto space-y-2.5 pr-1" id="bot-rules-list">
            <div class="text-center py-6 text-xs text-slate-400">
                <i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto text-indigo-500 mb-2"></i>
                Carregando regras...
            </div>
        </div>
    </div>
</div>

<script>
    // Inserir texto rápido no input
    function insertMsg(text) {
        const input = document.getElementById('msg-input');
        if (input) {
            input.value = text;
            input.focus();
        }
    }

    // Filtro local instantâneo das conversas na barra lateral
    function filterConversationsLocal(query) {
        const term = query.toLowerCase().trim();
        const items = document.querySelectorAll('#conversations-list-container .chat-item');
        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const phone = item.getAttribute('data-phone') || '';
            if (name.includes(term) || phone.includes(term)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Modal de Contatos
    function openContactsModal() {
        document.getElementById('contacts-modal').classList.remove('hidden');
        document.getElementById('contact-modal-search').focus();
    }

    function closeContactsModal() {
        document.getElementById('contacts-modal').classList.add('hidden');
    }

    let searchContactTimer = null;
    function searchContactsAjax(term) {
        clearTimeout(searchContactTimer);
        searchContactTimer = setTimeout(async () => {
            const list = document.getElementById('contacts-modal-list');
            try {
                const res = await fetch(`/inbox/contacts?q=${encodeURIComponent(term)}`);
                const data = await res.json();

                if (data.length === 0) {
                    list.innerHTML = '<div class="p-8 text-center text-xs text-slate-400">Nenhum contato encontrado.</div>';
                    return;
                }

                list.innerHTML = data.map(c => `
                    <form method="POST" action="{{ route('portal.inbox.start') }}">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                        <input type="hidden" name="contact_id" value="${c.id}">
                        <button type="submit" class="w-full p-3 rounded-2xl hover:bg-slate-100 dark:hover:bg-[#202c33] flex items-center justify-between text-left transition-all group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    ${c.avatar}
                                </div>
                                <div class="truncate">
                                    <h5 class="text-xs font-bold text-slate-900 dark:text-[#e9edef] truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                        ${c.name}
                                    </h5>
                                    <p class="text-[11px] text-slate-500 dark:text-[#8696a0] font-mono truncate">${c.phone}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                ${c.company ? `<span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-[#111b21] text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 max-w-[120px] truncate">${c.company}</span>` : ''}
                                <i data-lucide="message-square-plus" class="w-4 h-4 text-slate-400 group-hover:text-emerald-500 transition-colors"></i>
                            </div>
                        </button>
                    </form>
                `).join('');
                lucide.createIcons();
            } catch (err) {
                console.error('Erro ao buscar contatos:', err);
            }
        }, 250);
    }

    // Modal de Regras do Bot IA
    function openBotRulesModal() {
        document.getElementById('bot-rules-modal').classList.remove('hidden');
        loadBotRules();
    }

    function closeBotRulesModal() {
        document.getElementById('bot-rules-modal').classList.add('hidden');
    }

    async function loadBotRules() {
        const list = document.getElementById('bot-rules-list');
        try {
            const res = await fetch('{{ route("portal.bot-rules.index") }}');
            const rules = await res.json();

            if (rules.length === 0) {
                list.innerHTML = '<div class="p-8 text-center text-xs text-slate-400">Nenhuma regra cadastrada ainda. Adicione uma instrução acima!</div>';
                return;
            }

            list.innerHTML = rules.map(r => `
                <div class="p-3.5 rounded-2xl bg-white dark:bg-[#202c33] border ${r.is_active ? 'border-slate-200 dark:border-slate-700' : 'border-slate-200/50 dark:border-slate-800 opacity-60'} flex items-start justify-between gap-3 transition-all shadow-sm">
                    <div class="space-y-1 min-w-0">
                        <div class="flex items-center gap-2">
                            ${r.title ? `<span class="text-[11px] font-bold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">${r.title}</span>` : ''}
                            <span class="text-[10px] font-bold ${r.is_active ? 'text-emerald-500' : 'text-slate-400'}">
                                ${r.is_active ? '● Ativa' : '○ Pausada'}
                            </span>
                        </div>
                        <p class="text-xs text-slate-800 dark:text-[#d1d7db] leading-relaxed">${r.content}</p>
                    </div>

                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button type="button" onclick="toggleBotRule(${r.id})" title="${r.is_active ? 'Pausar Regra' : 'Ativar Regra'}"
                            class="p-2 rounded-xl text-slate-500 hover:text-indigo-500 hover:bg-slate-100 dark:hover:bg-[#111b21] transition-all">
                            <i data-lucide="${r.is_active ? 'toggle-right' : 'toggle-left'}" class="w-5 h-5 ${r.is_active ? 'text-emerald-500' : 'text-slate-400'}"></i>
                        </button>

                        <button type="button" onclick="deleteBotRule(${r.id})" title="Excluir Regra"
                            class="p-2 rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-all">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        } catch (err) {
            console.error('Erro ao carregar regras:', err);
            list.innerHTML = '<div class="p-6 text-center text-xs text-rose-500">Erro ao carregar regras.</div>';
        }
    }

    async function submitNewRule(e) {
        e.preventDefault();
        const titleInput = document.getElementById('new-rule-title');
        const contentInput = document.getElementById('new-rule-content');
        const content = contentInput.value.trim();
        const title = titleInput.value.trim();

        if (!content) return;

        try {
            await fetch('{{ route("portal.bot-rules.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ title, content })
            });

            titleInput.value = '';
            contentInput.value = '';
            loadBotRules();
        } catch (err) {
            console.error('Erro ao salvar regra:', err);
        }
    }

    async function toggleBotRule(ruleId) {
        try {
            await fetch(`/bot-rules/${ruleId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });
            loadBotRules();
        } catch (err) {
            console.error('Erro ao alternar regra:', err);
        }
    }

    async function deleteBotRule(ruleId) {
        if (!confirm('Deseja realmente remover esta instrução do bot?')) return;
        try {
            await fetch(`/bot-rules/${ruleId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });
            loadBotRules();
        } catch (err) {
            console.error('Erro ao excluir regra:', err);
        }
    }

    // Scroll automático para a última mensagem e auto-refresh
    document.addEventListener('DOMContentLoaded', () => {
        const msgContainer = document.getElementById('messages-container');
        if (msgContainer) {
            msgContainer.scrollTop = msgContainer.scrollHeight;
        }

        // Auto-refresh suave da conversa aberta a cada 5 segundos
        setInterval(() => {
            const inputField = document.getElementById('msg-input');
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

                // Atualiza mensagens
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
