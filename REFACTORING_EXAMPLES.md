# Component Refactoring Examples

## Example 1: Refactoring EntryModal to Use BaseModal

### Before (210 lines, custom modal markup)

```vue
<template>
    <div v-if="show" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:p-6 dark:bg-gray-800">
                    <!-- Modal content here -->
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" class="inline-flex w-full...">Save</button>
                        <button type="button" class="mt-3 inline-flex...">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
```

### After (~80 lines, using BaseModal)

```vue
<template>
    <BaseModal :show="show" size="2xl" @close="closeModal">
        <template #header>
            <h3 class="text-lg font-medium">
                {{ isEditing ? `Edit ${entryType?.name}` : `View ${entryType?.name}` }}
            </h3>
        </template>

        <template #body>
            <DynamicEntryForm
                v-if="isEditing && isComplexEntryType"
                :entry-type="entryType"
                :entry="entry"
                @submit="handleDynamicSubmit"
                @cancel="cancelEditing"
            />
            
            <EntryReadView
                v-else-if="!isEditing && isComplexEntryType"
                :entry="entry"
                :entry-type="entryType"
            />
            
            <!-- Simple form for backward compatibility -->
            <form v-else @submit.prevent="saveChanges">
                <FormField
                    id="title"
                    v-model="formData.title"
                    label="Title"
                    :readonly="!isEditing"
                    required
                />
                
                <FormField
                    id="content"
                    v-model="formData.content"
                    label="Content"
                    type="textarea"
                    :rows="6"
                    :readonly="!isEditing"
                    required
                />
            </form>
        </template>

        <template #footer>
            <!-- Edit mode buttons -->
            <template v-if="isEditing && !isComplexEntryType">
                <BaseButton variant="primary" @click="saveChanges" :disabled="saving">
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </BaseButton>
                <BaseButton variant="secondary" @click="cancelEditing">
                    Cancel
                </BaseButton>
                <BaseButton variant="danger" @click="deleteEntry">
                    Delete
                </BaseButton>
            </template>
            
            <!-- View mode buttons -->
            <template v-else-if="!isEditing">
                <BaseButton variant="primary" @click="startEditing">
                    Edit
                </BaseButton>
                <BaseButton variant="secondary" @click="closeModal">
                    Close
                </BaseButton>
            </template>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import BaseModal from '@/Components/atoms/BaseModal.vue'
import BaseButton from '@/Components/atoms/BaseButton.vue'
import FormField from '@/Components/molecules/FormField.vue'
import DynamicEntryForm from './DynamicEntryForm.vue'
import EntryReadView from './EntryReadView.vue'
// ... rest of logic
</script>
```

**Benefits:**
- ✅ Reduced from 210 to ~80 lines
- ✅ Consistent modal behavior
- ✅ Better accessibility
- ✅ Easier to maintain
- ✅ Uses atomic design properly

---

## Example 2: Refactoring ConfirmationDialog to Use BaseModal

### Before (82 lines, custom modal markup)

```vue
<template>
    <div v-if="show" class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" />
            </div>
            <!-- 50+ lines of modal content -->
        </div>
    </div>
</template>
```

### After (~35 lines, using BaseModal)

```vue
<template>
    <BaseModal :show="show" size="sm" @close="$emit('cancel')">
        <template #header>
            <div class="flex items-center">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" ...>
                        <!-- Warning icon -->
                    </svg>
                </div>
                <h3 class="ml-4 text-lg font-medium">{{ title }}</h3>
            </div>
        </template>

        <template #body>
            <p class="text-sm text-gray-500">{{ message }}</p>
        </template>

        <template #footer>
            <BaseButton variant="danger" @click="$emit('confirm')">
                {{ confirmText }}
            </BaseButton>
            <BaseButton variant="secondary" @click="$emit('cancel')">
                {{ cancelText }}
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import BaseModal from '@/Components/atoms/BaseModal.vue'
import BaseButton from '@/Components/atoms/BaseButton.vue'

interface Props {
    show: boolean
    title: string
    message: string
    confirmText?: string
    cancelText?: string
}

withDefaults(defineProps<Props>(), {
    confirmText: 'Confirm',
    cancelText: 'Cancel'
})

defineEmits<{
    confirm: []
    cancel: []
}>()
</script>
```

**Benefits:**
- ✅ Reduced from 82 to ~35 lines
- ✅ More maintainable
- ✅ Consistent with other modals

---

## Example 3: Creating FieldDisplay Molecule

### Create New File: `molecules/FieldDisplay.vue`

