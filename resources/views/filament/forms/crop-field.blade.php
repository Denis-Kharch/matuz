@php
    $config = $getEditorConfig();
    $statePath = $getStatePath();
@endphp

{{-- Ошибка проверки файла (скрытое поле upload) показывается под превью. --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :error-message="$errors->first($config['uploadPath']) ?: null">
    <div
        wire:key="{{ $statePath }}-{{ md5((string) $config['sourceUrl']) }}"
        x-data="imageCrop({
            crop: $wire.$entangle(@js($statePath)),
            focalX: $wire.$entangle(@js($config['focalXPath'])),
            focalY: $wire.$entangle(@js($config['focalYPath'])),
            config: @js($config),
        })"
        @class(['mcf', 'mcf--contain' => $config['contain']])
    >
        @if ($config['sourceUrl'])
            <div
                class="mcf__preview"
                x-ref="previewBox"
                @if ($config['focal']) x-on:click="setFocal($event)" title="Нажмите, чтобы поставить точку фокуса" @endif
            >
                <img x-bind:src="previewSrc" src="{{ $config['previewUrl'] }}" alt="" draggable="false">
                @if ($config['focal'])
                    <span class="mcf__focal" x-bind:style="`left: ${focalX}%; top: ${focalY}%`" aria-hidden="true"></span>
                @endif
            </div>
            @if ($config['focal'])
                <p class="mcf__hint">Нажмите на фото, чтобы поставить точку фокуса: она остаётся в кадре на любых экранах.</p>
            @endif
        @else
            <div class="mcf__empty">Файл не открывается для просмотра — проверьте формат.</div>
        @endif

        @if (! $config['contain'] && $config['sourceUrl'])
            <div class="mcf__actions">
                <x-filament::button type="button" size="sm" color="gray" icon="heroicon-o-scissors" x-on:click="open()">
                    Кадрировать
                </x-filament::button>
                <span class="mcf__status" x-show="crop" x-text="cropLabel()"></span>
            </div>

            <template x-teleport="body">
                <div
                    class="mcf-modal"
                    x-show="isOpen"
                    x-cloak
                    x-on:keydown.escape.window="isOpen && cancel()"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Кадрирование фото"
                >
                    <div class="mcf-modal__panel">
                        <div class="mcf-modal__head">
                            <h2>Кадрирование</h2>
                            <span x-text="aspectLabel()"></span>
                        </div>
                        <div class="mcf-modal__stage">
                            <img x-bind:id="imgId" alt="">
                        </div>
                        <div class="mcf-modal__tools">
                            <x-filament::button type="button" size="sm" color="gray" icon="heroicon-o-arrow-uturn-left" x-on:click="rotate(-90)">Повернуть влево</x-filament::button>
                            <x-filament::button type="button" size="sm" color="gray" icon="heroicon-o-arrow-uturn-right" x-on:click="rotate(90)">Повернуть</x-filament::button>
                            <x-filament::button type="button" size="sm" color="gray" icon="heroicon-o-arrows-right-left" x-on:click="flip('x')">Отразить</x-filament::button>
                            <x-filament::button type="button" size="sm" color="gray" icon="heroicon-o-arrows-up-down" x-on:click="flip('y')">Отразить вертикально</x-filament::button>
                            <x-filament::button type="button" size="sm" color="gray" icon="heroicon-o-magnifying-glass-plus" x-on:click="zoom(0.1)" :label-sr-only="true">Приблизить</x-filament::button>
                            <x-filament::button type="button" size="sm" color="gray" icon="heroicon-o-magnifying-glass-minus" x-on:click="zoom(-0.1)" :label-sr-only="true">Отдалить</x-filament::button>
                            <x-filament::button type="button" size="sm" color="gray" x-on:click="reset()">Сбросить</x-filament::button>
                        </div>
                        <div class="mcf-modal__foot">
                            <x-filament::button type="button" color="gray" x-on:click="cancel()">Отмена</x-filament::button>
                            <x-filament::button type="button" x-on:click="apply()">Применить</x-filament::button>
                        </div>
                    </div>
                </div>
            </template>
        @endif
    </div>
</x-dynamic-component>
