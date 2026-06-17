export async function reportClientError(message, route, { code = null, httpStatus = null, context = {} } = {}) {
    const trimmedMessage = String(message || '').trim();
    const trimmedRoute = String(route || '').trim();

    if (!trimmedMessage || !trimmedRoute) {
        return;
    }

    try {
        await fetch('/api/client-error', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            keepalive: true,
            body: JSON.stringify({
                message: trimmedMessage.slice(0, 500),
                route: trimmedRoute.slice(0, 120),
                code,
                httpStatus,
                context,
            }),
        });
    } catch {
        // ignore reporting failures
    }
}

export function shouldReportHttpStatus(status) {
    return !status || status >= 500;
}
