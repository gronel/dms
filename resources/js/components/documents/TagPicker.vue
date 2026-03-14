<script setup lang="ts">
import { ref, computed } from 'vue';
import { X, Plus, Tag as TagIcon } from 'lucide-vue-next';
import type { Tag } from '@/types';

const props = defineProps<{
    modelValue: number[];   // selected tag IDs
    allTags: Tag[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', ids: number[]): void;
}>();

// New tag creation state
const showNewTagInput = ref(false);
const newTagName = ref('');
const newTagColor = ref('#6b7280');
const creating = ref(false);

const selectedTags = computed(() =>
    props.allTags.filter((t) => props.modelValue.includes(t.id))
);

const availableTags = computed(() =>
    props.allTags.filter((t) => !props.modelValue.includes(t.id))
);

function toggle(tag: Tag) {
    const ids = props.modelValue.includes(tag.id)
        ? props.modelValue.filter((id) => id !== tag.id)
        : [...props.modelValue, tag.id];
    emit('update:modelValue', ids);
}

async function createTag() {
    if (!newTagName.value.trim()) return;
    creating.value = true;
    try {
        const res = await fetch('/tags', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name: newTagName.value.trim(), color: newTagColor.value }),
        });
        if (res.ok) {
            const tag: Tag = await res.json();
            // Emit new selected list including the new tag
            emit('update:modelValue', [...props.modelValue, tag.id]);
            // Emit an event so the parent can push the tag into allTags
            emit('tagCreated' as never, tag as never);
        }
    } finally {
        newTagName.value = '';
        newTagColor.value = '#6b7280';
        showNewTagInput.value = false;
        creating.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-2">

        <!-- Selected tags -->
        <div v-if="selectedTags.length > 0" class="flex flex-wrap gap-1.5">
            <button
                v-for="tag in selectedTags"
                :key="tag.id"
                type="button"
                class="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-white transition hover:opacity-80"
                :style="{ backgroundColor: tag.color ?? '#6b7280' }"
                @click="toggle(tag)"
            >
                {{ tag.name }}
                <X class="h-3 w-3" />
            </button>
        </div>

        <!-- Tag picker dropdown -->
        <div class="flex flex-wrap gap-1.5">
            <button
                v-for="tag in availableTags"
                :key="tag.id"
                type="button"
                class="flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium transition hover:brightness-95"
                :style="{ borderColor: tag.color ?? '#6b7280', color: tag.color ?? '#6b7280' }"
                @click="toggle(tag)"
            >
                <TagIcon class="h-3 w-3" />
                {{ tag.name }}
            </button>

            <button
                type="button"
                class="flex items-center gap-1 rounded-full border border-dashed border-sidebar-border px-2 py-0.5 text-xs text-muted-foreground hover:text-foreground"
                @click="showNewTagInput = !showNewTagInput"
            >
                <Plus class="h-3 w-3" />
                New tag
            </button>
        </div>

        <!-- Inline new-tag form -->
        <div
            v-if="showNewTagInput"
            class="flex items-center gap-2 rounded-lg border border-dashed border-sidebar-border bg-sidebar/40 p-2"
        >
            <input
                v-model="newTagColor"
                type="color"
                class="h-6 w-6 cursor-pointer rounded border-none bg-transparent p-0"
                title="Tag color"
            />
            <input
                v-model="newTagName"
                type="text"
                placeholder="Tag name"
                autofocus
                class="flex-1 rounded border border-sidebar-border bg-transparent px-2 py-1 text-xs outline-none focus:ring-1 focus:ring-primary"
                @keydown.enter.prevent="createTag"
                @keydown.escape.prevent="showNewTagInput = false"
            />
            <button
                type="button"
                :disabled="creating || !newTagName.trim()"
                class="rounded bg-primary px-2 py-1 text-xs text-primary-foreground disabled:opacity-50"
                @click="createTag"
            >
                Add
            </button>
        </div>
    </div>
</template>
