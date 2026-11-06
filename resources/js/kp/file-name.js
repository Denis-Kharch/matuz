// Имя PDF с КП: «КП_Матузов_<Компания>.pdf».
// Оставляем буквы, цифры, пробел и дефис; пробелы → «_»; не длиннее 60 символов.
export function kpFileName(company) {
    const clean = String(company ?? '')
        .replace(/[^\p{L}\p{N} -]+/gu, ' ')
        .trim()
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_');
    const cut = Array.from(clean).slice(0, 60).join('').replace(/^[_-]+|[_-]+$/g, '');
    return cut ? `КП_Матузов_${cut}.pdf` : 'КП_Матузов.pdf';
}
