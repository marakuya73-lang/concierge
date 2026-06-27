const STAY_DETAILS_KEY = 'stayDetails';
const ACCESS_CODE_KEY = 'accessCode';

export function clearStayCache() {
    localStorage.removeItem(STAY_DETAILS_KEY);
    localStorage.removeItem(ACCESS_CODE_KEY);
}

/** Matches server rule: access until checkout + 1 day (inclusive). */
export function isStayCacheValid() {
    const raw = localStorage.getItem(STAY_DETAILS_KEY);
    if (!raw) {
        return false;
    }

    try {
        const data = JSON.parse(raw);
        if (!data.checkOut) {
            return false;
        }

        const graceEnd = parseDate(data.checkOut);
        graceEnd.setDate(graceEnd.getDate() + 1);

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        return today <= graceEnd;
    } catch {
        return false;
    }
}

function parseDate(ymd) {
    const [year, month, day] = ymd.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    date.setHours(0, 0, 0, 0);

    return date;
}

export function canUseCachedStay(code) {
    const cachedCode = localStorage.getItem(ACCESS_CODE_KEY);

    return cachedCode === code && isStayCacheValid();
}
