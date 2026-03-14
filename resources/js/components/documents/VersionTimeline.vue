<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { RotateCcw, Download } from 'lucide-vue-next';
import type { Document, DocumentVersion } from '@/types';

const props = defineProps<{
    document: Document;
    versions: DocumentVersion[];
}>();

function downloadUrl(version: DocumentVersion): string {
    return `/documents/${props.document.ulid}/versions/${version.id}/download`;
}

const restoreForm = useForm({});

function restore(version: DocumentVersion) {
    restoreForm.post(`/documents/${props.document.ulid}/versions/${version.id}/restore`);
}
</script>

<template>
    <div class="flow-root">
        <ul class="-mb-8">
            <li v-for="(version, idx) in versions" :key="version.id" class="relative pb-8">
                <!-- Connector line -->
                <span
                    v-if="idx < versions.length - 1"
                    class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-border"
                    aria-hidden="true"
                />

                <div class="relative flex items-start gap-4">
                    <!-- Version badge -->
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-2 ring-white dark:ring-sidebar"
                        :class="version.is_current
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground'"
                    >
                        v{{ version.version_number }}
                    </div>

                    <div class="flex flex-1 flex-col gap-1">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <span class="text-sm font-medium">{{ version.file_name }}</span>
                                <span
                                    v-if="version.is_current"
                                    class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                >
                                    Current
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="downloadUrl(version)"
                                    class="flex items-center gap-1 rounded px-2 py-1 text-xs text-muted-foreground hover:bg-muted hover:text-foreground"
                                >
                                    <Download class="h-3.5 w-3.5" />
                                    Download
                                </a>
                                <button
                                    v-if="!version.is_current"
                                    type="button"
                                    :disabled="restoreForm.processing"
                                    class="flex items-center gap-1 rounded px-2 py-1 text-xs text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                                    @click="restore(version)"
                                >
                                    <RotateCcw class="h-3.5 w-3.5" />
                                    Restore
                                </button>
                            </div>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            {{ version.formatted_size }}
                            &middot;
                            <span v-if="version.uploader">by {{ version.uploader.name }}</span>
                            &middot;
                            {{ new Date(version.created_at).toLocaleDateString() }}
                        </p>

                        <p v-if="version.change_summary" class="text-xs italic text-muted-foreground">
                            "{{ version.change_summary }}"
                        </p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
