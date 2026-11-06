@php($contacts = $c->contacts())
<footer class="border-t border-line py-10 pb-28 text-sm text-muted sm:pb-10">
    <div class="wrap flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
        <p>© {{ date('Y') }} Андрей Матузов · Санкт-Петербург</p>
        <ul class="flex flex-wrap gap-x-6 gap-y-2">
            <li><a class="link-draw hover:text-text" href="{{ $contacts['phone_href'] }}">{{ $contacts['phone'] }}</a></li>
            <li><a class="link-draw hover:text-text" href="{{ $contacts['vk'] }}" target="_blank" rel="noopener">ВКонтакте</a></li>
            <li><a class="link-draw hover:text-text" href="{{ $contacts['telegram'] }}" target="_blank" rel="noopener">Telegram</a></li>
            <li><a class="link-draw hover:text-text" href="{{ $contacts['max'] }}" target="_blank" rel="noopener">MAX</a></li>
        </ul>
    </div>
</footer>
