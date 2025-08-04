# 🧬 Atomic Design System for VAMS

A comprehensive guide to the Atomic Design methodology implementation in your Laravel Inertia + Vue 3 project.

## 🎯 Overview

This Atomic Design system is specifically tailored for Laravel Inertia applications, providing a structured approach to component development that promotes reusability, consistency, and maintainability.

## 🏗️ Architecture

### Component Hierarchy
```
Pages (Specific instances)
├── Layouts (Page layouts - Inertia convention)
    ├── Organisms (Complex components)
        ├── Molecules (Simple combinations)
            └── Atoms (Basic building blocks)
```

### Directory Structure
```
resources/js/Components/
├── atoms/                    # Basic building blocks
│   ├── BaseButton.vue       # Button with variants
│   ├── BaseInput.vue        # Input with validation
│   ├── BaseCard.vue         # Card container
│   ├── BaseBadge.vue        # Status badge
│   └── index.ts             # Exports
├── molecules/               # Simple combinations
│   ├── FormField.vue        # Label + Input
│   ├── SearchBar.vue        # Input + Button
│   ├── StatusIndicator.vue  # Status with icon
│   └── index.ts             # Exports
├── organisms/               # Complex components
│   ├── DataTable.vue        # Table with controls
│   ├── FormPanel.vue        # Form with validation
│   └── index.ts             # Exports
├── examples/                # Demo components
│   └── AtomicDesignExample.vue # Interactive demo
└── ATOMIC_DESIGN_GUIDE.md   # This guide
```

## 🎨 Design Tokens

### Colors (Tailwind CSS)
```css
/* Primary Colors */
--color-primary: #3b82f6 (blue-500)
--color-primary-dark: #1d4ed8 (blue-700)
--color-primary-light: #93c5fd (blue-300)

/* Semantic Colors */
--color-success: #10b981 (emerald-500)
--color-warning: #f59e0b (amber-500)
--color-danger: #ef4444 (red-500)
--color-info: #6366f1 (indigo-500)

/* Neutral Colors */
--color-gray-50: #f9fafb
--color-gray-500: #6b7280
--color-gray-900: #111827
```

### Typography
```css
/* Font Sizes */
--text-xs: 0.75rem
--text-sm: 0.875rem
--text-base: 1rem
--text-lg: 1.125rem
--text-xl: 1.25rem

/* Font Weights */
--font-normal: 400
--font-medium: 500
--font-semibold: 600
--font-bold: 700
```

### Spacing
```css
/* Spacing Scale */
--space-1: 0.25rem
--space-2: 0.5rem
--space-3: 0.75rem
--space-4: 1rem
--space-6: 1.5rem
--space-8: 2rem
--space-12: 3rem
```

## 🧩 Atoms

### BaseButton
The fundamental button component with multiple variants and states.

```vue
<BaseButton
  variant="primary"
  size="md"
  :loading="false"
  :disabled="false"
  type="button"
  @click="handleClick"
>
  Button Text
</BaseButton>
```

**Props:**
- `variant`: 'primary' | 'secondary' | 'danger' | 'ghost'
- `size`: 'sm' | 'md' | 'lg'
- `loading`: boolean
- `disabled`: boolean
- `type`: 'button' | 'submit' | 'reset'

### BaseInput
A flexible input component with validation support.

```vue
<BaseInput
  v-model="value"
  type="text"
  placeholder="Enter text..."
  :error="errorMessage"
  :disabled="false"
  size="md"
  @blur="handleBlur"
  @focus="handleFocus"
/>
```

**Props:**
- `modelValue`: string
- `type`: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
- `placeholder`: string
- `error`: string
- `disabled`: boolean
- `size`: 'sm' | 'md' | 'lg'

### BaseCard
A container component for grouping content.

```vue
<BaseCard
  variant="default"
  padding="md"
  :hover="true"
  :clickable="false"
>
  Card content
</BaseCard>
```

