{{-- Моментальное КП: форма и документ. Подключается в layout, открывается событием kp-open. --}}
<div x-data="kpForm" x-on:kp-open.window="open($event.detail.source)">
    <x-modal name="kp-form" label="Моментальное КП">
        <p class="kicker">Моментальное КП</p>
        <h2 class="mt-4 pr-10 text-[1.65rem] leading-tight text-text sm:text-3xl">Прочитав коммерческое предложение — вы уже на полпути к результатам</h2>
        <p class="mt-4 text-muted">Укажите компанию — КП сформируется сразу, с ценами, форматами и условиями. Город — по желанию.</p>

        <form class="mt-7 space-y-5" novalidate x-on:submit.prevent="submit()">
            <div>
                <label for="kp-company" class="mb-2 block text-sm font-medium text-text">Название компании</label>
                <input id="kp-company" x-ref="company" autofocus x-model="company" type="text" autocomplete="organization" maxlength="200"
                       placeholder="ООО «Пример»" :aria-invalid="error.toString()" aria-describedby="kp-company-error"
                       class="w-full rounded-xl border border-line bg-bg px-4 py-3.5 text-text placeholder:text-muted/60 transition outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 aria-[invalid=true]:border-accent-2">
                <p id="kp-company-error" x-show="error" x-cloak class="mt-2 text-sm text-accent-2" role="alert">Укажите название компании — без него не получится адресовать КП.</p>
            </div>
            <div>
                <label for="kp-city" class="mb-2 block text-sm font-medium text-text">Город <span class="font-normal text-muted">— необязательно</span></label>
                <input id="kp-city" x-model="city" type="text" autocomplete="address-level2" maxlength="100" placeholder="Санкт-Петербург"
                       class="w-full rounded-xl border border-line bg-bg px-4 py-3.5 text-text placeholder:text-muted/60 transition outline-none focus:border-accent focus:ring-2 focus:ring-accent/30">
            </div>
            <button type="submit" class="btn btn-primary w-full">Сформировать КП</button>
        </form>
        <p class="mt-5 flex items-center gap-2 text-sm text-muted">
            <svg viewBox="0 0 24 24" class="size-4 text-sand" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
            Телефон не нужен — мы не собираем персональные данные.
        </p>
    </x-modal>

    @include('kp.document')
</div>
