<script setup lang="ts">
import { computed } from 'vue';
import {
  useVueTable,
  getCoreRowModel,
  getSortedRowModel,
  type ColumnDef,
  type SortingState,
  type RowSelectionState,
  FlexRender,
} from '@tanstack/vue-table';
import { ref } from 'vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import type { Pagination as PaginationType } from '@/types';
import Pagination from '@/Components/common/Pagination.vue';
import EmptyState from '@/Components/common/EmptyState.vue';

const props = defineProps<{
  columns: ColumnDef<any, any>[];
  data: unknown[];
  meta?: PaginationType;
  loading?: boolean;
  selectable?: boolean;
  emptyTitle?: string;
  emptyDescription?: string;
}>();

const emit = defineEmits<{
  'row-click': [row: unknown];
}>();

const sorting = ref<SortingState>([]);
const rowSelection = ref<RowSelectionState>({});

const enableSelection = computed(() => props.selectable ?? false);

const tableColumns = computed<ColumnDef<any, any>[]>(() => {
  if (!enableSelection.value) return props.columns;

  const selectColumn: ColumnDef<any, any> = {
    id: 'select',
    header: ({ table }) => {
      return h(Checkbox, {
        modelValue: table.getIsAllPageRowsSelected(),
        'onUpdate:modelValue': (val: boolean) => table.toggleAllPageRowsSelected(!!val),
      });
    },
    cell: ({ row }) => {
      return h(Checkbox, {
        modelValue: row.getIsSelected(),
        'onUpdate:modelValue': (val: boolean) => row.toggleSelected(!!val),
      });
    },
    enableSorting: false,
    size: 40,
  };

  return [selectColumn, ...props.columns];
});

const table = useVueTable({
  get data() {
    return props.data;
  },
  get columns() {
    return tableColumns.value;
  },
  state: {
    get sorting() {
      return sorting.value;
    },
    get rowSelection() {
      return rowSelection.value;
    },
  },
  onSortingChange: (updater) => {
    sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
  },
  onRowSelectionChange: (updater) => {
    rowSelection.value = typeof updater === 'function' ? updater(rowSelection.value) : updater;
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  enableRowSelection: enableSelection.value,
});

const selectedRows = computed(() => {
  return table.getSelectedRowModel().rows.map((row) => row.original);
});

import { h } from 'vue';

defineExpose({ selectedRows });
</script>

<template>
  <div>
    <!-- Bulk Actions -->
    <div
      v-if="selectedRows.length > 0"
      class="mb-4 flex items-center gap-3 rounded-lg border border-primary/20 bg-primary-light px-4 py-2.5"
    >
      <span class="text-sm font-medium text-primary">
        {{ selectedRows.length }} selected
      </span>
      <slot name="bulk-actions" :rows="selectedRows" />
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-border bg-white overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow
            v-for="headerGroup in table.getHeaderGroups()"
            :key="headerGroup.id"
          >
            <TableHead
              v-for="header in headerGroup.headers"
              :key="header.id"
              :style="{ width: header.getSize() ? `${header.getSize()}px` : undefined }"
              :class="header.column.getCanSort() ? 'cursor-pointer select-none' : ''"
              @click="header.column.getToggleSortingHandler()?.($event)"
            >
              <div class="flex items-center gap-1.5">
                <FlexRender
                  v-if="!header.isPlaceholder"
                  :render="header.column.columnDef.header"
                  :props="header.getContext()"
                />
                <template v-if="header.column.getCanSort()">
                  <ArrowUp
                    v-if="header.column.getIsSorted() === 'asc'"
                    class="h-3.5 w-3.5 text-primary"
                  />
                  <ArrowDown
                    v-else-if="header.column.getIsSorted() === 'desc'"
                    class="h-3.5 w-3.5 text-primary"
                  />
                  <ArrowUpDown
                    v-else
                    class="h-3.5 w-3.5 text-muted-foreground"
                  />
                </template>
              </div>
            </TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <template v-if="loading">
            <TableRow v-for="i in 5" :key="i">
              <TableCell
                v-for="j in tableColumns.length"
                :key="j"
              >
                <div class="h-4 bg-gray-100 rounded animate-pulse" :style="{ width: `${60 + Math.random() * 30}%` }" />
              </TableCell>
            </TableRow>
          </template>

          <template v-else-if="table.getRowModel().rows.length === 0">
            <TableRow>
              <TableCell :colspan="tableColumns.length" class="p-0">
                <EmptyState
                  :title="emptyTitle"
                  :description="emptyDescription"
                >
                  <template v-if="$slots['empty-action']" #action>
                    <slot name="empty-action" />
                  </template>
                </EmptyState>
              </TableCell>
            </TableRow>
          </template>

          <template v-else>
            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              :selected="row.getIsSelected()"
              :class="cn($slots['row-click'] && 'cursor-pointer')"
              @click="$emit('row-click', row.original)"
            >
              <TableCell
                v-for="cell in row.getVisibleCells()"
                :key="cell.id"
              >
                <FlexRender
                  :render="cell.column.columnDef.cell"
                  :props="cell.getContext()"
                />
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>

    <!-- Pagination -->
    <Pagination v-if="meta && !loading" :meta="meta" />
  </div>
</template>
