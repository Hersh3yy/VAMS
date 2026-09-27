// Helpers for deciding how an entry type is edited and displayed.

interface FieldLike {
    name?: string;
    type?: string;
}

interface EntryTypeLike {
    field_config?: FieldLike[] | null;
}

/** Field names used by the legacy single-textarea types (see EntryType::getDefaultFieldConfig / getIAmsFieldConfig). */
const LEGACY_FIELD_NAMES = ['content', 'statement'];

/**
 * True when the type's content is a single legacy textarea ("I AM" style):
 * no field_config at all, or exactly one textarea named `content` or `statement`.
 */
export function isLegacyEntryType(entryType: EntryTypeLike | null | undefined): boolean {
    const fields = entryType?.field_config;
    if (!Array.isArray(fields) || fields.length === 0) {
        return true;
    }

    if (fields.length !== 1) {
        return false;
    }

    const [field] = fields;
    return field?.type === 'textarea' && LEGACY_FIELD_NAMES.includes(field?.name ?? '');
}

/**
 * True when entries of this type should use the field-by-field form and read view
 * (DynamicEntryForm / EntryReadView) instead of the legacy "Content" textarea.
 */
export function usesFieldForm(entryType: EntryTypeLike | null | undefined): boolean {
    return !isLegacyEntryType(entryType);
}

/** Normalise an entry's content column (object, JSON string or legacy plain string) to an object. */
export function normalizeEntryContent(content: unknown): Record<string, any> {
    if (!content) {
        return {};
    }

    if (typeof content === 'string') {
        try {
            const parsed = JSON.parse(content);
            return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
                ? parsed
                : { statement: content };
        } catch {
            return { statement: content };
        }
    }

    return typeof content === 'object' && !Array.isArray(content)
        ? (content as Record<string, any>)
        : {};
}

/**
 * Text saved by the legacy form (`statement`, or `content`) when none of the type's
 * configured fields hold a value. Lets old entries of a type that has since gained
 * real fields still show / prefill their text instead of silently looking empty.
 */
export function legacyTextFor(
    entryType: EntryTypeLike | null | undefined,
    content: unknown
): string | null {
    const normalized = normalizeEntryContent(content);
    const fields = entryType?.field_config ?? [];

    const hasConfiguredValue = fields.some(field => {
        const value = normalized[field.name ?? ''];
        return value !== undefined && value !== null && value !== '';
    });
    if (hasConfiguredValue) {
        return null;
    }

    const text = normalized.statement ?? normalized.content;
    return typeof text === 'string' && text.trim() !== '' ? text : null;
}
