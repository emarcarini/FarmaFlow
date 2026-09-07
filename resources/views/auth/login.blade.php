<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — FarmaFlow Assistente Comercial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
        .meraki-auth-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.75) 0%, rgba(15, 23, 42, 0.95) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 30px -5px rgba(99, 102, 241, 0.15);
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-6 bg-slate-950 relative overflow-hidden">
    <!-- Ambient Meraki UI Glow Lights -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-cyan-600/15 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 shadow-xl shadow-indigo-500/25 mb-4 border border-indigo-400/30">
                <i data-lucide="sparkles" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="font-display text-3xl font-extrabold text-white tracking-tight">FarmaFlow</h1>
            <p class="text-sm text-slate-400 mt-1.5 font-medium">Assistente Comercial Inteligente • WhatsApp & IA</p>
        </div>

        <!-- Meraki UI Card -->
        <div class="meraki-auth-card rounded-3xl p-8">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-400 flex-shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="/login" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">E-mail de Acesso</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="email" name="email" id="email" value="{{ old('email', 'carlos@comercial.com.br') }}" required autofocus
                            placeholder="seu.email@empresa.com.br"
                            class="w-full pl-10 pr-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Senha</label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="password" name="password" id="password" value="senha123" required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-10 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                            <i data-lucide="eye" id="eye-icon" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer select-none">
                        <input type="checkbox" name="remember" checked class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                        <span>Manter conectado</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all text-sm flex items-center justify-center gap-2">
                    <span>Entrar no FarmaFlow</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <!-- Quick Demo Credential Selectors -->
            <div class="mt-8 pt-6 border-t border-slate-800/80">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 text-center mb-3">
                    Acesso Rápido de Demonstração
                </p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillCreds('carlos@comercial.com.br', 'senha123')"
                        class="p-2.5 rounded-xl bg-slate-950/60 hover:bg-indigo-600/20 border border-slate-800 hover:border-indigo-500/40 text-left transition-all">
                        <p class="font-bold text-slate-200">Carlos Silva</p>
                        <p class="text-[10px] text-indigo-400">Representante</p>
                    </button>
                    <button type="button" onclick="fillCreds('admin@comercial.com.br', 'senha123')"
                        class="p-2.5 rounded-xl bg-slate-950/60 hover:bg-indigo-600/20 border border-slate-800 hover:border-indigo-500/40 text-left transition-all">
                        <p class="font-bold text-slate-200">Administrador</p>
                        <p class="text-[10px] text-cyan-400">Gestão Geral</p>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function fillCreds(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }

        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>

