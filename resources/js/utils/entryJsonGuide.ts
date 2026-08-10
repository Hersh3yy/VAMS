import type { EntryField, EntryType } from '@/types/entryField'

export interface EntryJsonGuide {
    prompt: string
    example: string
    schemaNotes: string[]
}

function exampleValueForField(field: EntryField): unknown {
    switch (field.type) {
        case 'number':
            return 0
        case 'checkbox':
            return false
        case 'repeatable':
            return [
                Object.fromEntries(
                    (field.fields ?? []).map((nested) => [nested.name, exampleValueForField(nested as EntryField)]),
                ),
            ]
        case 'image_collection':
            return [
                {
                    path: 'uploads/example.webp',
                    ...Object.fromEntries(
                        (field.fields ?? []).map((nested) => [nested.name, nested.name === 'alt' ? 'Alt text' : '']),
                    ),
                },
            ]
        case 'entry_relation':
            return ['00000000-0000-0000-0000-000000000000']
        case 'object':
            return Object.fromEntries(
                (field.fields ?? []).map((nested) => [nested.name, exampleValueForField(nested as EntryField)]),
            )
        case 'textarea':
            return `Example ${field.label || field.name}`
        default:
            return `Example ${field.label || field.name}`
    }
}

function describeField(field: EntryField, indent = '    '): string {
    const required = field.required ? 'required' : 'optional'
    let line = `${indent}- ${field.name} (${field.type}, ${required}): ${field.label || field.name}`

    if ('fields' in field && Array.isArray(field.fields) && field.fields.length > 0) {
        const nested = field.fields.map((nestedField) => describeField(nestedField as EntryField, indent + '  ')).join('\n')
        line += `\n${nested}`
    }

    if (field.type === 'entry_relation') {
        line += `\n${indent}  (array of entry UUIDs${field.entry_type_slug ? ` for type "${field.entry_type_slug}"` : ''})`
    }

    if (field.type === 'image_collection') {
        line += `\n${indent}  (no file upload here — provide existing image path/metadata objects only)`
    }

    return line
}

export function buildEntryJsonGuide(entryType: EntryType): EntryJsonGuide {
    const fieldConfig = entryType.field_config ?? []

    const contentExample = Object.fromEntries(
        fieldConfig.map((field) => [field.name, exampleValueForField(field)]),
    )

    const exampleObject = {
        title: `Example ${entryType.name}`,
        status: 'published',
        content: contentExample,
    }

    const schemaNotes = fieldConfig.length
        ? fieldConfig.map((field) => describeField(field))
        : ['    - content may be a string (legacy) or an object with your fields']

    const prompt = `You are generating VAMS entry JSON for entry type "${entryType.name}" (slug: ${entryType.slug}).

Return ONLY valid JSON — either one object or an array of objects. No markdown fences, no commentary.

Each object MUST use this shape:
{
  "title": string (required, max 255),
  "status": "draft" | "published" (optional, default "published"),
  "content": { ...fields below }
}

You may also flatten fields onto the object (title/status at top level, field names as siblings) — the server adapter will wrap them into content.

content fields for "${entryType.name}":
${schemaNotes.join('\n')}

Example (single object):
${JSON.stringify(exampleObject, null, 2)}

Example (array):
${JSON.stringify([exampleObject, { ...exampleObject, title: `Another ${entryType.name}` }], null, 2)}
`

    return {
        prompt,
        example: JSON.stringify([exampleObject], null, 2),
        schemaNotes,
    }
}
