<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, FolderOpen, Lock } from 'lucide-vue-next';
import type { Document } from '@/types';

defineProps<{
    document: Document;
}>();
</script>

<template>
    <div
        class="group relative flex flex-col gap-2 rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-sidebar-border dark:bg-sidebar"
    >
        <!-- Lock badge -->
        <span
            v-if="document.is_locked"
            class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
        >
            <Lock class="h-3 w-3" />
            Locked
        </span>

        <!-- Icon + Title -->
        <div class="flex items-start gap-3">
            <FileText class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
            <div class="min-w-0">
                <Link
                    :href="`/documents/${document.ulid}`"
                    class="line-clamp-2 font-medium text-foreground hover:underline"
                >
                    {{ document.title }}
                </Link>
                <p v-if="document.description" class="mt-0.5 line-clamp-1 text-xs text-muted-foreground">
                    {{ document.description }}
                </p>
            </div>
        </div>

        <!-- Meta row -->
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <!-- Status badge -->
            <span
                class="rounded-full px-2 py-0.5 font-medium capitalize"
                :class="{
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': document.status === 'published',
                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': document.status === 'draft',
                    'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': document.status === 'archived',
                }"
            >
                {{ document.status }}
            </span>

            <span v-if="document.current_version">
                v{{ document.current_version.version_number }}
                &middot;
                {{ document.current_version.formatted_size }}
            </span>

            <span v-if="document.folder" class="flex items-center gap-1">
                <FolderOpen class="h-3 w-3" />
                {{ document.folder.name }}
            </span>
        </div>

        <!-- Tags -->
        <div v-if="document.tags && document.tags.length > 0" class="flex flex-wrap gap-1">
            <span
                v-for="tag in document.tags"
                :key="tag.id"
                class="rounded-full px-1.5 py-0.5 text-xs text-white"
                :style="{ backgroundColor: tag.color ?? '#6b7280' }"
            >
                {{ tag.name }}
            </span>
        </div>
    </div>
</template>
