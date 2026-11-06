@props(['name', 'label', 'fullscreen' => false, 'dismissable' => true, 'panelClass' => '', 'animate' => true])
{{-- Окно из стека $store.modals. Фон закрывает окно (если dismissable), фокус удерживается внутри. --}}
<div
    x-data
    x-show="$store.modals.isOpen('{{ $name }}')"
    x-cloak
    x-trap.inert.noscroll="$store.modals.isTop('{{ $name }}')"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $label }}"
    data-modal="{{ $name }}"
    {{ $attributes->class(['fixed inset-0 z-[70] flex', 'p-4' => ! $fullscreen]) }}
    @if ($dismissable) x-on:click.self="$store.modals.close('{{ $name }}')" @endif
    @if ($animate)
    x-transition:enter="transition duration-300 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-200 ease-in"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @endif
>
    <div class="pointer-events-none absolute inset-0 bg-[#050909]/65 backdrop-blur-md" aria-hidden="true"></div>
    <div
        @class([
            'relative m-auto w-full',
            'h-full overflow-y-auto overscroll-contain bg-bg' => $fullscreen,
            'glass max-h-[92svh] max-w-xl overflow-y-auto overscroll-contain rounded-[1.75rem] bg-surface/75 p-6 sm:p-8' => ! $fullscreen,
            $panelClass,
        ])
    >
        @if ($dismissable)
            <button
                type="button"
                class="glass absolute top-4 right-4 z-10 grid size-11 place-items-center rounded-full text-text/80 transition hover:text-accent-2"
                x-on:click="$store.modals.close('{{ $name }}')"
                aria-label="Закрыть"
            >
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        @endif
        {{ $slot }}
    </div>
</div>
