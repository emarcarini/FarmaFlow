@extends('layouts.app', ['title' => $activeTopic['title'] . ' — Docs FarmaFlow'])

@section('content')
<!-- Catppuccin Mocha Theme Wrapper -->
<div class="max-w-[90rem] mx-auto text-[#cdd6f4]">
    <!-- VitePress Catppuccin Top Header / Breadcrumb Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 mb-8 border-b border-[#313244]/80">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#cba6f7]/15 border border-[#cba6f7]/30 flex items-center justify-center text-[#cba6f7]">
                <i data-lucide="book-marked" class="w-4 h-4"></i>
            </div>
            <div>
                <div class="flex items-center gap-2 text-xs text-[#a6adc8]">
                    <span>Docs</span>
                    <span>/</span>
                    <span class="text-[#89b4fa] font-medium">{{ $activeTopic['category'] }}</span>
                    <span>/</span>
                    <span class="text-[#cdd6f4] font-semibold">{{ $activeTopic['title'] }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Catppuccin Theme Flavor Badge -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#181825] border border-[#313244] text-xs text-[#bac2de] shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-[#cba6f7] shadow-[0_0_8px_#cba6f7]"></span>
                <span class="font-medium font-mono text-[11px]">Catppuccin Mocha</span>
            </div>

            <a href="{{ route('portal.inbox') }}" class="px-3.5 py-1.5 rounded-xl bg-[#313244] hover:bg-[#45475a] text-[#cdd6f4] text-xs font-semibold transition-all flex items-center gap-1.5 border border-[#45475a]">
                <i data-lucide="message-square" class="w-3.5 h-3.5 text-[#a6e3a1]"></i>
                <span>Testar no WhatsApp</span>
            </a>
        </div>
    </div>

    <!-- VitePress 3-Column Layout: Sidebar Nav (3 cols) | Content (6-7 cols) | On This Page TOC (2-3 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: VitePress Sidebar Navigation -->
        <aside class="lg:col-span-3 bg-[#181825]/90 backdrop-blur-xl border border-[#313244] rounded-3xl p-5 shadow-xl sticky top-8 max-h-[calc(100vh-8rem)] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#313244]">
                <span class="text-xs font-bold uppercase tracking-wider text-[#a6adc8] flex items-center gap-2">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-[#89b4fa]"></i>
                    <span>Documentação Oficial</span>
                </span>
                <span class="text-[10px] font-mono text-[#89b4fa] bg-[#89b4fa]/15 px-2 py-0.5 rounded-full border border-[#89b4fa]/30">v1.0</span>
            </div>

            <nav class="space-y-6 text-xs">
                @foreach($groupedTopics as $category => $categoryTopics)
                    <div>
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#6c7086] px-2 mb-2 font-mono">
                            {{ $category }}
                        </h4>
                        <div class="space-y-1">
                            @foreach($categoryTopics as $slug => $topic)
                                <a href="{{ route('portal.docs', ['topic' => $slug]) }}"
                                    class="group flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition-all {{ $selectedSlug === $slug ? 'bg-[#313244] text-[#cba6f7] font-bold border border-[#cba6f7]/30 shadow-sm' : 'text-[#a6adc8] hover:text-[#cdd6f4] hover:bg-[#1e1e2e]' }}">
                                    <div class="flex items-center gap-2.5 truncate">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $selectedSlug === $slug ? 'bg-[#cba6f7] shadow-[0_0_6px_#cba6f7]' : 'bg-[#45475a] group-hover:bg-[#89b4fa]' }}"></span>
                                        <span class="truncate">{{ $topic['title'] }}</span>
                                    </div>
                                    @if(isset($topic['badge']))
                                        <span class="text-[9px] font-mono px-1.5 py-0.5 rounded {{ $selectedSlug === $slug ? 'bg-[#cba6f7]/20 text-[#cba6f7]' : 'bg-[#313244] text-[#6c7086]' }}">
                                            {{ $topic['badge'] }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </aside>

        <!-- Center: Main Documentation Article with Catppuccin Styling -->
        <main class="lg:col-span-6 xl:col-span-7 space-y-8">
            <article class="bg-[#181825]/90 backdrop-blur-xl border border-[#313244] rounded-3xl p-8 md:p-12 shadow-2xl relative">
                <!-- Topic Top Metadata -->
                <div class="pb-6 mb-8 border-b border-[#313244]/80 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-[#89b4fa]/15 text-[#89b4fa] border border-[#89b4fa]/30 font-mono">
                            {{ $activeTopic['category'] }}
                        </span>
                        @if(isset($activeTopic['badge']))
                            <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-[#cba6f7]/15 text-[#cba6f7] border border-[#cba6f7]/30 font-mono">
                                {{ $activeTopic['badge'] }}
                            </span>
                        @endif
                    </div>
                    <span class="text-xs text-[#a6adc8] flex items-center gap-1.5 font-mono">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-[#a6adc8]"></i>
                        Atualizado em tempo real
                    </span>
                </div>

                <!-- Markdown Formatted Content Body -->
                <div id="doc-content" class="catppuccin-prose text-[#cdd6f4] text-sm leading-relaxed space-y-6">
                    {!! $activeTopic['html_content'] !!}
                </div>
            </article>

            <!-- Bottom Navigation: Prev & Next Topic Cards (VitePress Standard) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($prevTopic)
                    <a href="{{ route('portal.docs', ['topic' => $prevTopic['slug']]) }}"
                        class="group p-5 rounded-2xl bg-[#181825] border border-[#313244] hover:border-[#89b4fa]/50 transition-all shadow-lg flex flex-col justify-between">
                        <span class="text-[11px] font-mono text-[#a6adc8] flex items-center gap-1">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-1 transition-transform"></i>
                            <span>Página Anterior</span>
                        </span>
                        <span class="text-sm font-bold text-[#cdd6f4] group-hover:text-[#89b4fa] mt-1 transition-colors">
                            {{ $prevTopic['title'] }}
                        </span>
                    </a>
                @else
                    <div></div>
                @endif

                @if($nextTopic)
                    <a href="{{ route('portal.docs', ['topic' => $nextTopic['slug']]) }}"
                        class="group p-5 rounded-2xl bg-[#181825] border border-[#313244] hover:border-[#cba6f7]/50 transition-all shadow-lg flex flex-col justify-between text-right">
                        <span class="text-[11px] font-mono text-[#a6adc8] flex items-center justify-end gap-1">
                            <span>Próxima Página</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <span class="text-sm font-bold text-[#cdd6f4] group-hover:text-[#cba6f7] mt-1 transition-colors">
                            {{ $nextTopic['title'] }}
                        </span>
                    </a>
                @endif
            </div>
        </main>

        <!-- Right: "On this page" Table of Contents (VitePress Standard) -->
        <aside class="hidden xl:block lg:col-span-3 xl:col-span-2 sticky top-8">
            <div class="p-5 rounded-3xl bg-[#181825]/90 border border-[#313244] shadow-xl">
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#a6adc8] mb-3 flex items-center gap-2 font-mono">
                    <i data-lucide="align-left" class="w-3.5 h-3.5 text-[#cba6f7]"></i>
                    <span>Nesta Página</span>
                </h4>
                <div id="on-this-page-toc" class="space-y-1.5 text-xs text-[#a6adc8]">
                    <!-- Preenchido automaticamente via JavaScript a partir dos headers H1, H2, H3 -->
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    /* Catppuccin Mocha Markdown Typography */
    .catppuccin-prose h1 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.875rem;
        font-weight: 800;
        color: #f5e0dc;
        letter-spacing: -0.025em;
        margin-top: 0;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(49, 50, 68, 0.8);
        padding-bottom: 0.75rem;
    }
    .catppuccin-prose h2 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 700;
        color: #89b4fa;
        margin-top: 2rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .catppuccin-prose h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.1rem;
        font-weight: 600;
        color: #cba6f7;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }
    .catppuccin-prose p {
        color: #bac2de;
        line-height: 1.7;
        margin-bottom: 1rem;
    }
    .catppuccin-prose ul {
        list-style-type: disc;
        padding-left: 1.5rem;
        margin-bottom: 1rem;
        color: #bac2de;
        space-y: 0.35rem;
    }
    .catppuccin-prose ol {
        list-style-type: decimal;
        padding-left: 1.5rem;
        margin-bottom: 1rem;
        color: #bac2de;
        space-y: 0.35rem;
    }
    .catppuccin-prose li {
        margin-bottom: 0.35rem;
    }
    .catppuccin-prose strong {
        color: #f5e0dc;
        font-weight: 700;
    }
    .catppuccin-prose hr {
        border-color: #313244;
        margin: 2rem 0;
    }
    .catppuccin-prose blockquote {
        border-left: 4px solid #cba6f7;
        padding-left: 1rem;
        font-style: italic;
        color: #a6adc8;
        background: rgba(203, 166, 247, 0.05);
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        border-radius: 0 0.75rem 0.75rem 0;
        margin: 1.25rem 0;
    }
    /* Catppuccin Code Blocks */
    .catppuccin-prose pre {
        background-color: #11111b !important;
        border: 1px solid #313244;
        border-radius: 1rem;
        padding: 1.25rem;
        overflow-x: auto;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8125rem;
        color: #cdd6f4;
        position: relative;
        margin: 1.25rem 0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .catppuccin-prose code:not(pre code) {
        background-color: #313244;
        color: #f38ba8;
        padding: 0.2rem 0.4rem;
        border-radius: 0.375rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8125rem;
        border: 1px solid rgba(255,255,255,0.05);
    }
    /* Catppuccin Tables */
    .catppuccin-prose table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid #313244;
    }
    .catppuccin-prose th {
        background-color: #11111b;
        color: #89b4fa;
        font-weight: 700;
        text-align: left;
        padding: 0.75rem 1rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #313244;
    }
    .catppuccin-prose td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #313244;
        color: #bac2de;
        font-size: 0.8125rem;
    }
    .catppuccin-prose tr:nth-child(even) td {
        background-color: rgba(24, 24, 37, 0.5);
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Gera dinamicamente o sumário lateral "Nesta Página" (TOC)
        const content = document.getElementById('doc-content');
        const tocContainer = document.getElementById('on-this-page-toc');
        if (content && tocContainer) {
            const headings = content.querySelectorAll('h2, h3');
            if (headings.length > 0) {
                headings.forEach((heading, index) => {
                    const id = 'heading-' + index;
                    heading.id = id;

                    const a = document.createElement('a');
                    a.href = '#' + id;
                    a.className = `block py-1 px-2 rounded-lg transition-colors truncate ${
                        heading.tagName === 'H3' ? 'pl-4 text-[11px] text-[#6c7086]' : 'text-xs text-[#a6adc8] font-medium'
                    } hover:text-[#cba6f7] hover:bg-[#313244]/50`;
                    a.textContent = heading.textContent;
                    tocContainer.appendChild(a);
                });
            } else {
                tocContainer.innerHTML = '<span class="text-[11px] text-[#6c7086]">Tópico único</span>';
            }
        }

        // 2. Adiciona botões de copiar em todos os blocos de código
        const codeBlocks = document.querySelectorAll('.catppuccin-prose pre');
        codeBlocks.forEach((pre) => {
            const button = document.createElement('button');
            button.className = 'absolute top-3 right-3 px-2.5 py-1 rounded-lg text-[10px] font-mono bg-[#313244] hover:bg-[#45475a] text-[#cdd6f4] border border-[#45475a] transition-all flex items-center gap-1';
            button.innerHTML = '<i data-lucide="copy" class="w-3 h-3"></i><span>Copiar</span>';

            button.addEventListener('click', () => {
                const text = pre.querySelector('code')?.innerText || pre.innerText;
                navigator.clipboard.writeText(text).then(() => {
                    button.innerHTML = '<i data-lucide="check" class="w-3 h-3 text-[#a6e3a1]"></i><span class="text-[#a6e3a1]">Copiado!</span>';
                    lucide.createIcons();
                    setTimeout(() => {
                        button.innerHTML = '<i data-lucide="copy" class="w-3 h-3"></i><span>Copiar</span>';
                        lucide.createIcons();
                    }, 2000);
                });
            });

            pre.appendChild(button);
        });

        lucide.createIcons();
    });
</script>
@endpush