**Props:**
- `variant`: 'default' | 'elevated' | 'outlined' | 'flat'
- `padding`: 'none' | 'sm' | 'md' | 'lg'
- `hover`: boolean
- `clickable`: boolean

### BaseBadge
A status indicator component.

```vue
<BaseBadge
  variant="success"
  size="md"
  :rounded="false"
>
  Status Text
</BaseBadge>
```

**Props:**
- `variant`: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
- `size`: 'sm' | 'md' | 'lg'
- `rounded`: boolean

## 🧬 Molecules

### FormField
Combines BaseInput with label and error handling.

```vue
<FormField
  v-model="email"
  label="Email Address"
  type="email"
  placeholder="Enter your email"
  :required="true"
  :error="emailError"
  help-text="We'll never share your email"
/>
```

### SearchBar
Combines BaseInput with search functionality and optional button.

```vue
<SearchBar
  v-model="searchQuery"
  placeholder="Search items..."
  :show-button="true"
  button-text="Search"
  @search="handleSearch"
/>
```

### StatusIndicator
Combines BaseBadge with an icon for enhanced status display.

```vue
<StatusIndicator
  variant="success"
  :icon="CheckIcon"
  :animate="false"
>
  Active
</StatusIndicator>
```

## 🦠 Organisms

### DataTable
A complex table component with search, actions, and pagination.

```vue
<DataTable
  title="Users"
  :columns="columns"
  :data="users"
  :loading="loading"
  :show-search="true"
  :show-add-button="true"
  :show-actions="true"
  @add="handleAdd"
  @edit="handleEdit"
  @delete="handleDelete"
  @search="handleSearch"
>
  <template #cell-status="{ item, value }">
    <BaseBadge :variant="value === 'Active' ? 'success' : 'danger'">
      {{ value }}
    </BaseBadge>
  </template>
</DataTable>
```

### FormPanel
A form container with validation and submission handling.

```vue
<FormPanel
  title="Create User"
  description="Add a new user to the system"
  :loading="submitting"
  @submit="handleSubmit"
  @cancel="handleCancel"
>
  <FormField
    v-model="form.name"
    label="Name"
    required
  />
  <FormField
    v-model="form.email"
    label="Email"
    type="email"
    required
  />
</FormPanel>
```

## 🚀 Usage Examples

### Basic Form Implementation
```vue
<template>
  <FormPanel
    title="Contact Form"
    @submit="handleSubmit"
  >
    <FormField
      v-model="form.name"
      label="Full Name"
      placeholder="Enter your full name"
      required
    />
    <FormField
      v-model="form.email"
      label="Email Address"
      type="email"
      placeholder="Enter your email"
      required
    />
    <FormField
      v-model="form.message"
      label="Message"
      placeholder="Enter your message"
    />
  </FormPanel>
</template>

<script setup lang="ts">
import { FormPanel, FormField } from '@/Components/organisms'

const form = ref({
  name: '',
  email: '',
  message: ''
})

const handleSubmit = () => {
  // Handle form submission
  console.log('Form submitted:', form.value)
}
</script>
```

### Data Table with Custom Cells
```vue
<template>
  <DataTable
    title="Product Inventory"
    :columns="columns"
    :data="products"
    :show-search="true"
    :show-add-button="true"
    @add="handleAddProduct"
    @edit="handleEditProduct"
    @delete="handleDeleteProduct"
  >
    <template #cell-price="{ value }">
      ${{ value.toFixed(2) }}
    </template>
    <template #cell-status="{ value }">
      <BaseBadge :variant="value === 'In Stock' ? 'success' : 'danger'">
        {{ value }}
      </BaseBadge>
    </template>
  </DataTable>
</template>

<script setup lang="ts">
import { DataTable, BaseBadge } from '@/Components/organisms'

const columns = [
  { key: 'name', label: 'Product Name' },
  { key: 'price', label: 'Price' },
  { key: 'status', label: 'Status' }
]

const products = ref([
  { id: 1, name: 'Product A', price: 29.99, status: 'In Stock' },
  { id: 2, name: 'Product B', price: 49.99, status: 'Out of Stock' }
])
</script>
```

