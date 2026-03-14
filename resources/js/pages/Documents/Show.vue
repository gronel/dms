<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ClipboardList, Edit2, Trash2, FolderOpen, History, Tag as TagIcon, Database, Share2, Lock, LockOpen, MessageSquare } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import VersionTimeline from '@/components/documents/VersionTimeline.vue';
import ShareModal from '@/components/documents/ShareModal.vue';
import CommentsSection from '@/components/documents/CommentsSection.vue';
import type { BreadcrumbItem, Document } from '@/types';

const props = defineProps<{
    document: Document;
}>();

const page = usePage();
const currentUserId = computed(() => (page.props.auth as { user: { id: number } }).user.id);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documents', href: '/documents' },
    ...(props.document.folder ? [{ title: props.document.folder.name, href: `/folders/${props.document.folder.id}` }] : []),
    { title: props.document.title, href: `/documents/${props.document.ulid}` },
];

const shareModalOpen = ref(false);
const deleteForm = useForm({});
const lockForm = useForm({});

function deleteDocument() {
    if (!confirm('Delete this document? This action can be undone by an admin.')) return;
    deleteForm.delete(`/documents/${props.document.ulid}`);
}

function toggleLock() {
    if (props.document.is_locked) {
        lockForm.delete(`/documents/${props.document.ulid}/lock`);
    } else {
        lockForm.post(`/documents/${props.document.ulid}/lock`);
    }
}

const statusClass = computed(() => ({
    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': props.document.status === 'published',
    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': props.document.status === 'draft',
    'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': props.document.status === 'archived',
}));

const downloadUrl = computed(() =>
    props.document.current_version
        ? `/documents/${props.document.ulid}/versions/${props.document.current_version.id}/download`
        : null
);
</script>

<template>
    <Head :title="document.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-4">

            <!-- Document header -->
            <div class="flex flex-col gap-4 rounded-xl border border-sidebar-border bg-sidebar/40 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl font-semibold leading-tight">{{ document.title }}</h1>
                        <p v-if="document.description" class="mt-1 text-sm text-muted-foreground">
                            {{ document.description }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Lock / Unlock (owner only) -->
                        <button
                            type="button"
                            :disabled="lockForm.processing"
                            :title="document.is_locked ? 'Unlock document' : 'Lock document'"
                            class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50"
                            :class="document.is_locked
                                ? 'border-yellow-400/50 text-yellow-600 hover:bg-yellow-50 dark:text-yellow-400'
                                : 'border-sidebar-border hover:bg-muted'"
                            @click="toggleLock"
                        >
                            <Lock v-if="document.is_locked" class="h-3.5 w-3.5" />
                            <LockOpen v-else class="h-3.5 w-3.5" />
                            {{ document.is_locked ? 'Unlock' : 'Lock' }}
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-lg border border-sidebar-border px-3 py-1.5 text-sm hover:bg-muted"
                            @click="shareModalOpen = true"
                        >
                            <Share2 class="h-3.5 w-3.5" />
                            Share
                        </button>
                        <Link
                            :href="`/documents/${document.ulid}/audit`"
                            class="flex items-center gap-1.5 rounded-lg border border-sidebar-border px-3 py-1.5 text-sm hover:bg-muted"
                        >
                            <ClipboardList class="h-3.5 w-3.5" />
                            Audit
                        </Link>
                        <Link
                            :href="`/documents/${document.ulid}/edit`"
                            class="flex items-center gap-1.5 rounded-lg border border-sidebar-border px-3 py-1.5 text-sm hover:bg-muted"
                        >
                            <Edit2 class="h-3.5 w-3.5" />
                            Edit
                        </Link>
                        <button
                            type="button"
                            :disabled="deleteForm.processing"
                            class="flex items-center gap-1.5 rounded-lg border border-destructive/30 px-3 py-1.5 text-sm text-destructive hover:bg-destructive/5 disabled:opacity-50"
                            @click="deleteDocument"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete
                        </button>
                    </div>
                </div>

                <!-- Meta -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
                    <span class="rounded-full px-2 py-0.5 font-medium capitalize" :class="statusClass">
                        {{ document.status }}
                    </span>

                    <span
                        v-if="document.is_locked"
                        class="flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-0.5 font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"
                    >
                        <Lock class="h-3 w-3" /> Locked
                    </span>

                    <span v-if="document.folder" class="flex items-center gap-1">
                        <FolderOpen class="h-3.5 w-3.5" />
                        {{ document.folder.name }}
                    </span>

                    <span>Owned by {{ document.owner?.name }}</span>

                    <span>Updated {{ new Date(document.updated_at).toLocaleDateString() }}</span>
                </div>

                <!-- Tags -->
                <div v-if="document.tags && document.tags.length > 0" class="flex flex-wrap gap-1.5">
                    <span
                        v-for="tag in document.tags"
                        :key="tag.id"
                        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-white"
                        :style="{ backgroundColor: tag.color ?? '#6b7280' }"
                    >
                        <TagIcon class="h-3 w-3" />
                        {{ tag.name }}
                    </span>
                </div>

                <!-- Current version info + download -->
                <div
                    v-if="document.current_version"
                    class="flex items-center justify-between gap-4 rounded-lg border border-sidebar-border bg-sidebar px-4 py-3"
                >
                    <div class="text-sm">
                        <span class="font-medium">{{ document.current_version.file_name }}</span>
                        <span class="ml-2 text-muted-foreground">
                            v{{ document.current_version.version_number }}
                            &middot; {{ document.current_version.formatted_size }}
                            &middot; {{ document.current_version.mime_type }}
                        </span>
                    </div>
                    <a
                        v-if="downloadUrl"
                        :href="downloadUrl"
                        class="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Download
                    </a>
                </div>
            </div>

            <!-- Version History -->
            <div class="mt-8">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                    <History class="h-4 w-4" />
                    Version History
                </h2>
                <VersionTimeline
                    :document="document"
                    :versions="document.versions ?? []"
                />
            </div>

            <!-- Custom Metadata -->
            <div v-if="document.metadata && document.metadata.length > 0" class="mt-8">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                    <Database class="h-4 w-4" />
                    Custom Fields
                </h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
                    <div v-for="m in document.metadata" :key="m.id" class="flex flex-col gap-0.5">
                        <dt class="text-xs font-medium text-muted-foreground">{{ m.key }}</dt>
                        <dd class="text-sm">{{ m.value }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Comments -->
        <div class="mx-auto max-w-3xl px-4 pb-8">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                <MessageSquare class="h-4 w-4" />
                Comments
            </h2>
            <CommentsSection :document="document" :current-user-id="currentUserId" />
        </div>

        <ShareModal v-model="shareModalOpen" :document="document" />
    </AppLayout>
</template>