```vue
<template>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ label }}
        </label>

        <!-- Text/Textarea Display -->
        <div 
            v-if="type === 'text' || type === 'textarea'"
            class="text-sm text-gray-900 dark:text-gray-100"
            :class="{ 'whitespace-pre-wrap': type === 'textarea' }"
        >
            {{ value || '-' }}
        </div>

        <!-- Repeatable Section Display -->
        <div v-else-if="type === 'repeatable' && Array.isArray(value)" class="space-y-3">
            <div 
                v-for="(item, index) in value" 
                :key="index"
                class="border rounded-lg p-3 bg-gray-50 dark:bg-gray-700"
            >
                <div class="space-y-2">
                    <div v-for="[key, val] in Object.entries(item)" :key="key" class="text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-400">
                            {{ formatFieldName(key) }}:
                        </span>
                        <span class="ml-2 text-gray-900 dark:text-gray-100">
                            {{ val || '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Collection Display -->
        <div v-else-if="type === 'image_collection' && Array.isArray(value)" class="grid grid-cols-3 gap-4">
            <div 
                v-for="(image, index) in value"
                :key="index"
                class="border rounded-lg overflow-hidden"
            >
                <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                    <img 
                        v-if="image.url || image.path"
                        :src="image.url || `/storage/${image.path}`"
                        :alt="image.alt || `Image ${index + 1}`"
                        class="w-full h-full object-cover"
                    />
                </div>
                <div v-if="image.alt" class="p-2 bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
                    {{ image.alt }}
                </div>
            </div>
        </div>

        <!-- Object/Group Display -->
        <div 
            v-else-if="type === 'object' && typeof value === 'object'"
            class="border rounded-lg p-3 bg-gray-50 dark:bg-gray-700 space-y-2"
        >
            <div v-for="[key, val] in Object.entries(value)" :key="key" class="text-sm">
                <span class="font-medium text-gray-600 dark:text-gray-400">
                    {{ formatFieldName(key) }}:
                </span>
                <span class="ml-2 text-gray-900 dark:text-gray-100">
                    {{ val || '-' }}
                </span>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!value" class="text-sm text-gray-500">
            No data
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    label: string
    value: any
    type: 'text' | 'textarea' | 'repeatable' | 'image_collection' | 'object'
}

defineProps<Props>()

const formatFieldName = (name: string): string => {
    return name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
}
</script>
```

### Using FieldDisplay in EntryReadView

```vue
<template>
    <div class="space-y-4 max-h-[70vh] overflow-y-auto">
        <FieldDisplay
            v-for="field in displayFields"
            :key="field.name"
            :label="field.label"
            :value="getFieldValue(field.name)"
            :type="field.type"
        />
        
        <div class="text-sm text-gray-500 dark:text-gray-400 pt-4 border-t">
            Created {{ formatDate(entry.created_at) }}
        </div>
    </div>
</template>

<script setup lang="ts">
import FieldDisplay from '@/Components/molecules/FieldDisplay.vue'
// Much simpler now!
</script>
```

**Benefits:**
- ✅ Reusable across all read-only views
- ✅ Consistent field display
- ✅ Single place to update styling
- ✅ Reduces `EntryReadView` from ~110 lines to ~30 lines

---

## Example 4: Using FormField in DynamicEntryForm

### Before

```vue
<div>
    <label :for="field.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ field.label }}
        <span v-if="field.required" class="text-red-500">*</span>
    </label>
    <input
        :id="field.name"
        v-model="form.content[field.name]"
        type="text"
        :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
        :required="field.required"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
    />
</div>
```

### After

```vue
<FormField
    :id="field.name"
    v-model="form.content[field.name]"
    :label="field.label"
    :placeholder="field.placeholder || `Enter ${field.label.toLowerCase()}...`"
    :required="field.required"
    :error="form.errors?.[field.name]"
/>
```

**Benefits:**
- ✅ Reduced from 14 lines to 7 lines
- ✅ Automatic error handling
- ✅ Consistent styling
- ✅ Built-in dark mode support

---

## Migration Script Example

```bash
#!/bin/bash
# migrate_base_to_atoms.sh

echo "Migrating Base/ components to atoms/..."

# Move files
mv resources/js/Components/Base/Button.vue resources/js/Components/atoms/BaseButton.vue
mv resources/js/Components/Base/Input.vue resources/js/Components/atoms/BaseInput.vue
mv resources/js/Components/Base/Modal.vue resources/js/Components/atoms/BaseModal.vue

# Update imports
find resources/js -type f -name "*.vue" -exec sed -i '' 's|@/Components/Base/Button|@/Components/atoms/BaseButton|g' {} +
find resources/js -type f -name "*.vue" -exec sed -i '' 's|@/Components/Base/Input|@/Components/atoms/BaseInput|g' {} +
find resources/js -type f -name "*.vue" -exec sed -i '' 's|@/Components/Base/Modal|@/Components/atoms/BaseModal|g' {} +

echo "Migration complete! Run tests to verify."
```

---

## Testing Checklist After Refactoring

- [ ] All existing tests pass
- [ ] Visual regression tests pass (if applicable)
- [ ] Manual testing of refactored components
- [ ] Check browser console for errors
- [ ] Verify dark mode still works
- [ ] Test responsive behavior
- [ ] Check accessibility (ARIA, focus management)

---

## Rollback Plan

If refactoring causes issues:

1. **Immediately:** `git revert <commit-hash>`
2. **Incremental:** Revert specific file changes
3. **Emergency:** Restore from backup

Always commit refactors separately from features!

