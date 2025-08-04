# 🔄 Migration Guide: From Current Structure to Atomic Design

This guide helps you migrate your existing components to the new Atomic Design system.

## 📋 Migration Checklist

### Phase 1: Setup Atomic Design Structure
- [x] Create `atoms/` directory with base components
- [x] Create `molecules/` directory with combined components
- [x] Create `organisms/` directory with complex components
- [x] Create index files for easy importing
- [x] Create example and documentation files

### Phase 2: Component Analysis
- [ ] Audit existing components in `general/`, `shared/`, etc.
- [ ] Identify which components should become atoms, molecules, or organisms
- [ ] Plan component refactoring strategy
- [ ] Create migration timeline

### Phase 3: Gradual Migration
- [ ] Start with atoms (BaseButton, BaseInput, etc.)
- [ ] Update imports in existing components
- [ ] Test thoroughly after each migration
- [ ] Update documentation

## 🔍 Component Analysis

### Current Structure Analysis

#### `general/` Components → Atoms
```
PrimaryButton.vue → BaseButton.vue (variant="primary")
SecondaryButton.vue → BaseButton.vue (variant="secondary")
Modal.vue → Keep as is (complex enough for organism)
ApplicationLogo.vue → Keep as is (specific to app)
NavLink.vue → Keep as is (specific to navigation)
ResponsiveNavLink.vue → Keep as is (specific to navigation)
Dropdown.vue → Could become organism
DropdownLink.vue → Could become atom
```

#### `shared/` Components → Molecules/Organisms
```
ItemCard.vue → Could become organism (complex card with actions)
MediaGrid.vue → Could become organism (grid layout)
ConfirmationDialog.vue → Could become organism (modal + form)
DashboardStats.vue → Could become organism (stats display)
IndexLayout.vue → Keep as layout
```

#### `layout/` Components → Keep as Layouts
```
Navbar.vue → Keep as layout component
MobileNav.vue → Keep as layout component
UserDropdown.vue → Could become organism
```

## 🚀 Migration Examples

### Example 1: Button Migration

**Before (PrimaryButton.vue):**
```vue
<template>
    <button class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:focus:ring-offset-gray-800 dark:active:bg-gray-300">
        <slot />
    </button>
</template>
```

**After (BaseButton.vue):**
```vue
<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="loading-spinner mr-2">
      <!-- Loading spinner -->
    </span>
    <slot />
  </button>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  type?: 'button' | 'submit' | 'reset'
}

// ... rest of implementation
</script>
```

**Usage Migration:**
```vue
<!-- Before -->
<PrimaryButton>Click me</PrimaryButton>

<!-- After -->
<BaseButton variant="primary">Click me</BaseButton>
```

### Example 2: Form Field Migration

**Before (Manual implementation):**
```vue
<template>
  <div class="form-group">
    <label for="email" class="form-label">Email</label>
    <input
      id="email"
      v-model="email"
      type="email"
      class="form-input"
      :class="{ 'error': emailError }"
    />
    <span v-if="emailError" class="error-message">{{ emailError }}</span>
  </div>
</template>
```

**After (FormField molecule):**
```vue
<template>
  <FormField
    v-model="email"
    label="Email"
    type="email"
    :error="emailError"
    required
  />
</template>

<script setup lang="ts">
import { FormField } from '@/Components/molecules'
</script>
```

### Example 3: Data Table Migration

**Before (Manual table):**
```vue
<template>
  <div class="table-container">
    <div class="table-header">
      <h3>Users</h3>
      <button @click="addUser">Add User</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td>{{ user.name }}</td>
          <td>{{ user.email }}</td>
          <td>
            <button @click="editUser(user)">Edit</button>
            <button @click="deleteUser(user)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

**After (DataTable organism):**
```vue
<template>
  <DataTable
    title="Users"
    :columns="columns"
    :data="users"
    :show-add-button="true"
    @add="addUser"
    @edit="editUser"
    @delete="deleteUser"
  />
</template>

<script setup lang="ts">
import { DataTable } from '@/Components/organisms'

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' }
]
</script>
```

## 📝 Step-by-Step Migration Process

### Step 1: Update Imports
1. Replace individual component imports with atomic design imports
2. Update component usage to use new prop names
3. Test each component after migration

### Step 2: Refactor Complex Components
1. Break down complex components into atoms and molecules
2. Create organisms for complex UI patterns
3. Maintain existing functionality while improving structure

### Step 3: Update Documentation
1. Update component documentation
2. Create usage examples
3. Update team guidelines

## 🔧 Migration Scripts

### TypeScript Import Updater
```typescript
// Example script to update imports
const importMappings = {
  '@/Components/general/PrimaryButton.vue': '@/Components/atoms',
  '@/Components/general/SecondaryButton.vue': '@/Components/atoms',
  // Add more mappings as needed
}

