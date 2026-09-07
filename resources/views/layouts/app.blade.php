<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'FarmaFlow' }} — Assistente Comercial Inteligente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        .meraki-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.85) 100%);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }
        .meraki-sidebar {
            background: linear-gradient(180deg, #0b0f19 0%, #060911 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }
        .glow-brand {
            box-shadow: 0 0 25px -5px rgba(99, 102, 241, 0.35);
        }
        .glow-emerald {
            box-shadow: 0 0 20px -5px rgba(16, 185, 129, 0.3);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.3); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.5); }
    </style>
</head>
<body class="h-full antialiased selection:bg-indigo-500 selection:text-white bg-slate-950">
    <div class="flex h-screen overflow-hidden">
        <!-- Meraki UI Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0 flex flex-col justify-between meraki-sidebar z-20">
            <div class="flex flex-col h-full">
                <!-- Brand Header -->
                <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-800/80">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 flex items-center justify-center glow-brand border border-indigo-400/30">
                        <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h1 class="font-display font-extrabold text-lg tracking-tight text-white leading-none">FarmaFlow</h1>
                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">V1</span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Assistente Comercial IA</p>
                    </div>
                </div>

                <!-- Navigation Section -->
                <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
                    <!-- Admin Navigation Group -->
                    @if(auth()->user()->isAdmin())
                        <div>
                            <span class="px-3 text-[11px] font-bold tracking-wider text-amber-400 uppercase flex items-center gap-1.5">
                                <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                                <span>Administração</span>
                            </span>
                            <nav class="mt-2 space-y-1">
                                <a href="{{ route('admin.representatives.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('admin.representatives*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="users" class="w-4 h-4 {{ request()->routeIs('admin.representatives*') ? 'text-white' : 'text-amber-400 group-hover:text-white' }}"></i>
                                        <span>Representantes</span>
                                    </div>
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">Admin</span>
                                </a>
                            </nav>
                        </div>
                    @endif

                    <!-- Commercial & Sales Navigation -->
                    <div>
                        <span class="px-3 text-[11px] font-bold tracking-wider text-slate-500 uppercase">
                            {{ auth()->user()->isAdmin() ? 'Visão Consolidada' : 'Minha Carteira' }}
                        </span>
                        <nav class="mt-2 space-y-1">
                            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('portal.dashboard') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                <i data-lucide="layout-grid" class="w-4 h-4 {{ request()->routeIs('portal.dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('portal.inbox') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('portal.inbox*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="message-circle" class="w-4 h-4 {{ request()->routeIs('portal.inbox*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                                    <span>Inbox WhatsApp</span>
                                </div>
                                <span id="sidebar-wa-pill" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700">
                                    WhatsApp
                                </span>
                            </a>

                            <a href="{{ route('portal.crm') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('portal.crm*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                <i data-lucide="users-2" class="w-4 h-4 {{ request()->routeIs('portal.crm*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                                <span>CRM & Carteira</span>
                            </a>

                            <a href="{{ route('portal.catalog') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('portal.catalog*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                <i data-lucide="layers" class="w-4 h-4 {{ request()->routeIs('portal.catalog*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                                <span>Catálogo & Preços</span>
                            </a>

                            <a href="{{ route('portal.sales') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('portal.sales*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                <i data-lucide="shopping-bag" class="w-4 h-4 {{ request()->routeIs('portal.sales*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                                <span>Cotações & Pedidos</span>
                            </a>

                            <a href="{{ route('portal.tasks') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('portal.tasks*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                <i data-lucide="check-square" class="w-4 h-4 {{ request()->routeIs('portal.tasks*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                                <span>Tarefas & Follow-ups</span>
                            </a>
                        </nav>
                    </div>

                    <div>
                        <span class="px-3 text-[11px] font-bold tracking-wider text-slate-500 uppercase">Documentação & IA</span>
                        <nav class="mt-2 space-y-1">
                            <a href="{{ route('portal.docs') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group {{ request()->routeIs('portal.docs*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="book-open" class="w-4 h-4 {{ request()->routeIs('portal.docs*') ? 'text-white' : 'text-indigo-400' }}"></i>
                                    <span>Central de Docs</span>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Manual</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Meraki UI User Profile Card & AI Engine Badge -->
                <div class="p-4 border-t border-slate-800/80 bg-slate-950/60">
                    <!-- AI Badge -->
                    <div class="mb-3 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                            <span class="text-[11px] font-semibold text-slate-300">Google Gemini IA</span>
                        </div>
                        <span class="text-[10px] font-mono text-cyan-400 bg-cyan-500/10 px-1.5 py-0.5 rounded">2.0 Flash</span>
                    </div>

                    <!-- User Account Details -->
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-800 border border-indigo-400/30 flex items-center justify-center font-bold text-xs text-white shadow-md">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-bold text-slate-200 truncate">{{ auth()->user()->name ?? 'Usuário' }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if(auth()->user()->isAdmin())
                                        <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20">Admin</span>
                                    @else
                                        <span class="text-[10px] font-semibold text-slate-400">{{ auth()->user()->representative?->code ?? 'Rep' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="/logout">
                            @csrf
                            <button type="submit" title="Sair do FarmaFlow" class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-xl transition-all">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-full min-w-0 overflow-hidden">
            <!-- Meraki UI Top Navbar -->
            <header class="h-16 flex-shrink-0 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl px-8 flex items-center justify-between z-10">
                <!-- Search Bar -->
                <div class="flex items-center gap-3 w-96">
                    <div class="relative w-full">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" placeholder="Buscar clientes, cotações, remédios..." 
                            class="w-full pl-10 pr-12 py-2 bg-slate-950/60 border border-slate-800/80 rounded-xl text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <kbd class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-mono text-slate-500 bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800">⌘K</kbd>
                    </div>
                </div>

                <!-- Right Navbar Actions -->
                <div class="flex items-center gap-4">
                    <!-- Real Dynamic WhatsApp Status Button -->
                    <button id="whatsapp-header-badge" onclick="openWhatsAppModal()" 
                        class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-950/80 border border-slate-800 hover:border-indigo-500/50 text-xs text-slate-300 transition-all cursor-pointer shadow-sm">
                        <span id="whatsapp-header-dot" class="w-2 h-2 rounded-full bg-slate-500 animate-pulse"></span>
                        <span id="whatsapp-header-text" class="font-medium">Verificando WhatsApp...</span>
                    </button>

                    <!-- Quick Doc Button -->
                    <a href="{{ route('portal.docs') }}" class="p-2 text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-xl transition-all border border-transparent hover:border-indigo-500/20" title="Manual e Documentação">
                        <i data-lucide="help-circle" class="w-5 h-5"></i>
                    </a>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-slate-950 p-8">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between shadow-lg shadow-emerald-950/30">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center justify-between shadow-lg shadow-red-950/30">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-400"></i>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Content Slot -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- WhatsApp Connection & QR Code Modal -->
    <div id="whatsapp-modal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 max-w-lg w-full shadow-2xl space-y-6 relative">
            <button onclick="closeWhatsAppModal()" class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <i data-lucide="qr-code" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-lg text-white">Conexão WhatsApp do Assistente</h3>
                    <p class="text-xs text-slate-400">Pareie o WhatsApp individual do seu assistente comercial.</p>
                </div>
            </div>

            <!-- Status Info Card -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Titular / Representante:</span>
                    <span id="modal-wa-rep-name" class="font-bold text-slate-200">Carregando...</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Status da Instância:</span>
                    <span id="modal-wa-state" class="font-bold font-mono text-amber-400">Verificando...</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Instância Dedicada:</span>
                    <span id="modal-wa-instance" class="font-mono text-indigo-300">comercial</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Servidor Evolution:</span>
                    <span id="modal-wa-server" class="font-mono text-slate-400 text-[11px] truncate max-w-[200px]">http://evolution-api:8080</span>
                </div>
            </div>

            <!-- QR Code Area -->
            <div id="modal-qr-container" class="text-center py-4 space-y-4">
                <div id="modal-qr-box" class="w-56 h-56 mx-auto rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center p-2 relative overflow-hidden">
                    <div id="modal-qr-loading" class="text-xs text-slate-500 flex flex-col items-center gap-2">
                        <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-indigo-400"></i>
                        <span>Carregando QR Code...</span>
                    </div>
                    <img id="modal-qr-img" src="" alt="WhatsApp QR Code" class="w-full h-full object-contain rounded-xl hidden">
                </div>

                <div id="modal-pairing-box" class="hidden">
                    <span class="text-xs text-slate-400 block mb-1">Ou conecte usando o Código de Pareamento:</span>
                    <span id="modal-pairing-code" class="font-mono font-bold text-lg text-emerald-400 bg-slate-950 px-4 py-1.5 rounded-xl border border-slate-800 inline-block tracking-widest">---</span>
                </div>

                <p id="modal-qr-help" class="text-xs text-slate-400 max-w-xs mx-auto">
                    Abra o WhatsApp no celular > Aparelhos Conectados > Conectar um aparelho e aponte a câmera.
                </p>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex gap-3 pt-2">
                <button onclick="fetchWhatsAppQrCode()" class="flex-1 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-all flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/25">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Gerar / Atualizar QR Code</span>
                </button>
                <button id="modal-disconnect-btn" onclick="disconnectWhatsApp()" class="py-3 px-4 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 font-semibold text-xs transition-all hidden flex items-center gap-2">
                    <i data-lucide="power" class="w-4 h-4"></i>
                    <span>Desconectar</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Funções de Gestão de Conexão WhatsApp Real
        async function checkWhatsAppStatus() {
            try {
                const res = await fetch('{{ route("portal.whatsapp.status") }}');
                const data = await res.json();

                const badgeDot = document.getElementById('whatsapp-header-dot');
                const badgeText = document.getElementById('whatsapp-header-text');
                const sidebarPill = document.getElementById('sidebar-wa-pill');
                const modalState = document.getElementById('modal-wa-state');
                const modalInstance = document.getElementById('modal-wa-instance');
                const modalServer = document.getElementById('modal-wa-server');
                const modalRepName = document.getElementById('modal-wa-rep-name');
                const disconnectBtn = document.getElementById('modal-disconnect-btn');

                if (modalRepName) modalRepName.innerText = data.representative_name || '{{ auth()->user()->name }}';
                if (modalInstance) modalInstance.innerText = data.instance || 'comercial';
                if (modalServer) modalServer.innerText = data.server_url || 'http://evolution-api:8080';

                if (data.connected && data.state === 'open') {
                    // CONECTADO REAL
                    badgeDot.className = 'w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399] animate-pulse';
                    badgeText.innerText = 'WhatsApp Conectado';
                    badgeText.className = 'font-semibold text-emerald-300';
                    
                    if (sidebarPill) {
                        sidebarPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
                        sidebarPill.innerText = '● Online';
                    }
                    if (modalState) {
                        modalState.innerText = 'Conectado (Online)';
                        modalState.className = 'font-bold font-mono text-emerald-400';
                    }
                    if (disconnectBtn) disconnectBtn.classList.remove('hidden');

                    const qrBox = document.getElementById('modal-qr-box');
                    if (qrBox) {
                        qrBox.innerHTML = '<div class="text-center p-4"><i data-lucide="check-circle-2" class="w-12 h-12 text-emerald-400 mx-auto mb-2"></i><p class="text-xs font-bold text-white">Instância Ativa & Conectada!</p><p class="text-[11px] text-slate-400 mt-1">Pronto para envio e recebimento de mensagens.</p></div>';
                        lucide.createIcons();
                    }
                } else {
                    // DESCONECTADO OU AGUARDANDO QR CODE
                    badgeDot.className = 'w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_8px_#f43f5e] animate-ping';
                    badgeText.innerText = data.online ? 'WhatsApp Desconectado' : 'Evolution Offline';
                    badgeText.className = 'font-semibold text-rose-300';

                    if (sidebarPill) {
                        sidebarPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 border border-rose-500/30';
                        sidebarPill.innerText = 'Desconectado';
                    }
                    if (modalState) {
                        modalState.innerText = data.state === 'connecting' ? 'Aguardando Leitura do QR Code' : (data.online ? 'Desconectado' : 'Evolution API Offline');
                        modalState.className = 'font-bold font-mono text-rose-400';
                    }
                    if (disconnectBtn) disconnectBtn.classList.add('hidden');
                }
            } catch (err) {
                console.error('Erro ao verificar status do WhatsApp:', err);
                const badgeDot = document.getElementById('whatsapp-header-dot');
                const badgeText = document.getElementById('whatsapp-header-text');
                if (badgeDot && badgeText) {
                    badgeDot.className = 'w-2 h-2 rounded-full bg-rose-500';
                    badgeText.innerText = 'WhatsApp Desconectado';
                    badgeText.className = 'font-medium text-rose-400';
                }
            }
        }

        async function fetchWhatsAppQrCode() {
            const qrLoading = document.getElementById('modal-qr-loading');
            const qrImg = document.getElementById('modal-qr-img');
            const pairingBox = document.getElementById('modal-pairing-box');
            const pairingCode = document.getElementById('modal-pairing-code');

            if (qrLoading) qrLoading.classList.remove('hidden');
            if (qrImg) qrImg.classList.add('hidden');

            try {
                const res = await fetch('{{ route("portal.whatsapp.qrcode") }}');
                const data = await res.json();

                if (data.success && data.base64) {
                    if (qrImg) {
                        qrImg.src = data.base64.startsWith('data:') ? data.base64 : 'data:image/png;base64,' + data.base64;
                        qrImg.classList.remove('hidden');
                    }
                    if (qrLoading) qrLoading.classList.add('hidden');

                    if (data.pairingCode && pairingBox && pairingCode) {
                        pairingCode.innerText = data.pairingCode;
                        pairingBox.classList.remove('hidden');
                    }
                } else {
                    checkWhatsAppStatus();
                }
            } catch (err) {
                console.error('Erro ao buscar QR code:', err);
            }
        }

        async function disconnectWhatsApp() {
            if (!confirm('Deseja desconectar a instância do WhatsApp?')) return;
            try {
                await fetch('{{ route("portal.whatsapp.disconnect") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });
                checkWhatsAppStatus();
            } catch (err) {
                console.error('Erro ao desconectar WhatsApp:', err);
            }
        }

        function openWhatsAppModal() {
            document.getElementById('whatsapp-modal').classList.remove('hidden');
            checkWhatsAppStatus();
            fetchWhatsAppQrCode();
        }

        function closeWhatsAppModal() {
            document.getElementById('whatsapp-modal').classList.add('hidden');
        }

        // Checagem periódica do status real a cada 15 segundos
        document.addEventListener('DOMContentLoaded', () => {
            checkWhatsAppStatus();
            setInterval(checkWhatsAppStatus, 15000);
        });
    </script>
    @stack('scripts')
</body>
</html>
