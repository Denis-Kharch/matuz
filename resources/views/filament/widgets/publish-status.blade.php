<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                @if (! $status)
                    <p class="font-medium">Сайт ещё не обновлялся из админки</p>
                @elseif ($status['ok'] ?? false)
                    <p class="font-medium">Сайт обновлён в {{ $time }}</p>
                    <p class="text-sm text-gray-500">{{ $date }}, страниц: {{ $status['pages'] ?? 0 }}</p>
                @else
                    <p class="font-medium text-danger-600">Ошибка: {{ $status['error'] ?? 'неизвестная' }}</p>
                    <p class="text-sm text-gray-500">{{ $date }} {{ $time }}. На сайте осталась прежняя версия.</p>
                @endif
            </div>
            <x-filament::button wire:click="rebuild" wire:loading.attr="disabled" icon="heroicon-m-arrow-path">
                Пересобрать сайт
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
