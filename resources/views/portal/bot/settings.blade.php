@extends('layouts.app', ['title' => 'Configurações do Robô IA'])

@section('content')
<div class="max-w-6xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-800 border border-indigo-400/30 flex items-center justify-center text-white shadow-lg shadow-indigo-600/25">
                    <i data-lucide="sliders" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="font-display font-extrabold text-2xl md:text-3xl text-slate-900 dark:text-white tracking-tight">
                        Configurações do Robô IA
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Ajuste o ritmo de digitação, delays naturais do WhatsApp, divisão de mensagens e salvaguardas operacionais.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('portal.bot.rules') }}" class="px-4 py-2.5 rounded-2xl bg-slate-900/80 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white font-semibold text-xs transition-all flex items-center gap-2 shadow-sm">
                <i data-lucide="sparkles" class="w-4 h-4 text-emerald-400"></i>
                <span>Ver Regras de Atendimento (80+)</span>
            </a>
        </div>
    </div>

    <!-- Session Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3 shadow-lg shadow-emerald-950/20">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 flex-shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm space-y-1 shadow-lg">
            <div class="font-bold flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-400"></i>
                <span>Corrija os erros abaixo:</span>
            </div>
            <ul class="list-disc list-inside text-xs pl-2 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('portal.bot.settings.update') }}" class="space-y-8">
        @csrf

        <!-- CARD 1: Ritmo de Digitação & Presença no WhatsApp (Sua Regra Principal) -->
        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 md:p-8 shadow-xl space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                            Ritmo de Digitação & Delay WhatsApp
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Evita respostas imediatas de robô, simulando uma pessoa digitando em tempo real.
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 border border-indigo-500/20">
                    Tempo Real
                </span>
            </div>

            <!-- Toggle: Digitando... -->
            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                <div class="space-y-0.5 max-w-xl">
                    <label for="typing_delay_enabled" class="text-sm font-bold text-slate-900 dark:text-white cursor-pointer">
                        Simular Status "Digitando..." no WhatsApp
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Quando ativado, o robô aciona o status verde de "digitando..." no WhatsApp do cliente durante todo o período de espera antes de mandar a resposta.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="typing_delay_enabled" id="typing_delay_enabled" value="1" 
                        class="sr-only peer" {{ ($settings['typing_delay_enabled']->value ?? '1') == '1' ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <!-- Sliders: Tempo Mínimo e Máximo (5s a 30s) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div class="space-y-3 p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="play" class="w-3.5 h-3.5 text-indigo-400"></i>
                            <span>Tempo Mínimo de Espera</span>
                        </label>
                        <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-400 font-mono font-bold text-xs border border-indigo-500/20" id="min_val_display">
                            {{ $settings['typing_delay_min']->value ?? 5 }}s
                        </span>
                    </div>
                    <input type="range" name="typing_delay_min" min="1" max="60" value="{{ $settings['typing_delay_min']->value ?? 5 }}" id="typing_delay_min"
                        oninput="document.getElementById('min_val_display').innerText = this.value + 's'; document.getElementById('min_input').value = this.value"
                        class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                    <div class="flex justify-between items-center text-[10px] text-slate-400">
                        <span>1 segundo</span>
                        <div class="flex items-center gap-1">
                            <span>Exato:</span>
                            <input type="number" id="min_input" value="{{ $settings['typing_delay_min']->value ?? 5 }}" min="1" max="60" 
                                oninput="document.getElementById('typing_delay_min').value = this.value; document.getElementById('min_val_display').innerText = this.value + 's'"
                                class="w-16 px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-white font-mono text-xs text-center outline-none">
                        </div>
                        <span>60 segundos</span>
                    </div>
                </div>

                <div class="space-y-3 p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="square" class="w-3.5 h-3.5 text-indigo-400"></i>
                            <span>Tempo Máximo de Espera</span>
                        </label>
                        <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-400 font-mono font-bold text-xs border border-indigo-500/20" id="max_val_display">
                            {{ $settings['typing_delay_max']->value ?? 30 }}s
                        </span>
                    </div>
                    <input type="range" name="typing_delay_max" min="5" max="120" value="{{ $settings['typing_delay_max']->value ?? 30 }}" id="typing_delay_max"
                        oninput="document.getElementById('max_val_display').innerText = this.value + 's'; document.getElementById('max_input').value = this.value"
                        class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                    <div class="flex justify-between items-center text-[10px] text-slate-400">
                        <span>5 segundos</span>
                        <div class="flex items-center gap-1">
                            <span>Exato:</span>
                            <input type="number" id="max_input" value="{{ $settings['typing_delay_max']->value ?? 30 }}" min="5" max="120" 
                                oninput="document.getElementById('typing_delay_max').value = this.value; document.getElementById('max_val_display').innerText = this.value + 's'"
                                class="w-16 px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-white font-mono text-xs text-center outline-none">
                        </div>
                        <span>120 segundos</span>
                    </div>
                </div>
            </div>

            <!-- Toggle: Dividir Mensagens Longas -->
            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                <div class="space-y-0.5 max-w-xl">
                    <label for="split_long_messages" class="text-sm font-bold text-slate-900 dark:text-white cursor-pointer">
                        Dividir Mensagens Longas em Mensagens Picadas
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Em vez de enviar um texto gigante com cotação e explicações de uma só vez, divide em 2 ou 3 mensagens menores enviadas com 2 a 3 segundos de intervalo.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="split_long_messages" id="split_long_messages" value="1" 
                        class="sr-only peer" {{ ($settings['split_long_messages']->value ?? '1') == '1' ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>
        </div>

        <!-- CARD 2: Intervenção Humana & Salvaguardas -->
        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 md:p-8 shadow-xl space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                        Intervenção Humana & Pausa Inteligente
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Controle o que acontece quando o representante digita manualmente no WhatsApp.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                <div class="space-y-0.5 max-w-xl">
                    <label for="auto_pause_on_human_reply" class="text-sm font-bold text-slate-900 dark:text-white cursor-pointer">
                        Pausar o Robô Automaticamente quando o Representante Digitar
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Se você mandar uma mensagem manual para o cliente no WhatsApp, o robô se cala imediatamente para não conflitar com a sua negociação.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="auto_pause_on_human_reply" id="auto_pause_on_human_reply" value="1" 
                        class="sr-only peer" {{ ($settings['auto_pause_on_human_reply']->value ?? '1') == '1' ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Duração do Silêncio do Robô (Minutos)
                    </label>
                    <div class="relative">
                        <input type="number" name="pause_duration_minutes" value="{{ $settings['pause_duration_minutes']->value ?? 120 }}" min="5" max="1440" required
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                        <span class="absolute right-4 top-2.5 text-xs text-slate-400">minutos (padrão: 120m / 2h)</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Desconto Máximo Autônomo da IA (%)
                    </label>
                    <div class="relative">
                        <input type="number" step="0.5" name="max_autonomous_discount_pct" value="{{ $settings['max_autonomous_discount_pct']->value ?? 5.0 }}" min="0" max="30" required
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                        <span class="absolute right-4 top-2.5 text-xs text-slate-400">% de desconto limite</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: Alertas & Palavras Proibidas -->
        <div class="bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 md:p-8 shadow-xl space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">
                        Alertas & Filtros de Negociação
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Notificações de pedidos de grande porte e restrições de vocabulário.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Notificar Administrador em Cotações Acima de (R$)
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-2.5 text-xs text-slate-400 font-bold">R$</span>
                        <input type="number" step="100" name="large_order_alert_threshold" value="{{ $settings['large_order_alert_threshold']->value ?? 3000.00 }}" min="0" required
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Cotações que superarem este valor alertam o Emmanuel no WhatsApp.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Palavras e Termos Proibidos (separados por vírgula)
                    </label>
                    <input type="text" name="prohibited_words" value="{{ $settings['prohibited_words']->value ?? '' }}" placeholder="Ex: concorrente X, prazo 90 dias, fiado"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">O robô é instruído a nunca pronunciar esses termos.</p>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('portal.bot.rules') }}" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Voltar para Regras de Atendimento</span>
            </a>

            <button type="submit" class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 transition-all flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Salvar Configurações</span>
            </button>
        </div>
    </form>
</div>
@endsection
