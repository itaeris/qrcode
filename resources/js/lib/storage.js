const KEY = 'qr-generate.mine';

export function loadMine() {
    try {
        const parsed = JSON.parse(localStorage.getItem(KEY) || '[]');

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function remember(entry) {
    const next = [entry, ...loadMine().filter((item) => item.code !== entry.code)].slice(0, 100);
    localStorage.setItem(KEY, JSON.stringify(next));
}

export function forget(code) {
    localStorage.setItem(KEY, JSON.stringify(loadMine().filter((item) => item.code !== code)));
}

export function secretFor(code) {
    return loadMine().find((item) => item.code === code)?.secret ?? '';
}
