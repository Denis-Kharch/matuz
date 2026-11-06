{{-- Иконки контактов. $name: phone | telegram | vk | max | mail. $class — размеры. --}}
@switch($name)
    @case('phone')
        <svg viewBox="0 0 24 24" class="{{ $class ?? 'size-5' }}" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
        @break
    @case('telegram')
        <svg viewBox="0 0 24 24" class="{{ $class ?? 'size-5' }}" fill="currentColor" aria-hidden="true"><path d="M21.4 4.2 2.9 11.3c-1.3.5-1.2 1.2-.2 1.5l4.7 1.5 1.8 5.6c.2.6.4.8.9.8.4 0 .6-.2.9-.4l2.3-2.2 4.7 3.5c.9.5 1.5.2 1.7-.8l3.1-14.6c.3-1.3-.5-1.8-1.4-1.5ZM9 14.6l8.9-5.6c.4-.3.8-.1.5.2l-7.4 6.7-.3 3.1L9 14.6Z"/></svg>
        @break
    @case('vk')
        <svg viewBox="0 0 24 24" class="{{ $class ?? 'size-5' }}" fill="currentColor" aria-hidden="true"><path d="M12.8 17.5c-5.5 0-8.6-3.8-8.7-10h2.7c.1 4.6 2.1 6.5 3.7 6.9V7.5h2.6v3.9c1.6-.2 3.2-2 3.8-3.9h2.6a7.7 7.7 0 0 1-3.5 5 7.9 7.9 0 0 1 4.1 5h-2.8a5 5 0 0 0-4.2-3.6v3.6h-.3Z"/></svg>
        @break
    @case('max')
        <svg viewBox="0 0 24 24" class="{{ $class ?? 'size-5' }}" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3.5a8.5 8.5 0 0 0-7.4 12.7L3.5 20.5l4.4-1.1A8.5 8.5 0 1 0 12 3.5Z"/><path d="M8.5 14.5V9.5l3.5 3.5 3.5-3.5v5"/></svg>
        @break
    @case('mail')
        <svg viewBox="0 0 24 24" class="{{ $class ?? 'size-5' }}" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
        @break
@endswitch
