@extends('layouts.app', ['title' => 'Central de Documentação & Treinamento'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-900/50 via-slate-900/80 to-purple-950/40 border border-indigo-500/20 p-8 shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-semibold mb-3">
                    <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                    <span>Base de Conhecimento Oficial</span>
                </div>
                <h2 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight">Manual Completo do Representante</h2>
                <p class="text-sm text-slate-400 mt-1 max-w-2xl">
                    Consulte todas as funcionalidades, regras de negócio, comandos em linguagem natural e procedimentos de segurança do seu Assistente Comercial Inteligente.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('portal.inbox') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                    <span>Abrir Inbox WhatsApp</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Layout: Sidebar & Content -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Topics Sidebar Navigation -->
        <div class="lg:col-span-4 bg-slate-900/70 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-4 shadow-lg sticky top-8">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-3 py-2">Índice de Tópicos</h3>
            <div class="mt-2 space-y-1">
                @foreach($topics as $slug => $topic)
                    <a href="{{ route('portal.docs', ['topic' => $slug]) }}"
                        class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium transition-all {{ $selectedSlug === $slug ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $selectedSlug === $slug ? 'bg-indigo-400' : 'bg-slate-600' }}"></span>
                            <span class="truncate">{{ $topic['title'] }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 opacity-50"></i>
                    </a>
                @endforeach
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800 px-3">
                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-400 space-y-1">
                    <p class="font-semibold text-slate-300 flex items-center gap-1.5">
                        <i data-lucide="help-circle" class="w-4 h-4 text-indigo-400"></i>
                        <span>Dúvidas Técnicas?</span>
                    </p>
                    <p>O robô é monitorado com logs de auditoria e validação determinística de fechamento.</p>
                </div>
            </div>
        </div>

        <!-- Topic Detail Content -->
        <div class="lg:col-span-8 space-y-6">
            <article class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-8 shadow-xl">
                <!-- Topic Header -->
                <div class="pb-6 border-b border-slate-800/80">
                    <div class="flex items-center gap-2 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">
                        <span class="px-2.5 py-0.5 rounded-md bg-indigo-500/10 border border-indigo-500/20">{{ $activeTopic['category'] }}</span>
                    </div>
                    <h2 class="font-display text-2xl font-bold text-white">{{ $activeTopic['title'] }}</h2>
                    <p class="text-sm text-slate-400 mt-2">{{ $activeTopic['summary'] }}</p>
                </div>

                <!-- Markdown Formatted Content -->
                <div class="pt-6 prose prose-invert max-w-none text-slate-300 space-y-6 text-sm leading-relaxed">
                    @php
                        // Converte markdown simples para visual rico
                        $parsed = Illuminate\Support\Str::markdown($activeTopic['content']);
                    @endphp
                    {!! $parsed !!}
                </div>
            </article>

            <!-- Navigation Footer between Docs -->
            <div class="flex items-center justify-between p-4 bg-slate-900/40 border border-slate-800 rounded-2xl">
                <div class="text-xs text-slate-500">
                    Documentação atualizada • V1 Estável
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('portal.dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
                        Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
