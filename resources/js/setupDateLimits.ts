import { voxAlert } from './voxDialogs';
const selector = 'input[type="date"], input[type="datetime-local"]';
function constrain(root: ParentNode) {
    root.querySelectorAll<HTMLInputElement>(selector).forEach(input => {
        const time = input.type === 'datetime-local';
        const min = time ? '1000-01-01T00:00' : '1000-01-01';
        const max = time ? '2999-12-31T23:59' : '2999-12-31';
        if (!input.min || input.min < min) input.min = min;
        if (!input.max || input.max > max) input.max = max;
    });
}
const observer = new MutationObserver(() => constrain(document));
observer.observe(document.body, { childList: true, subtree: true });
constrain(document);
const check = (event: Event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches(selector)) return;
    const year = input.value.split('-')[0];
    input.setCustomValidity(year && !/^[12][0-9]{3}$/.test(year) ? 'Yıl 1 veya 2 ile başlayan dört rakam olmalıdır.' : '');
};
const rejectInvalidYear = (event: Event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches(selector)) return;
    const year = input.value.split('-')[0];
    if ((!year && !input.validity.badInput) || (year && /^[12][0-9]{3}$/.test(year))) return;
    input.value = '';
    input.setCustomValidity('');
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    void voxAlert('Yıl 1 veya 2 ile başlayan dört rakam olmalıdır. Örneğin: 2026.', 'error');
};
document.addEventListener('focusout', rejectInvalidYear, true);
document.addEventListener('input', check, true);
document.addEventListener('change', check, true);
if (import.meta.hot) import.meta.hot.dispose(() => { observer.disconnect(); document.removeEventListener('focusout',rejectInvalidYear,true); document.removeEventListener('input',check,true); document.removeEventListener('change',check,true); });
