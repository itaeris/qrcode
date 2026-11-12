function xsrf() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function firstError(errors) {
    if (!errors) {
        return '';
    }

    const value = Object.values(errors)[0];

    return Array.isArray(value) ? value[0] : value;
}

export async function api(url, { body, signal } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-XSRF-TOKEN': xsrf(),
    };
    let payload = body;

    if (!(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body ?? {});
    }

    const response = await fetch(url, {
        method: 'POST',
        headers,
        body: payload,
        signal,
        credentials: 'same-origin',
    });
    const data = await response.json().catch(() => ({}));

    if (response.status === 401) {
        window.location.assign('/masuk');
    }

    if (!response.ok) {
        const message = response.status === 419 || response.status === 401
            ? 'Sesi habis. Masuk lagi untuk melanjutkan.'
            : (firstError(data.errors) || data.message || 'Permintaan gagal.');
        const error = new Error(message);
        error.status = response.status;
        throw error;
    }

    return data;
}
