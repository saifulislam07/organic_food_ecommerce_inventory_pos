import axios from 'axios';

const token = document.head.querySelector('meta[name="csrf-token"]');

const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

if (token) {
    http.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.warn('CSRF token meta tag not found — POST/PATCH requests will fail.');
}

/**
 * Pull a human readable message out of an axios error, whatever shape
 * Laravel returned (validation bag, exception message, or nothing at all).
 */
export function errorMessage(error, fallback = 'Something went wrong. Please try again.') {
    const data = error?.response?.data;

    if (data?.errors) {
        const first = Object.values(data.errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
    }

    return data?.message || error?.message || fallback;
}

export default http;
