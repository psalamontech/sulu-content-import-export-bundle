import {Requester} from 'sulu-admin-bundle/services';

const CSRF_HEADER_NAME = 'csrf-token';
const DEFAULT_HEADERS = {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
};

function createCsrfToken() {
    const bytes = new Uint8Array(18);
    window.crypto.getRandomValues(bytes);

    return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
}

async function parseJsonResponse(response) {
    const text = await response.text();
    let data = {};

    if (text) {
        try {
            data = JSON.parse(text);
        } catch (error) {
            throw new Error(response.ok ? 'Invalid server response.' : 'Request failed.');
        }
    }

    if (!response.ok) {
        if (response.status === 403 && data.error === 'Invalid CSRF token.') {
            throw new Error('Your admin session expired or the security token became invalid. Reload the tab and try again.');
        }

        throw new Error(data.error || 'Request failed');
    }

    return data;
}

export function getJson(url) {
    return Requester.fetch(url, {
        method: 'GET',
        headers: DEFAULT_HEADERS,
    }).then(parseJsonResponse);
}

export function postProtectedJson(url, payload) {
    const csrfToken = createCsrfToken();

    return Requester.fetch(url, {
        method: 'POST',
        headers: {
            ...DEFAULT_HEADERS,
            [CSRF_HEADER_NAME]: csrfToken,
        },
        body: JSON.stringify({
            ...payload,
            _token: csrfToken,
        }),
    }).then(parseJsonResponse);
}
