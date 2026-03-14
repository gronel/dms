<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FolderPlus, FilePlus2, FolderOpen, ChevronRight } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import DocumentCard from '@/components/documents/DocumentCard.vue';
import type { BreadcrumbItem, Document, Folder } from '@/types';

const props = defineProps<{
    documents: Document[];
    folders: Folder[];
    folder: Folder | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documents', href: '/documents' },
    ...(props.folder ? [{ title: props.folder.name, href: `/folders/${props.folder.id}` }] : []),
];

// New folder inline form
const showFolderForm = ref(false);
const folderForm = useForm({ name: '', parent_id: props.folder?.id ?? null });

function createFolder() {
    folderForm.post('/folders', {
        onSuccess: () => {
            folderForm.reset();
            showFolderForm.value = false;
        },
    });
}
</script>

<template>
    <Head :title="folder ? folder.name : 'Documents'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Link href="/documents" class="hover:text-foreground">All Documents</Link>
                    <template v-if="folder">
                        <ChevronRight class="h-3.5 w-3.5" />
                        <span class="font-medium text-foreground">{{ folder.name }}</span>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg border border-sidebar-border bg-sidebar px-3 py-2 text-sm hover:bg-sidebar/70"
                        @click="showFolderForm = !showFolderForm"
                    >
                        <FolderPlus class="h-4 w-4" />
                        New Folder
                    </button>

                    <Link
                        :href="`/documents/create${folder ? '?folder_id=' + folder.id : ''}`"
                        class="flex items-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-sm text-primary-foreground hover:bg-primary/90"
                    >
                        <FilePlus2 class="h-4 w-4" />
                        Upload Document
                    </Link>
                </div>
            </div>

            <!-- Inline new-folder form -->
            <form
                v-if="showFolderForm"
                class="flex items-center gap-2 rounded-xl border border-dashed border-sidebar-border bg-sidebar/40 p-3"
                @submit.prevent="createFolder"
            >
                <input
                    v-model="folderForm.name"
                    type="text"
                    placeholder="Folder name"
                    autofocus
                    class="flex-1 rounded-lg border border-sidebar-border bg-transparent px-3 py-1.5 text-sm outline-none focus:ring-1 focus:ring-primary"
                />
                <button
                    type="submit"
                    :disabled="folderForm.processing || !folderForm.name"
                    class="rounded-lg bg-primary px-3 py-1.5 text-sm text-primary-foreground disabled:opacity-50"
                >
                    Create
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground"
                    @click="showFolderForm = false"
                >
                    Cancel
                </button>
            </form>

            <!-- Sub-folders -->
            <section v-if="folders.length > 0">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Folders</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <Link
                        v-for="f in folders"
                        :key="f.id"
                        :href="`/folders/${f.id}`"
                        class="flex items-center gap-2 rounded-xl border border-sidebar-border bg-sidebar/40 px-3 py-2.5 text-sm hover:bg-sidebar/70 dark:border-sidebar-border"
                    >
                        <FolderOpen class="h-4 w-4 shrink-0 text-amber-500" />
                        <span class="truncate font-medium">{{ f.name }}</span>
                        <span v-if="f.documents_count !== undefined" class="ml-auto text-xs text-muted-foreground">
                            {{ f.documents_count }}
                        </span>
                    </Link>
                </div>
            </section>

            <!-- Documents -->
            <section>
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Documents</h2>

                <div v-if="documents.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <DocumentCard v-for="doc in documents" :key="doc.id" :document="doc" />
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border py-16 text-center text-muted-foreground"
                >
                    <FilePlus2 class="mb-3 h-10 w-10 opacity-30" />
                    <p class="text-sm">No documents yet.</p>
                    <Link
                        :href="`/documents/create${folder ? '?folder_id=' + folder.id : ''}`"
                        class="mt-2 text-sm text-primary hover:underline"
                    >
                        Upload your first document
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
