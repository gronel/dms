<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Upload } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import DocumentUploader from '@/components/documents/DocumentUploader.vue';
import TagPicker from '@/components/documents/TagPicker.vue';
import MetadataEditor from '@/components/documents/MetadataEditor.vue';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem, Document, Folder, Tag, DocumentMetadata } from '@/types';

const props = defineProps<{
    folders: Folder[];
    folder_id?: number | string | null;
    document?: Document;
    allTags?: Tag[];
}>();

const isEditing = computed(() => !!props.document);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documents', href: '/documents' },
    { title: isEditing.value ? 'Edit Document' : 'Upload Document', href: '#' },
];

// Local reactive allTags list so newly created tags appear immediately
const localAllTags = ref<Tag[]>(props.allTags ?? []);

type MetadataRow = Pick<DocumentMetadata, 'key' | 'value' | 'value_type'>;

const form = useForm({
    title:          props.document?.title ?? '',
    description:    props.document?.description ?? '',
    folder_id:      props.folder_id ?? props.document?.folder_id ?? null,
    status:         props.document?.status ?? 'draft',
    file:           null as File | null,
    change_summary: '',
    tags:           (props.document?.tags ?? []).map((t) => t.id) as number[],
    metadata:       (props.document?.metadata ?? []).map((m) => ({
        key: m.key, value: m.value, value_type: m.value_type,
    })) as MetadataRow[],
});

function onFileSelected(file: File | null) {
    form.file = file;
}

function onFileError(message: string) {
    form.setError('file', message);
}

function onTagCreated(tag: Tag) {
    localAllTags.value = [...localAllTags.value, tag];
}

function submit() {
    if (isEditing.value && props.document) {
        // PHP does not parse multipart/form-data on PATCH requests, so we must
        // send as POST with _method spoofing (Inertia file-upload limitation).
        form.transform((data) => ({ ...data, _method: 'patch' }))
            .post(`/documents/${props.document.ulid}`, {
                forceFormData: true,
            });
    } else {
        form.post('/documents', {
            forceFormData: true,
        });
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Document' : 'Upload Document'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-4">
            <h1 class="mb-6 text-xl font-semibold">
                {{ isEditing ? 'Edit Document' : 'Upload Document' }}
            </h1>

            <form class="flex flex-col gap-5" @submit.prevent="submit">

                <!-- Title -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" for="title">Title <span class="text-destructive">*</span></label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        required
                        class="rounded-lg border border-sidebar-border bg-transparent px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-primary"
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <!-- Description -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" for="description">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="rounded-lg border border-sidebar-border bg-transparent px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-primary"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Folder -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" for="folder_id">Folder</label>
                    <select
                        id="folder_id"
                        v-model="form.folder_id"
                        class="rounded-lg border border-sidebar-border bg-transparent px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option :value="null">— No folder —</option>
                        <option v-for="f in folders" :key="f.id" :value="f.id">{{ f.name }}</option>
                    </select>
                    <InputError :message="form.errors.folder_id" />
                </div>

                <!-- Status -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" for="status">Status</label>
                    <select
                        id="status"
                        v-model="form.status"
                        class="rounded-lg border border-sidebar-border bg-transparent px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option v-if="isEditing" value="archived">Archived</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>

                <!-- File Upload -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">
                        File <span v-if="!isEditing" class="text-destructive">*</span>
                        <span v-if="isEditing" class="font-normal text-muted-foreground">(leave empty to keep current)</span>
                    </label>
                    <DocumentUploader
                        :model-value="form.file"
                        @update:model-value="onFileSelected"
                        @error="onFileError"
                    />
                    <InputError :message="form.errors.file" />
                </div>

                <!-- Tags -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Tags</label>
                    <TagPicker
                        v-model="form.tags"
                        :all-tags="localAllTags"
                        @tag-created="onTagCreated"
                    />
                    <InputError :message="form.errors.tags" />
                </div>

                <!-- Custom Metadata -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Custom Fields</label>
                    <MetadataEditor v-model="form.metadata" />
                </div>

                <!-- Change Summary (shown on edit or when a file is selected) -->
                <div v-if="isEditing || form.file" class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" for="change_summary">Change Summary</label>
                    <input
                        id="change_summary"
                        v-model="form.change_summary"
                        type="text"
                        placeholder="e.g. Updated section 3 with revised figures"
                        class="rounded-lg border border-sidebar-border bg-transparent px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-primary"
                    />
                    <InputError :message="form.errors.change_summary" />
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pt-2">
                    <a
                        href="/documents"
                        class="rounded-lg border border-sidebar-border px-4 py-2 text-sm hover:bg-muted"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        <component :is="isEditing ? Save : Upload" class="h-4 w-4" />
                        {{ isEditing ? 'Save Changes' : 'Upload' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
