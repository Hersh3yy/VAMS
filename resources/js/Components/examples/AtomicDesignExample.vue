<template>
  <div class="atomic-design-example">
    <h1 class="text-2xl font-bold mb-6">Atomic Design System Example</h1>
    
    <!-- Atoms Section -->
    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-4">Atoms</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <BaseCard>
          <h3 class="font-medium mb-2">BaseButton</h3>
          <div class="space-y-2">
            <BaseButton variant="primary" size="sm">Primary Small</BaseButton>
            <BaseButton variant="secondary" size="md">Secondary Medium</BaseButton>
            <BaseButton variant="danger" size="lg">Danger Large</BaseButton>
            <BaseButton variant="ghost" :loading="true">Loading</BaseButton>
          </div>
        </BaseCard>
        
        <BaseCard>
          <h3 class="font-medium mb-2">BaseInput</h3>
          <div class="space-y-2">
            <BaseInput v-model="inputValue" placeholder="Enter text..." />
            <BaseInput v-model="emailValue" type="email" placeholder="Email" />
            <BaseInput v-model="errorValue" error="This field is required" />
          </div>
        </BaseCard>
        
        <BaseCard>
          <h3 class="font-medium mb-2">BaseCard</h3>
          <div class="space-y-2">
            <BaseCard variant="default" padding="sm">Default Card</BaseCard>
            <BaseCard variant="elevated" padding="sm">Elevated Card</BaseCard>
            <BaseCard variant="outlined" padding="sm">Outlined Card</BaseCard>
          </div>
        </BaseCard>
        
        <BaseCard>
          <h3 class="font-medium mb-2">BaseBadge</h3>
          <div class="space-y-2">
            <BaseBadge variant="success">Success</BaseBadge>
            <BaseBadge variant="warning">Warning</BaseBadge>
            <BaseBadge variant="danger">Danger</BaseBadge>
            <BaseBadge variant="info" rounded>Info Rounded</BaseBadge>
          </div>
        </BaseCard>
      </div>
    </section>
    
    <!-- Molecules Section -->
    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-4">Molecules</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <BaseCard>
          <h3 class="font-medium mb-4">FormField</h3>
          <div class="space-y-4">
            <FormField
              v-model="formFieldValue"
              label="Email Address"
              type="email"
              placeholder="Enter your email"
              required
              help-text="We'll never share your email"
            />
            <FormField
              v-model="formFieldError"
              label="Username"
              error="Username is already taken"
            />
          </div>
        </BaseCard>
        
        <BaseCard>
          <h3 class="font-medium mb-4">SearchBar</h3>
          <div class="space-y-4">
            <SearchBar
              v-model="searchValue"
              placeholder="Search items..."
              @search="handleSearch"
            />
            <SearchBar
              v-model="searchWithButton"
              placeholder="Search with button..."
              :show-button="true"
              @search="handleSearch"
            />
          </div>
        </BaseCard>
      </div>
    </section>
    
    <!-- Organisms Section -->
    <section class="mb-8">
      <h2 class="text-xl font-semibold mb-4">Organisms</h2>
      <div class="space-y-6">
        <DataTable
          title="Sample Data Table"
          :columns="tableColumns"
          :data="tableData"
          :show-search="true"
          :show-add-button="true"
          @add="handleAdd"
          @edit="handleEdit"
          @delete="handleDelete"
          @search="handleTableSearch"
        />
        
        <FormPanel
          title="Sample Form"
          description="This is an example form using the Atomic Design system"
          @submit="handleFormSubmit"
          @cancel="handleFormCancel"
        >
          <FormField
            v-model="formData.name"
            label="Name"
            placeholder="Enter your name"
            required
          />
          <FormField
            v-model="formData.email"
            label="Email"
            type="email"
            placeholder="Enter your email"
            required
          />
          <FormField
            v-model="formData.message"
            label="Message"
            placeholder="Enter your message"
          />
        </FormPanel>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { BaseButton, BaseInput, BaseCard, BaseBadge } from '@/Components/atoms'
import { FormField, SearchBar } from '@/Components/molecules'
import { DataTable, FormPanel } from '@/Components/organisms'

// Reactive data
const inputValue = ref('')
const emailValue = ref('')
const errorValue = ref('')
const formFieldValue = ref('')
const formFieldError = ref('')
const searchValue = ref('')
const searchWithButton = ref('')
const formData = ref({
  name: '',
  email: '',
  message: ''
})

// Table data
const tableColumns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Status' }
]

const tableData = ref([
  { id: 1, name: 'John Doe', email: 'john@example.com', status: 'Active' },
  { id: 2, name: 'Jane Smith', email: 'jane@example.com', status: 'Inactive' },
  { id: 3, name: 'Bob Johnson', email: 'bob@example.com', status: 'Active' }
])

// Event handlers
const handleSearch = (query: string) => {
  console.log('Search query:', query)
}

const handleAdd = () => {
  console.log('Add button clicked')
}

const handleEdit = (item: any) => {
  console.log('Edit item:', item)
}

const handleDelete = (item: any) => {
  console.log('Delete item:', item)
}

const handleTableSearch = (query: string) => {
  console.log('Table search:', query)
}

const handleFormSubmit = () => {
  console.log('Form submitted:', formData.value)
}

const handleFormCancel = () => {
  console.log('Form cancelled')
  formData.value = { name: '', email: '', message: '' }
}
</script>

<style scoped>
.atomic-design-example {
  @apply max-w-7xl mx-auto p-6;
}
</style> 