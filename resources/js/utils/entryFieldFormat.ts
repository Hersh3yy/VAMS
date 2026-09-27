// Shared value formatters for entry fields. Used by the read view (FieldDisplay),
// the entry form (datetime round-tripping) and the one-line summary on index cards,
// so a value looks the same everywhere.

interface FieldLike {
    name: string;
    type: string;
    label?: string;
    summary?: boolean;
}

export interface ParsedDateTime {
    year: number;
    month: number; // 1-12
    day: number;
    hour: number;
    minute: number;
    second: number;
    /** Offset exactly as stored ('Z', '+02:00'), or null when the value had none. */
    offset: string | null;
}

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const ISO_DATETIME =
    /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?)?\s*(Z|[+-]\d{2}:?\d{2})?$/i;

const pad = (n: number): string => String(n).padStart(2, '0');

function normalizeOffset(offset: string | undefined | null): string | null {
    if (!offset) {
        return null;
    }
    if (offset.toUpperCase() === 'Z') {
        return 'Z';
    }
    return offset.includes(':') ? offset : `${offset.slice(0, 3)}:${offset.slice(3)}`;
}

/**
 * Parse an ISO 8601 string into its wall-clock parts WITHOUT converting to the
 * viewer's timezone: "2026-10-22T23:00:00+02:00" stays 23:00 (the event's local time).
 */
export function parseIsoDateTime(value: unknown): ParsedDateTime | null {
    if (typeof value !== 'string' || value.trim() === '') {
        return null;
    }

    const match = ISO_DATETIME.exec(value.trim());
    if (match) {
        return {
            year: Number(match[1]),
            month: Number(match[2]),
            day: Number(match[3]),
            hour: Number(match[4] ?? 0),
            minute: Number(match[5] ?? 0),
            second: Number(match[6] ?? 0),
            offset: normalizeOffset(match[7])
        };
    }

    // Anything else Date can read: fall back to the viewer's local time.
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return {
        year: date.getFullYear(),
        month: date.getMonth() + 1,
        day: date.getDate(),
        hour: date.getHours(),
        minute: date.getMinutes(),
        second: date.getSeconds(),
        offset: null
    };
}

/**
 * "Thu 22 Oct 2026, 23:00". With `compact`, the year is dropped when it is the
 * current year ("Thu 22 Oct, 23:00"). Unparseable values are returned as-is.
 */
export function formatDateTime(value: unknown, options: { compact?: boolean } = {}): string {
    const parsed = parseIsoDateTime(value);
    if (!parsed) {
        return value === null || value === undefined ? '' : String(value);
    }

    const weekday =
        WEEKDAYS[new Date(Date.UTC(parsed.year, parsed.month - 1, parsed.day)).getUTCDay()];
    const showYear = !options.compact || parsed.year !== new Date().getFullYear();

    return `${weekday} ${parsed.day} ${MONTHS[parsed.month - 1]}${showYear ? ` ${parsed.year}` : ''}, ${pad(parsed.hour)}:${pad(parsed.minute)}`;
}

/** ISO string -> value for <input type="datetime-local"> ("YYYY-MM-DDTHH:mm"), keeping the stored wall-clock time. */
export function toDateTimeLocalValue(value: unknown): string {
    const parsed = parseIsoDateTime(value);
    if (!parsed) {
        return '';
    }

    return `${parsed.year}-${pad(parsed.month)}-${pad(parsed.day)}T${pad(parsed.hour)}:${pad(parsed.minute)}`;
}

/** The browser's UTC offset ("+02:00") at a given local date-time. */
function browserOffsetAt(localValue: string): string {
    const date = new Date(localValue);
    const minutes = Number.isNaN(date.getTime())
        ? -new Date().getTimezoneOffset()
        : -date.getTimezoneOffset();
    const sign = minutes >= 0 ? '+' : '-';
    const abs = Math.abs(minutes);

    return `${sign}${pad(Math.floor(abs / 60))}:${pad(abs % 60)}`;
}

/**
 * datetime-local value -> ISO 8601 string with offset. The offset of the previous
 * value is kept (so editing an event stored as +02:00 stays +02:00); a new value
 * gets the browser's offset for that date. Empty input -> null.
 */
