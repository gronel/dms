<script setup lang="ts">
import { ref, computed } from 'vue';
import { Upload, X, FileText } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    modelValue: File | null;
    accept?: string;
    maxMb?: number;
}>(), {
    accept: '*/*',
    maxMb: 100,
});

const emit = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
    (e: 'error', message: string): void;
}>();

const dragging = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);

const previewName = computed(() => props.modelValue?.name ?? null);
const previewSize = computed(() => {
    if (!props.modelValue) return null;
    const bytes = props.modelValue.size;
    if (bytes < 1_048_576) return `${(bytes / 1_024).toFixed(1)} KB`;
    return `${(bytes / 1_048_576).toFixed(1)} MB`;
});

function onInputChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    validate(file);
}

function onDrop(event: DragEvent) {
    dragging.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    validate(file);
}

function validate(file: File | null) {
    if (!file) return;
    if (file.size > props.maxMb * 1_048_576) {
        emit('error', `File exceeds the ${props.maxMb} MB limit.`);
        return;
    }
    emit('update:modelValue', file);
}

function clear() {
    emit('update:modelValue', null);
    if (inputRef.value) inputRef.value.value = '';
}
</script>

<template>
    <div>
        <!-- Drop zone (shown when no file selected) -->
        <div
            v-if="!modelValue"
            class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-sidebar-border bg-sidebar/40 p-8 text-center transition hover:bg-sidebar/70"
            :class="{ 'border-primary bg-primary/5': dragging }"
            @click="inputRef?.click()"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <Upload class="h-8 w-8 text-muted-foreground" />
            <div>
                <p class="text-sm font-medium">Drop a file here or <span class="text-primary">browse</span></p>
                <p class="mt-1 text-xs text-muted-foreground">Max {{ maxMb }} MB</p>
            </div>
            <input
                ref="inputRef"
                type="file"
                class="sr-only"
                :accept="accept"
                @change="onInputChange"
            />
        </div>

        <!-- Selected file preview -->
        <div
            v-else
            class="flex items-center gap-3 rounded-xl border border-sidebar-border bg-sidebar/40 px-4 py-3"
        >
            <FileText class="h-5 w-5 shrink-0 text-muted-foreground" />
            <div class="flex-1 min-w-0">
                <p class="truncate text-sm font-medium">{{ previewName }}</p>
                <p class="text-xs text-muted-foreground">{{ previewSize }}</p>
            </div>
            <button
                type="button"
                class="ml-2 rounded p-1 text-muted-foreground hover:text-destructive"
                @click="clear"
            >
                <X class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