## 🎨 Styling Guidelines

### CSS Classes
- Use Tailwind CSS utility classes for styling
- Follow BEM methodology for custom CSS
- Maintain consistent spacing and typography
- Support both light and dark themes

### Component Styling
```vue
<style scoped>
.component-name {
  @apply base-classes;
}

.component-name__element {
  @apply element-classes;
}

.component-name--modifier {
  @apply modifier-classes;
}
</style>
```

### Dark Mode Support
All components include dark mode variants using Tailwind's `dark:` prefix:

```css
.class-name {
  @apply bg-white text-gray-900 dark:bg-gray-800 dark:text-white;
}
```

## 🔧 Development Workflow

### 1. Component Creation
1. Determine the appropriate level (atom, molecule, organism)
2. Create the component file with proper TypeScript interfaces
3. Add comprehensive prop validation and defaults
4. Include accessibility features (ARIA labels, keyboard navigation)
5. Add to the appropriate index.ts export file

### 2. Testing Guidelines
- Test all component variants and states
- Verify prop validation and error handling
- Check event emissions and data flow
- Test responsive behavior and accessibility
- Include integration tests for complex organisms

### 3. Documentation
- Add JSDoc comments for all props and methods
- Create usage examples and demos
- Update this guide when adding new components
- Include accessibility considerations

## 🎯 Best Practices

### Component Design
- Keep components small and focused
- Use composition over inheritance
- Implement consistent error handling
- Provide meaningful default values
- Support both controlled and uncontrolled modes

### State Management
- Use props for data flow down
- Use events for data flow up
- Avoid prop drilling with provide/inject
- Keep component state local when possible
- Use composables for shared logic

### Performance
- Use `v-memo` for expensive computations
- Implement proper key strategies for lists
- Lazy load heavy components
- Optimize bundle size with code splitting
- Use virtual scrolling for large datasets

### Accessibility
- Include proper ARIA labels and roles
- Support keyboard navigation
- Ensure color contrast compliance
- Provide alternative text for images
- Test with screen readers

## 🔮 Migration Guide

### From Existing Components
To migrate your existing components to the Atomic Design system:

1. **Identify Component Level**
   - `general/` components → `atoms/`
   - `shared/` components → `molecules/` or `organisms/`
   - `layout/` components → keep as layouts

2. **Refactor Components**
   - Add proper TypeScript interfaces
   - Implement consistent prop naming
   - Add comprehensive validation
   - Include accessibility features

3. **Update Imports**
   ```typescript
   // Old
   import PrimaryButton from '@/Components/general/PrimaryButton.vue'
   
   // New
   import { BaseButton } from '@/Components/atoms'
   ```

4. **Update Usage**
   ```vue
   <!-- Old -->
   <PrimaryButton>Click me</PrimaryButton>
   
   <!-- New -->
   <BaseButton variant="primary">Click me</BaseButton>
   ```

## 📚 Resources

- [Atomic Design by Brad Frost](https://atomicdesign.bradfrost.com/)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Laravel Inertia Documentation](https://inertiajs.com/)

## 🤝 Contributing

When contributing to the Atomic Design system:

1. **Follow the hierarchy** - Build from atoms up to organisms
2. **Maintain consistency** - Use established patterns and conventions
3. **Document thoroughly** - Include examples and use cases
4. **Test comprehensively** - Cover all variants and edge cases
5. **Consider accessibility** - Ensure inclusive design
6. **Optimize performance** - Minimize bundle impact

---

*This guide provides a foundation for implementing atomic design in your Laravel Inertia project. Adapt and extend based on your specific needs and requirements.* 