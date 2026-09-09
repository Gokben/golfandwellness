function meta(name: string): string {
    return typeof document === 'undefined' ? '' : document.querySelector<HTMLMetaElement>(`meta[name="${name}"]`)?.content ?? '';
}

export function apiUrl(path: string): string {
    return (meta('api-base') || '/api').replace(/\/$/, '') + '/' + path.replace(/^\//, '');
}

export function apiHeaders(): Record<string, string> {
    const token = meta('csrf-token');
    return { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(token ? { 'X-CSRF-TOKEN': token } : {}) };
}
