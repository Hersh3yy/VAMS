<template>
  <div class="data-table">
    <div class="data-table__header">
      <h3 class="data-table__title">{{ title }}</h3>
      <div class="data-table__actions">
        <SearchBar
          v-if="showSearch"
          v-model="searchQuery"
          placeholder="Search..."
          size="sm"
          @search="$emit('search', $event)"
        />
        <BaseButton
          v-if="showAddButton"
          variant="primary"
          size="sm"
          @click="$emit('add')"
        >
          {{ addButtonText }}
        </BaseButton>
      </div>
    </div>
    
    <div class="data-table__content">
      <div v-if="loading" class="data-table__loading">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white"></div>
        <span class="ml-2">Loading...</span>
      </div>
      
      <div v-else-if="data.length === 0" class="data-table__empty">
        <p class="text-gray-500 dark:text-gray-400">{{ emptyMessage }}</p>
      </div>
      
      <table v-else class="data-table__table">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column.key" class="data-table__th">
              {{ column.label }}
            </th>
            <th v-if="showActions" class="data-table__th">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in data" :key="item.id" class="data-table__tr">
            <td v-for="column in columns" :key="column.key" class="data-table__td">
              <slot :name="`cell-${column.key}`" :item="item" :value="item[column.key]">
                {{ item[column.key] }}
              </slot>
            </td>
            <td v-if="showActions" class="data-table__td">
              <div class="data-table__actions-cell">
                <BaseButton
                  v-if="showEditButton"
                  variant="ghost"
                  size="sm"
                  @click="$emit('edit', item)"
                >
                  Edit
                </BaseButton>
                <BaseButton
                  v-if="showDeleteButton"
                  variant="danger"
                  size="sm"
                  @click="$emit('delete', item)"
                >
                  Delete
                </BaseButton>
                <slot name="actions" :item="item" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div v-if="showPagination && pagination" class="data-table__pagination">
      <slot name="pagination" :pagination="pagination" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { BaseButton } from '@/Components/atoms'
import { SearchBar } from '@/Components/molecules'

interface Column {
  key: string
  label: string
  sortable?: boolean
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface Props {
  title: string
  columns: Column[]
  data: any[]
  loading?: boolean
  showSearch?: boolean
  showAddButton?: boolean
  showEditButton?: boolean
  showDeleteButton?: boolean
  showActions?: boolean
  showPagination?: boolean
  addButtonText?: string
  emptyMessage?: string
  pagination?: Pagination
}

interface Emits {
  (e: 'add'): void
  (e: 'edit', item: any): void
  (e: 'delete', item: any): void
  (e: 'search', query: string): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  showSearch: false,
  showAddButton: false,
  showEditButton: true,
  showDeleteButton: true,
  showActions: true,
  showPagination: false,
  addButtonText: 'Add Item',
  emptyMessage: 'No data available'
})

defineEmits<Emits>()

const searchQuery = ref('')
</script>

<style scoped>
.data-table {
  @apply space-y-4;
}

.data-table__header {
  @apply flex items-center justify-between;
}

.data-table__title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.data-table__actions {
  @apply flex items-center gap-3;
}

.data-table__loading {
  @apply flex items-center justify-center py-8 text-gray-500 dark:text-gray-400;
}

.data-table__empty {
  @apply text-center py-8;
}

.data-table__table {
  @apply min-w-full divide-y divide-gray-200 dark:divide-gray-700;
}

.data-table__th {
  @apply px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider;
}

.data-table__tr {
  @apply hover:bg-gray-50 dark:hover:bg-gray-800;
}

.data-table__td {
  @apply px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300;
}

.data-table__actions-cell {
  @apply flex items-center gap-2;
}

.data-table__pagination {
  @apply flex items-center justify-between;
}
</style> 