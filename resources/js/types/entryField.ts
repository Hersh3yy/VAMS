// Type definitions for dynamic entry field system

export interface BaseField {
    name: string
    type: string
    label: string
    required?: boolean
    placeholder?: string
}

export interface SimpleField extends BaseField {
    type: 'text' | 'textarea' | 'number' | 'select' | 'checkbox' | 'image'
    options?: string[] // For select fields
}

export interface RepeatableField extends BaseField {
    type: 'repeatable'
    fields: SimpleField[]
    min?: number
    max?: number
}

export interface ImageCollectionField extends BaseField {
    type: 'image_collection'
    fields: SimpleField[] // Alt, caption, etc.
    min?: number
    max?: number
    allow_reorder?: boolean
}

export interface EntryRelationField extends BaseField {
    type: 'entry_relation'
    entry_type_slug?: string
    min?: number
    max?: number
    exclude_current?: boolean
}

export interface ObjectField extends BaseField {
    type: 'object'
    fields: SimpleField[]
    collapsible?: boolean
}

export type EntryField = SimpleField | RepeatableField | ImageCollectionField | EntryRelationField | ObjectField

export const COMPLEX_FIELD_TYPES = ['repeatable', 'image_collection', 'object', 'entry_relation'] as const

/**
 * Decide whether the dynamic, field_config-driven form should be used.
 * Only legacy single-textarea types (e.g. "I AM") keep the simple title + content form.
 */
export function shouldUseDynamicEntryForm(fieldConfig?: EntryField[] | null): boolean {
    if (!fieldConfig || fieldConfig.length === 0) {
        return false
    }

    if (fieldConfig.some((field) => (COMPLEX_FIELD_TYPES as readonly string[]).includes(field.type))) {
        return true
    }

    return !(fieldConfig.length === 1 && fieldConfig[0].type === 'textarea')
}

export interface EntryType {
    id: string
    name: string
    slug: string
    description?: string
    field_config: EntryField[]
    is_active: boolean
}

export interface CaseEntryContent {
    client?: string
    role?: string
    team?: string
    sections?: Array<{
        title: string
        content: string
    }>
    images?: Array<{
        path: string
        alt?: string
        caption?: string
        order: number
    }>
    related_projects?: string[] // Entry IDs
    seo?: {
        title?: string
        description?: string
        og_title?: string
        og_description?: string
    }
}

export interface IAmEntryContent {
    statement: string
}

