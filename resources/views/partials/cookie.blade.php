<div x-data="cookieBanner">
    <x-modal name="cookie" label="Файлы cookie" :dismissable="false">
        <h2 class="font-display text-2xl text-text">Файлы cookie</h2>
        <p class="mt-3 text-muted">Сайт использует файлы cookie для корректной работы и анализа посещаемости. Вы можете принять их или отказаться — и спокойно остаться на сайте.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <button type="button" class="btn btn-primary" x-on:click="choose('accepted')">Принять</button>
            <button type="button" class="btn btn-quiet" x-on:click="choose('declined')">Отказать и остаться</button>
        </div>
    </x-modal>
</div>
