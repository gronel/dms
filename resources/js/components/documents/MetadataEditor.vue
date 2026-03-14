<script setup lang="ts">
import { Plus, Trash2 } from 'lucide-vue-next';
import type { DocumentMetadata, MetadataValueType } from '@/types';

type MetadataRow = Pick<DocumentMetadata, 'key' | 'value' | 'value_type'>;

const props = defineProps<{
    modelValue: MetadataRow[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', rows: MetadataRow[]): void;
}>();

const VALUE_TYPES: { value: MetadataValueType; label: string }[] = [
    { value: 'string',  label: 'Text' },
    { value: 'date',    label: 'Date' },
    { value: 'number',  label: 'Number' },
    { value: 'boolean', label: 'Boolean' },
];

function addRow() {
    emit('update:modelValue', [...props.modelValue, { key: '', value: '', value_type: 'string' }]);
}

function removeRow(index: number) {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index));
}

function updateRow(index: number, field: keyof MetadataRow, value: string) {
    const updated = props.modelValue.map((row, i) =>
        i === index ? { ...row, [field]: value } : row
    );
    emit('update:modelValue', updated);
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            v-for="(row, idx) in modelValue"
            :key="idx"
            class="grid grid-cols-[1fr_1fr_auto_auto] items-center gap-2"
        >
            <!-- Key -->
            <input
                :value="row.key"
                type="text"
                placeholder="key (e.g. expiry_date)"
                pattern="[a-z0-9_]+"
                title="Lowercase letters, numbers and underscores only"
                class="rounded-lg border border-sidebar-border bg-transparent px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                @input="updateRow(idx, 'key', ($event.target as HTMLInputElement).value)"
            />

            <!-- Value (changes input type based on value_type) -->
            <input
                v-if="row.value_type !== 'boolean'"
                :value="row.value"
                :type="row.value_type === 'date' ? 'date' : row.value_type === 'number' ? 'number' : 'text'"
                placeholder="value"
                class="rounded-lg border border-sidebar-border bg-transparent px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                @input="updateRow(idx, 'value', ($event.target as HTMLInputElement).value)"
            />
            <select
                v-else
                :value="row.value"
                class="rounded-lg border border-sidebar-border bg-transparent px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                @change="updateRow(idx, 'value', ($event.target as HTMLSelectElement).value)"
            >
                <option value="true">true</option>
                <option value="false">false</option>
            </select>

            <!-- Type selector -->
            <select
                :value="row.value_type"
                class="rounded-lg border border-sidebar-border bg-transparent px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                @change="updateRow(idx, 'value_type', ($event.target as HTMLSelectElement).value)"
            >
                <option v-for="t in VALUE_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>

            <!-- Remove -->
            <button
                type="button"
                class="rounded p-1 text-muted-foreground hover:text-destructive"
                @click="removeRow(idx)"
            >
                <Trash2 class="h-3.5 w-3.5" />
            </button>
        </div>

        <button
            type="button"
            class="flex w-max items-center gap-1.5 rounded-lg border border-dashed border-sidebar-border px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground"
            @click="addRow"
        >
            <Plus class="h-3.5 w-3.5" />
            Add field
        </button>
    </div>
</template>
