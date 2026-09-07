<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <div>
                        <span class="px-3 text-[11px] font-bold tracking-wider text-slate-500 uppercase">Gestão & Vendas</span>
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
                                <span class="flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full {{ request()->routeIs('portal.inbox*') ? 'bg-indigo-700/80 text-white' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Live
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
                                <p class="text-[11px] text-slate-400 capitalize">{{ auth()->user()->role ?? 'Representante' }}</p>
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
                    <!-- WhatsApp Status Indicator -->
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-medium">WhatsApp Conectado</span>
                    </div>

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

    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>