// Update imports in your files
function updateImports(fileContent: string): string {
  let updatedContent = fileContent
  
  Object.entries(importMappings).forEach(([oldPath, newPath]) => {
    const regex = new RegExp(`import.*from ['"]${oldPath}['"]`, 'g')
    updatedContent = updatedContent.replace(regex, `import { BaseButton } from '${newPath}'`)
  })
  
  return updatedContent
}
```

### Component Usage Updater
```typescript
// Example script to update component usage
const componentMappings = {
  'PrimaryButton': 'BaseButton variant="primary"',
  'SecondaryButton': 'BaseButton variant="secondary"',
  // Add more mappings as needed
}

function updateComponentUsage(template: string): string {
  let updatedTemplate = template
  
  Object.entries(componentMappings).forEach(([oldComponent, newComponent]) => {
    const regex = new RegExp(`<${oldComponent}([^>]*)>`, 'g')
    updatedTemplate = updatedTemplate.replace(regex, `<${newComponent}$1>`)
  })
  
  return updatedTemplate
}
```

## 🧪 Testing Strategy

### Unit Tests
```typescript
// Example test for BaseButton
import { mount } from '@vue/test-utils'
import { BaseButton } from '@/Components/atoms'

describe('BaseButton', () => {
  it('renders with primary variant', () => {
    const wrapper = mount(BaseButton, {
      props: { variant: 'primary' },
      slots: { default: 'Click me' }
    })
    
    expect(wrapper.text()).toBe('Click me')
    expect(wrapper.classes()).toContain('btn--primary')
  })
  
  it('emits click event', async () => {
    const wrapper = mount(BaseButton)
    await wrapper.trigger('click')
    
    expect(wrapper.emitted('click')).toBeTruthy()
  })
})
```

### Integration Tests
```typescript
// Example test for FormPanel organism
import { mount } from '@vue/test-utils'
import { FormPanel } from '@/Components/organisms'
import { FormField } from '@/Components/molecules'

describe('FormPanel', () => {
  it('submits form with field data', async () => {
    const wrapper = mount(FormPanel, {
      slots: {
        default: `
          <FormField v-model="form.name" label="Name" />
          <FormField v-model="form.email" label="Email" />
        `
      }
    })
    
    await wrapper.find('form').trigger('submit')
    expect(wrapper.emitted('submit')).toBeTruthy()
  })
})
```

## 🚨 Common Issues and Solutions

### Issue 1: Styling Conflicts
**Problem:** Existing styles conflict with new atomic components
**Solution:** Use CSS scoping and Tailwind's `@apply` directive

### Issue 2: Prop Naming Changes
**Problem:** Different prop names between old and new components
**Solution:** Create adapter components or update all usages

### Issue 3: Event Handling
**Problem:** Different event names or payloads
**Solution:** Update event handlers and add proper TypeScript types

### Issue 4: Bundle Size
**Problem:** Increased bundle size with new components
**Solution:** Use tree shaking and lazy loading

## 📊 Migration Progress Tracking

Create a migration tracking file:

```markdown
# Migration Progress

## Atoms ✅
- [x] BaseButton
- [x] BaseInput
- [x] BaseCard
- [x] BaseBadge

## Molecules ✅
- [x] FormField
- [x] SearchBar
- [x] StatusIndicator

## Organisms ✅
- [x] DataTable
- [x] FormPanel

## Layouts 🔄
- [ ] Navbar (in progress)
- [ ] MobileNav (pending)
- [ ] UserDropdown (pending)

## Legacy Components 📋
- [ ] PrimaryButton → BaseButton
- [ ] SecondaryButton → BaseButton
- [ ] Modal → Keep as organism
- [ ] ItemCard → Refactor to organism
- [ ] MediaGrid → Refactor to organism
```

## 🎯 Success Metrics

Track these metrics during migration:

1. **Component Reusability**: How many times each component is used
2. **Bundle Size**: Impact on application bundle size
3. **Development Speed**: Time to create new features
4. **Maintenance**: Time to fix bugs or add features
5. **Consistency**: Visual and behavioral consistency across the app

## 📚 Additional Resources

- [Vue 3 Migration Guide](https://v3-migration.vuejs.org/)
- [TypeScript Migration Guide](https://www.typescriptlang.org/docs/handbook/migrating-from-javascript.html)
- [Tailwind CSS Migration](https://tailwindcss.com/docs/upgrading-to-v3)

---

*This migration guide should be updated as you progress through the migration process.* 