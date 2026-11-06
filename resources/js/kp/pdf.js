// PDF с КП в браузере. Библиотека подгружается только по клику (отдельный файл).
// Если загрузка или генерация не удалась — печать браузера (стили в kp-print.css).

// Статичная копия листа: без x-* атрибутов, иначе Alpine подхватит клон html2pdf
// и сотрёт подставленный текст (компания, город, дата).
function staticCopy(el) {
    const copy = el.cloneNode(true);
    [copy, ...copy.querySelectorAll('*')].forEach((node) => {
        [...node.attributes].forEach((a) => {
            if (a.name.startsWith('x-') || a.name.startsWith(':') || a.name.startsWith('@')) node.removeAttribute(a.name);
        });
        if (node.style?.display === 'none') node.remove();
    });
    copy.setAttribute('x-ignore', '');
    copy.removeAttribute('x-ref');
    return copy;
}

export async function downloadKp(el, fileName) {
    try {
        const mod = await import('html2pdf.js');
        const html2pdf = mod.default ?? mod;
        await html2pdf()
            .set({
                margin: [10, 10, 12, 10],
                filename: fileName,
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: {
                    scale: 2,
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    ignoreElements: (node) => node.classList?.contains('no-pdf'),
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'], avoid: '.kp-avoid' },
            })
            .from(staticCopy(el))
            .save();
    } catch (e) {
        console.warn('КП: PDF не собрался, открываю печать', e);
        window.print();
    }
}
