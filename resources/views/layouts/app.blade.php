<!DOCTYPE html>
<html lang="pt-BR" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'FarmaFlow' }} — Assistente Comercial Inteligente</title>
    
    <!-- HeroUI & Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS with Dark Mode -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        // HeroUI Theme Initialization (Instant to prevent FOUC)
        (function() {
            const savedTheme = localStorage.getItem('farmaflow_theme');
            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();

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

    <!-- HeroUI Tokens & Styles -->
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }

        :root {
            --heroui-bg: #f8fafc;
            --heroui-surface: #ffffff;
            --heroui-surface-subtle: #f1f5f9;
            --heroui-border: rgba(226, 232, 240, 0.9);
            --heroui-text-primary: #0f172a;
            --heroui-text-secondary: #64748b;
            --heroui-sidebar: #ffffff;
            --heroui-header: rgba(255, 255, 255, 0.85);
        }

        html.dark {
            --heroui-bg: #030712;
            --heroui-surface: #0f172a;
            --heroui-surface-subtle: #090d16;
            --heroui-border: rgba(255, 255, 255, 0.08);
            --heroui-text-primary: #ffffff;
            --heroui-text-secondary: #94a3b8;
            --heroui-sidebar: #060911;
            --heroui-header: rgba(11, 15, 25, 0.85);
        }

        .heroui-card, .meraki-card {
            background-color: var(--heroui-surface);
            border: 1px solid var(--heroui-border);
            backdrop-filter: blur(16px);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .heroui-sidebar, .meraki-sidebar {
            background-color: var(--heroui-sidebar);
            border-right: 1px solid var(--heroui-border);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .heroui-header {
            background-color: var(--heroui-header);
            border-bottom: 1px solid var(--heroui-border);
            backdrop-filter: blur(16px);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .glow-brand {
            box-shadow: 0 0 25px -5px rgba(99, 102, 241, 0.35);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.25); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.5); }
    </style>
</head>
<body class="h-full antialiased selection:bg-indigo-500 selection:text-white bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
    <div class="flex h-screen overflow-hidden">
        <!-- HeroUI Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0 flex flex-col justify-between heroui-sidebar z-20">
            <div class="flex flex-col h-full">
                <!-- Brand Header -->
                <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-200/80 dark:border-slate-800/80">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 flex items-center justify-center glow-brand border border-indigo-400/30">
                        <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h1 class="font-display font-extrabold text-lg tracking-tight text-slate-900 dark:text-white leading-none">FarmaFlow</h1>
                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 dark:border-indigo-500/30">V1</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">Assistente Comercial IA</p>
                    </div>
                </div>

                <!-- Navigation Section -->
                <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">

                    <!-- Commercial & Sales Navigation -->
                    <div>
                        <span class="px-3 text-[11px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase">
                            {{ auth()->user()->isAdmin() ? 'Visão Consolidada' : 'Minha Carteira' }}
                        </span>
                        <nav class="mt-2 space-y-1">
                            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.dashboard') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <i data-lucide="layout-grid" class="w-4 h-4 {{ request()->routeIs('portal.dashboard') ? 'text-white' : 'text-slate-400 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white' }}"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('portal.inbox') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.inbox*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="message-circle" class="w-4 h-4 {{ request()->routeIs('portal.inbox*') ? 'text-white' : 'text-slate-400 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white' }}"></i>
                                    <span>Inbox WhatsApp</span>
                                </div>
                                <span id="sidebar-wa-pill" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                    WhatsApp
                                </span>
                            </a>

                            <a href="{{ route('portal.crm') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.crm*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <i data-lucide="users-2" class="w-4 h-4 {{ request()->routeIs('portal.crm*') ? 'text-white' : 'text-slate-400 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white' }}"></i>
                                <span>CRM & Carteira</span>
                            </a>

                            <a href="{{ route('portal.catalog') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.catalog*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <i data-lucide="layers" class="w-4 h-4 {{ request()->routeIs('portal.catalog*') ? 'text-white' : 'text-slate-400 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white' }}"></i>
                                <span>Catálogo & Preços</span>
                            </a>

                            <a href="{{ route('portal.sales') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.sales*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <i data-lucide="shopping-bag" class="w-4 h-4 {{ request()->routeIs('portal.sales*') ? 'text-white' : 'text-slate-400 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white' }}"></i>
                                <span>Cotações & Pedidos</span>
                            </a>

                            <a href="{{ route('portal.tasks') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.tasks*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <i data-lucide="check-square" class="w-4 h-4 {{ request()->routeIs('portal.tasks*') ? 'text-white' : 'text-slate-400 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white' }}"></i>
                                <span>Tarefas & Follow-ups</span>
                            </a>
                        </nav>
                    </div>

                    <div>
                        <span class="px-3 text-[11px] font-bold tracking-wider text-slate-400 dark:text-slate-500 uppercase">Inteligência Artificial & Robô</span>
                        <nav class="mt-2 space-y-1">
                            <a href="{{ route('portal.bot.settings') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.bot.settings*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="sliders" class="w-4 h-4 {{ request()->routeIs('portal.bot.settings*') ? 'text-white' : 'text-indigo-500 dark:text-indigo-400' }}"></i>
                                    <span>Configurações do Robô</span>
                                </div>
                                <span class="px-1.5 py-0.5 text-[10px] font-mono rounded-md bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">Delay & IA</span>
                            </a>

                            <a href="{{ route('portal.bot.rules') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.bot.rules*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="sparkles" class="w-4 h-4 {{ request()->routeIs('portal.bot.rules*') ? 'text-white' : 'text-emerald-500 dark:text-emerald-400' }}"></i>
                                    <span>Regras de Atendimento</span>
                                </div>
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">80+</span>
                            </a>

                            <a href="{{ route('portal.docs') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-all group {{ request()->routeIs('portal.docs*') ? 'bg-indigo-600 text-white font-semibold shadow-lg shadow-indigo-600/25' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/50' }}">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="book-open" class="w-4 h-4 {{ request()->routeIs('portal.docs*') ? 'text-white' : 'text-slate-400' }}"></i>
                                    <span>Central de Docs</span>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-500/10 text-slate-400 border border-slate-500/20">Manual</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- HeroUI User Profile Card & AI Engine Badge -->
                <div class="p-4 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-100/50 dark:bg-slate-950/60">
                    <!-- AI Badge -->
                    <div class="mb-3 px-3 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-cyan-500 dark:bg-cyan-400 animate-ping"></span>
                            <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300">Google Gemini IA</span>
                        </div>
                        <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 bg-cyan-500/10 px-1.5 py-0.5 rounded-lg">2.0 Flash</span>
                    </div>

                    <!-- User Account Details -->
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-800 border border-indigo-400/30 flex items-center justify-center font-bold text-xs text-white shadow-md">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ auth()->user()->name ?? 'Usuário' }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if(auth()->user()->isAdmin())
                                        <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-1.5 py-0.2 rounded border border-amber-500/20">Admin</span>
                                    @else
                                        <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ auth()->user()->representative?->code ?? 'Rep' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="/logout">
                            @csrf
                            <button type="submit" title="Sair do FarmaFlow" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-all">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-full min-w-0 overflow-hidden">
            <!-- HeroUI Top Navbar -->
            <header class="h-16 flex-shrink-0 heroui-header px-8 flex items-center justify-between z-10">
                <!-- Search Bar -->
                <div class="flex items-center gap-3 w-96">
                    <div class="relative w-full">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" placeholder="Buscar clientes, cotações, remédios..." 
                            class="w-full pl-10 pr-12 py-2 bg-slate-100 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 rounded-2xl text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <kbd class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-mono text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded-lg border border-slate-200 dark:border-slate-800">⌘K</kbd>
                    </div>
                </div>

                <!-- Right Navbar Actions -->
                <div class="flex items-center gap-3">
                    <!-- HeroUI Theme Toggle Switch (Dark / Light) -->
                    <button id="theme-toggle-btn" onclick="toggleTheme()" 
                        class="px-3 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 hover:border-indigo-500/40 transition-all shadow-sm flex items-center gap-2 cursor-pointer"
                        title="Alternar Tema Claro / Escuro">
                        <span id="theme-icon-container" class="flex items-center">
                            <i id="theme-icon-moon" data-lucide="moon" class="w-4 h-4 hidden dark:block text-indigo-400"></i>
                            <i id="theme-icon-sun" data-lucide="sun" class="w-4 h-4 block dark:hidden text-amber-500"></i>
                        </span>
                        <span class="text-xs font-semibold select-none" id="theme-text-label"></span>
                    </button>

                    <!-- Real Dynamic WhatsApp Status Button -->
                    <button id="whatsapp-header-badge" onclick="openWhatsAppModal()" 
                        class="flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-white dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 hover:border-indigo-500/50 text-xs text-slate-700 dark:text-slate-300 transition-all cursor-pointer shadow-sm">
                        <span id="whatsapp-header-dot" class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500 animate-pulse"></span>
                        <span id="whatsapp-header-text" class="font-medium">Verificando WhatsApp...</span>
                    </button>

                    <!-- Quick Doc Button -->
                    <a href="{{ route('portal.docs') }}" class="p-2.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-500/10 rounded-2xl transition-all border border-slate-200 dark:border-slate-800 hover:border-indigo-500/30 shadow-sm" title="Manual e Documentação">
                        <i data-lucide="help-circle" class="w-4 h-4"></i>
                    </a>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-950 p-8 transition-colors duration-200">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm flex items-center justify-between shadow-lg shadow-emerald-500/5">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500 dark:text-emerald-400"></i>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-sm flex items-center justify-between shadow-lg shadow-rose-500/5">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 dark:text-rose-400"></i>
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
    <div id="whatsapp-modal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 max-w-lg w-full shadow-2xl space-y-6 relative transition-all">
            <button onclick="closeWhatsAppModal()" class="absolute top-6 right-6 p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="qr-code" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Conexão WhatsApp do Assistente</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pareie o WhatsApp individual do seu assistente comercial.</p>
                </div>
            </div>

            <!-- Status Info Card -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-2.5 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Titular & Administrador:</span>
                    <span id="modal-wa-rep-name" class="font-bold text-slate-800 dark:text-slate-200">Emmanuel Marcarini</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Telefone Admin:</span>
                    <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">55 28 99943-9677</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">WhatsApp do Atendente (Bot):</span>
                    <span id="modal-wa-atendente-phone" class="font-mono font-bold text-emerald-600 dark:text-emerald-400">55 28 99915-8412</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Status da Instância:</span>
                    <span id="modal-wa-state" class="font-bold font-mono text-amber-600 dark:text-amber-400">Verificando...</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Instância Evolution API:</span>
                    <span id="modal-wa-instance" class="font-mono text-indigo-600 dark:text-indigo-300 font-semibold">farmaflow</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Servidor Evolution:</span>
                    <span id="modal-wa-server" class="font-mono text-slate-500 dark:text-slate-400 text-[11px] truncate max-w-[200px]">http://evolution-api:8080</span>
                </div>
            </div>

            <!-- QR Code Area -->
            <div id="modal-qr-container" class="text-center py-4 space-y-4">
                <div id="modal-qr-box" class="w-56 h-56 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center p-2 relative overflow-hidden">
                    <div id="modal-qr-loading" class="text-xs text-slate-500 flex flex-col items-center gap-2">
                        <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-indigo-500"></i>
                        <span>Carregando QR Code...</span>
                    </div>
                    <img id="modal-qr-img" src="" alt="WhatsApp QR Code" class="w-full h-full object-contain rounded-xl hidden">
                </div>

                <div id="modal-pairing-box" class="hidden">
                    <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Ou conecte usando o Código de Pareamento:</span>
                    <span id="modal-pairing-code" class="font-mono font-bold text-lg text-emerald-600 dark:text-emerald-400 bg-slate-100 dark:bg-slate-950 px-4 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 inline-block tracking-widest">---</span>
                </div>

                <p id="modal-qr-help" class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                    Abra o WhatsApp no celular > Aparelhos Conectados > Conectar um aparelho e aponte a câmera.
                </p>
            </div>

            <!-- Orphan Instances Cleanup (Optional) -->
            <div id="modal-orphan-instances" class="hidden"></div>

            <!-- Modal Action Buttons -->
            <div class="flex gap-3 pt-2">
                <button onclick="fetchWhatsAppQrCode()" class="flex-1 py-3 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-semibold text-xs transition-all flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/25">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Gerar / Atualizar QR Code</span>
                </button>
                <button id="modal-disconnect-btn" onclick="disconnectWhatsApp()" class="py-3 px-4 rounded-2xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 font-semibold text-xs transition-all hidden flex items-center gap-2">
                    <i data-lucide="power" class="w-4 h-4"></i>
                    <span>Desconectar</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // HeroUI Theme Management
        function updateThemeUI() {
            const isDark = document.documentElement.classList.contains('dark');
            const themeLabel = document.getElementById('theme-text-label');
            if (themeLabel) {
                themeLabel.innerText = isDark ? 'Modo Escuro' : 'Modo Claro';
            }
            lucide.createIcons();
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('farmaflow_theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('farmaflow_theme', 'dark');
            }
            updateThemeUI();
        }

        // Funções de Gestão de Conexão WhatsApp da Instância Única FarmaFlow
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
                const modalPhone = document.getElementById('modal-wa-atendente-phone');
                const disconnectBtn = document.getElementById('modal-disconnect-btn');

                if (modalRepName) modalRepName.innerText = data.representative_name || 'Emmanuel Marcarini';
                if (modalPhone && data.atendente_phone) modalPhone.innerText = data.atendente_phone;
                if (modalInstance) modalInstance.innerText = data.instance || 'farmaflow';
                if (modalServer) modalServer.innerText = data.server_url || 'http://evolution-api:8080';

                if (data.connected && data.state === 'open') {
                    // CONECTADO REAL
                    badgeDot.className = 'w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399] animate-pulse';
                    badgeText.innerText = 'WhatsApp Conectado';
                    badgeText.className = 'font-semibold text-emerald-600 dark:text-emerald-300';
                    
                    if (sidebarPill) {
                        sidebarPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30';
                        sidebarPill.innerText = '● Online';
                    }
                    if (modalState) {
                        modalState.innerText = 'Conectado (Online)';
                        modalState.className = 'font-bold font-mono text-emerald-600 dark:text-emerald-400';
                    }
                    if (disconnectBtn) disconnectBtn.classList.remove('hidden');

                    const qrBox = document.getElementById('modal-qr-box');
                    if (qrBox) {
                        qrBox.innerHTML = '<div class="text-center p-4"><i data-lucide="check-circle-2" class="w-12 h-12 text-emerald-500 dark:text-emerald-400 mx-auto mb-2"></i><p class="text-xs font-bold text-slate-800 dark:text-white">Instância Ativa & Conectada!</p><p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Pronto para envio e recebimento de mensagens.</p></div>';
                        lucide.createIcons();
                    }
                } else {
                    // DESCONECTADO OU AGUARDANDO QR CODE
                    badgeDot.className = 'w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_8px_#f43f5e] animate-ping';
                    badgeText.innerText = data.online ? 'WhatsApp Desconectado' : 'Evolution Offline';
                    badgeText.className = 'font-semibold text-rose-600 dark:text-rose-400';

                    if (sidebarPill) {
                        sidebarPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 dark:border-rose-500/30';
                        sidebarPill.innerText = 'Desconectado';
                    }
                    if (modalState) {
                        modalState.innerText = data.state === 'connecting' ? 'Aguardando Leitura do QR Code' : (data.online ? 'Desconectado' : 'Evolution API Offline');
                        modalState.className = 'font-bold font-mono text-rose-600 dark:text-rose-400';
                    }
                    if (disconnectBtn) disconnectBtn.classList.add('hidden');
                }

                // Renderiza instâncias antigas/órfãs detectadas na Evolution API para limpeza com 1 clique
                const orphanBox = document.getElementById('modal-orphan-instances');
                if (orphanBox && data.live_instances) {
                    const currentInst = data.instance || 'farmaflow';
                    const orphans = data.live_instances.filter(i => {
                        const name = is_array(i) ? (i.name ?? (i.instance?.instanceName ?? null)) : null;
                        return name && name !== currentInst;
                    });

                    if (orphans.length > 0) {
                        orphanBox.innerHTML = '<div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-xs space-y-1.5"><div class="font-bold text-amber-600 dark:text-amber-400">Instâncias antigas encontradas no servidor (limpeza recomendada):</div>' +
                            orphans.map(o => {
                                const oName = o.name || o.instance?.instanceName;
                                return `<div class="flex items-center justify-between font-mono text-[11px] text-slate-700 dark:text-slate-300"><span>${oName}</span><button onclick="deleteInstanceFromEvolution('${oName}')" class="px-2 py-0.5 rounded bg-rose-500/20 hover:bg-rose-500/30 text-rose-600 dark:text-rose-400 font-sans font-bold text-[10px]">Excluir</button></div>`;
                            }).join('') + '</div>';
                        orphanBox.classList.remove('hidden');
                    } else {
                        orphanBox.classList.add('hidden');
                    }
                }

                return data;
            } catch (err) {
                console.error('Erro ao verificar status do WhatsApp:', err);
                const badgeDot = document.getElementById('whatsapp-header-dot');
                const badgeText = document.getElementById('whatsapp-header-text');
                if (badgeDot && badgeText) {
                    badgeDot.className = 'w-2 h-2 rounded-full bg-rose-500';
                    badgeText.innerText = 'WhatsApp Desconectado';
                    badgeText.className = 'font-medium text-rose-500';
                }
                return null;
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
            if (!confirm('Deseja desconectar o WhatsApp da FarmaFlow?')) return;
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

        async function deleteInstanceFromEvolution(instanceName) {
            if (!confirm(`Deseja remover a instância antiga '${instanceName}' da Evolution API?`)) return;
            try {
                await fetch('{{ route("portal.whatsapp.delete-instance") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ instance: instanceName })
                });
                checkWhatsAppStatus();
            } catch (err) {
                console.error('Erro ao deletar instância:', err);
            }
        }

        async function openWhatsAppModal() {
            document.getElementById('whatsapp-modal').classList.remove('hidden');
            const data = await checkWhatsAppStatus();
            if (!data || !data.connected || data.state !== 'open') {
                fetchWhatsAppQrCode();
            }
        }

        function closeWhatsAppModal() {
            document.getElementById('whatsapp-modal').classList.add('hidden');
        }

        // Checagem periódica do status real a cada 15 segundos
        document.addEventListener('DOMContentLoaded', () => {
            updateThemeUI();
            checkWhatsAppStatus();
            setInterval(checkWhatsAppStatus, 15000);
        });
    </script>
    @stack('scripts')
</body>
</html>
