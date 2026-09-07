<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Assistente Comercial Inteligente' }} — Plataforma de Vendas WhatsApp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
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
                        },
                        emerald: {
                            500: '#10b981',
                            600: '#059669',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.6); }
        ::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.4); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.6); }
    </style>
</head>
<body class="h-full antialiased selection:bg-brand-500 selection:text-white">
    <div class="flex h-full min-h-screen">
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0 flex flex-col justify-between border-r border-slate-800 bg-slate-900/95 backdrop-blur-xl">
            <div>
                <!-- Brand Header -->
                <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-800/80">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-400 flex items-center justify-center shadow-lg shadow-brand-500/20">
                        <i data-lucide="bot" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-display font-bold text-base tracking-tight text-white leading-tight">Assistente IA</h1>
                        <span class="text-xs text-brand-400 font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            WhatsApp V1 Online
                        </span>
                    </div>
                </div>

                <!-- Nav Links -->
                <nav class="p-4 space-y-1.5">
                    <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('portal.dashboard') ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('portal.inbox') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('portal.inbox*') ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-3">
                            <i data-lucide="message-square" class="w-4 h-4"></i>
                            <span>Inbox WhatsApp</span>
                        </div>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Live</span>
                    </a>

                    <a href="{{ route('portal.crm') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('portal.crm*') ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>CRM & Carteira</span>
                    </a>

                    <a href="{{ route('portal.catalog') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('portal.catalog*') ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <i data-lucide="package" class="w-4 h-4"></i>
                        <span>Catálogo & Preços</span>
                    </a>

                    <a href="{{ route('portal.sales') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('portal.sales*') ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                        <span>Cotações & Pedidos</span>
                    </a>

                    <a href="{{ route('portal.tasks') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('portal.tasks*') ? 'bg-brand-600/20 text-brand-300 border border-brand-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Tarefas & Follow-ups</span>
                    </a>

                    <div class="pt-4 mt-4 border-t border-slate-800/80">
                        <span class="px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Ajuda e Treinamento</span>
                        <a href="{{ route('portal.docs') }}" class="mt-2 flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('portal.docs*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                            <div class="flex items-center gap-3">
                                <i data-lucide="book-open" class="w-4 h-4 text-indigo-400"></i>
                                <span>Central de Docs</span>
                            </div>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase">Manual</span>
                        </a>
                    </div>
                </nav>
            </div>

            <!-- User Footer -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-xs text-brand-400">
                            {{ substr(auth()->user()->name ?? 'U', 0, 2) }}
                        </div>
                        <div class="truncate">
                            <p class="text-sm font-medium text-slate-200 truncate">{{ auth()->user()->name ?? 'Usuário' }}</p>
                            <p class="text-xs text-slate-500 capitalize">{{ auth()->user()->role ?? 'Representante' }}</p>
                        </div>
                    </div>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" title="Sair do sistema" class="p-2 text-slate-400 hover:text-red-400 hover:bg-slate-800/80 rounded-lg transition-colors">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col h-full overflow-y-auto bg-slate-950">
            <!-- Top Alert / Flash Messages -->
            @if(session('success'))
                <div class="mx-8 mt-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-8 mt-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-400"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Content Slot -->
            <div class="p-8">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>
