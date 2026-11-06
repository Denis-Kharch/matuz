// Отправка заявки на КП. Этап 1: <meta name="kp-endpoint"> нет — ничего не отправляем.
// Этап 3: сервер добавит мету, и заявка уйдёт POST-запросом.
// Функция никогда не отклоняется: сбой отправки не должен мешать посетителю получить КП.
export function sendLead(payload) {
    const meta = document.querySelector('meta[name="kp-endpoint"]');
    if (!meta?.content) return Promise.resolve();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    return fetch(meta.content, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        body: JSON.stringify(payload),
        keepalive: true,
    })
        .then(() => undefined)
        .catch((e) => console.warn('КП: заявка не отправлена', e));
}
