@extends('layouts.app', ['title' => 'Central de Documentação & Treinamento'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Meraki UI Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-950/60 via-slate-900/90 to-purple-950/50 border border-indigo-500/20 p-8 md:p-10 shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-semibold mb-3">
                    <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                    <span>Base de Conhecimento Oficial</span>
                </div>
                <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white tracking-tight">Manual Completo do Representante</h2>
                <p class="text-sm text-slate-400 mt-2 max-w-2xl leading-relaxed">
                    Consulte regras de negócio, comandos em linguagem natural para o WhatsApp, catálogo de preços determinísticos e políticas de segurança.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('portal.inbox') }}" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white text-sm font-semibold transition-all shadow-lg shadow-indigo-600/25 flex items-center gap-2">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                    <span>Abrir Inbox WhatsApp</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Layout: Sidebar & Content -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Topics Sidebar Navigation -->
        <div class="lg:col-span-4 bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-5 shadow-xl sticky top-8">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-3 py-2 flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-indigo-400"></i>
                <span>Índice de Tópicos</span>
            </h3>
            <div class="mt-3 space-y-1.5">
                @foreach($topics as $slug => $topic)
                    <a href="{{ route('portal.docs', ['topic' => $slug]) }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl text-sm font-medium transition-all {{ $selectedSlug === $slug ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50' }}">
                        <div class="flex items-center gap-3 truncate">
                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $selectedSlug === $slug ? 'bg-indigo-400 ring-4 ring-indigo-400/20' : 'bg-slate-700' }}"></span>
                            <span class="truncate">{{ $topic['title'] }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 opacity-50 flex-shrink-0"></i>
                    </a>
                @endforeach
            </div>

            <div class="mt-6 pt-5 border-t border-slate-800/80 px-2">
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-400 space-y-1.5">
                    <p class="font-bold text-slate-200 flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                        <span>Auditoria & Segurança</span>
                    </p>
                    <p class="leading-relaxed">Todas as interações do assistente são auditadas e validadas por travas de fechamento.</p>
                </div>
            </div>
        </div>

        <!-- Topic Detail Content -->
        <div class="lg:col-span-8 space-y-6">
            <article class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-8 md:p-10 shadow-xl">
                <!-- Topic Header -->
                <div class="pb-6 border-b border-slate-800/80">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 uppercase tracking-wider">
                            {{ $activeTopic['category'] }}
                        </span>
                    </div>
                    <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white">{{ $activeTopic['title'] }}</h2>
                    <p class="text-sm text-slate-400 mt-2 leading-relaxed">{{ $activeTopic['summary'] }}</p>
                </div>

                <!-- Markdown Formatted Content -->
                <div class="pt-8 prose prose-invert max-w-none text-slate-300 space-y-6 text-sm leading-relaxed">
                    @php
                        $parsed = Illuminate\Support\Str::markdown($activeTopic['content']);
                    @endphp
                    {!! $parsed !!}
                </div>
            </article>

            <!-- Navigation Footer between Docs -->
            <div class="flex items-center justify-between p-5 bg-slate-900/60 border border-slate-800 rounded-3xl">
                <div class="text-xs text-slate-500">
                    Documentação FarmaFlow • Versão 1.0 Oficial
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('portal.dashboard') }}" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
                        Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