export function fromDateTimeLocalValue(localValue: string, previousValue?: unknown): string | null {
    const match = /^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})(?::(\d{2}))?/.exec(localValue ?? '');
    if (!match) {
        return null;
    }

    const offset = parseIsoDateTime(previousValue)?.offset ?? browserOffsetAt(localValue);

    return `${match[1]}T${match[2]}:${match[3] ?? '00'}${offset}`;
}

export function isHttpUrl(value: unknown): value is string {
    return typeof value === 'string' && /^https?:\/\/\S+$/i.test(value.trim());
}

/** Link target plus a short "host/path" label (no scheme, no www., truncated). */
export function formatUrl(value: unknown, maxLength = 48): { href: string; label: string } {
    const raw = String(value ?? '').trim();

    try {
        const url = new URL(raw);
        const host = url.hostname.replace(/^www\./i, '');
        const path = url.pathname.replace(/\/+$/, '');
        const label = `${host}${path}`;

        return {
            href: url.href,
            label: label.length > maxLength ? `${label.slice(0, maxLength - 1)}…` : label
        };
    } catch {
        return {
            href: raw,
            label: raw.length > maxLength ? `${raw.slice(0, maxLength - 1)}…` : raw
        };
    }
}

export function formatNumber(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    const number = typeof value === 'number' ? value : Number(value);

    return Number.isFinite(number) ? String(number) : String(value);
}

export function formatCheckbox(value: unknown): string {
    return value ? 'Yes' : 'No';
}

export function isEmptyValue(value: unknown): boolean {
    return (
        value === null ||
        value === undefined ||
        value === '' ||
        (Array.isArray(value) && value.length === 0) ||
        (typeof value === 'object' &&
            !Array.isArray(value) &&
            Object.keys(value as object).length === 0)
    );
}

export function isScalar(value: unknown): value is string | number | boolean {
    return ['string', 'number', 'boolean'].includes(typeof value);
}

export function isScalarArray(value: unknown): value is Array<string | number | boolean> {
    return Array.isArray(value) && value.every(isScalar);
}

const truncate = (text: string, max: number): string =>
    text.length > max ? `${text.slice(0, max - 1)}…` : text;

/**
 * Compact one-line text for a field value (card summaries). Returns null when the
 * value should be left out: empty values and unchecked checkboxes. A checked
 * checkbox shows its label ("Sold out"), which reads better in a summary than "Yes".
 */
export function formatSummaryValue(
    field: FieldLike,
    value: unknown,
    titleFor: (id: string) => string | null = () => null
): string | null {
    if (field.type === 'checkbox') {
        return value ? field.label || field.name : null;
    }

    if (isEmptyValue(value)) {
        return null;
    }

    switch (field.type) {
        case 'datetime':
            return formatDateTime(value, { compact: true });
        case 'url':
            return formatUrl(value, 32).label;
        case 'number':
            return formatNumber(value);
        case 'entry_relation': {
            const ids = (Array.isArray(value) ? value : [value]).map(String);
            const titles = ids.slice(0, 2).map(id => titleFor(id) ?? '…');
            return ids.length > 2 ? `${titles.join(', ')} +${ids.length - 2}` : titles.join(', ');
        }
        case 'json':
            return truncate(isScalarArray(value) ? value.join(', ') : JSON.stringify(value), 60);
        case 'text':
        case 'textarea':
        case 'select':
            return isHttpUrl(value)
                ? formatUrl(value, 32).label
                : truncate(String(value).split('\n')[0], 60);
        default:
            return isScalar(value) ? truncate(String(value), 60) : null;
    }
}

/** Fields flagged with `summary: true`, in config order. */
export function summaryFields<T extends FieldLike>(fieldConfig: T[] | null | undefined): T[] {
    return (fieldConfig ?? []).filter(field => field.summary === true);
}

/** "BRET · Wed 21 Oct, 22:00 · Sold out" for the given content. */
export function formatSummaryLine(
    fields: FieldLike[],
    content: Record<string, unknown>,
    titleFor?: (id: string) => string | null
): string {
    return fields
        .map(field => formatSummaryValue(field, content[field.name], titleFor))
        .filter((part): part is string => part !== null && part !== '')
        .join(' · ');
}
