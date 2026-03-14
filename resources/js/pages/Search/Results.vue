<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, SlidersHorizontal, X, FileText, FolderOpen, Lock } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SearchPageProps, SearchSort, Tag } from '@/types';

const props = defineProps<SearchPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Search', href: '/search' },
];

// ── Local filter state (controlled inputs, synced from props) ──────────────
const query   = ref(props.filters.q);
const status  = ref<string>(props.filters.status ?? '');
const tagIds  = ref<number[]>([...props.filters.tag_ids]);
const sort    = ref<SearchSort>(props.filters.sort);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        '/search',
        {
            q:        query.value || undefined,
            status:   status.value || undefined,
            tag_ids:  tagIds.value.length ? tagIds.value : undefined,
            sort:     sort.value !== 'relevance' ? sort.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function onQueryInput() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
}

function clearQuery() {
    query.value = '';
    applyFilters();
}

function toggleTag(tagId: number) {
    const idx = tagIds.value.indexOf(tagId);
    if (idx === -1) {
        tagIds.value = [...tagIds.value, tagId];
    } else {
        tagIds.value = tagIds.value.filter((id) => id !== tagId);
    }
    applyFilters();
}

function clearFilters() {
    query.value  = '';
    status.value = '';
    tagIds.value = [];
    sort.value   = 'relevance';
    applyFilters();
}

watch(() => props.filters, (f) => {
    query.value  = f.q;
    status.value = f.status ?? '';
    tagIds.value = [...f.tag_ids];
    sort.value   = f.sort;
});

const sortOptions: { value: SearchSort; label: string }[] = [
    { value: 'relevance',  label: 'Relevance' },
    { value: 'newest',     label: 'Newest first' },
    { value: 'oldest',     label: 'Oldest first' },
    { value: 'title_asc',  label: 'Title A→Z' },
    { value: 'title_desc', label: 'Title Z→A' },
];

const statusOptions = [
    { value: '',          label: 'All statuses' },
    { value: 'draft',     label: 'Draft' },
    { value: 'published', label: 'Published' },
    { value: 'archived',  label: 'Archived' },
];

function tagById(id: number): Tag | undefined {
    return props.allTags.find((t) => t.id === id);
}

const activeFilterCount = () =>
    (status.value ? 1 : 0) + tagIds.value.length + (sort.value !== 'relevance' ? 1 : 0);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Search Documents" />

        <div class="flex h-full flex-col gap-0">
            <!-- ── Search bar ────────────────────────────────────────────── -->
            <div class="border-b border-sidebar-border/70 bg-background px-4 py-4 sm:px-6">
                <div class="relative mx-auto max-w-3xl">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search documents by title, description, tags, or file content…"
                        class="w-full rounded-lg border border-input bg-background py-2 pl-9 pr-10 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-ring"
                        @input="onQueryInput"
                        @keydown.enter.prevent="applyFilters"
                    />
                    <button
                        v-if="query"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        @click="clearQuery"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- ── Body: sidebar + results ──────────────────────────────── -->
            <div class="flex min-h-0 flex-1">
                <!-- Sidebar ──────────────────────────────────────────────── -->
                <aside class="hidden w-56 shrink-0 flex-col gap-6 overflow-y-auto border-r border-sidebar-border/70 bg-sidebar px-4 py-5 lg:flex xl:w-64">
                    <!-- Filters header -->
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-foreground">
                            <SlidersHorizontal class="h-4 w-4" />
                            Filters
                            <span
                                v-if="activeFilterCount() > 0"
                                class="ml-1 rounded-full bg-primary px-1.5 py-0.5 text-xs font-bold text-primary-foreground"
                            >{{ activeFilterCount() }}</span>
                        </span>
                        <button
                            v-if="activeFilterCount() > 0"
                            type="button"
                            class="text-xs text-muted-foreground hover:text-foreground"
                            @click="clearFilters"
                        >
                            Clear all
                        </button>
                    </div>

                    <!-- Sort -->
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Sort by</p>
                        <select
                            v-model="sort"
                            class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            @change="applyFilters"
                        >
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status filter -->
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</p>
                        <div class="flex flex-col gap-1">
                            <label
                                v-for="opt in statusOptions"
                                :key="opt.value"
                                class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-accent"
                            >
                                <input
                                    v-model="status"
                                    type="radio"
                                    :value="opt.value"
                                    class="accent-primary"
                                    @change="applyFilters"
                                />
                                {{ opt.label }}
                            </label>
                        </div>
                    </div>

                    <!-- Tag filter -->
                    <div v-if="allTags.length > 0">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Tags</p>
                        <div class="flex flex-col gap-1">
                            <button
                                v-for="tag in allTags"
                                :key="tag.id"
                                type="button"
                                class="flex items-center gap-2 rounded-md px-2 py-1 text-left text-sm transition hover:bg-accent"
                                :class="{ 'bg-accent': tagIds.includes(tag.id) }"
                                @click="toggleTag(tag.id)"
                            >
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    :style="{ backgroundColor: tag.color ?? '#6b7280' }"
                                />
                                <span class="truncate">{{ tag.name }}</span>
                                <span
                                    v-if="tagIds.includes(tag.id)"
                                    class="ml-auto text-primary"
                                >✓</span>
                            </button>
                        </div>
                    </div>
                </aside>

                <!-- Results pane ──────────────────────────────────────────── -->
                <main class="flex flex-1 flex-col gap-4 overflow-y-auto px-4 py-5 sm:px-6">
                    <!-- Active filter pills (mobile-friendly) -->
                    <div v-if="tagIds.length > 0 || status" class="flex flex-wrap gap-2">
                        <span
                            v-if="status"
                            class="inline-flex items-center gap-1 rounded-full border border-border bg-accent px-2.5 py-0.5 text-xs font-medium capitalize"
                        >
                            {{ status }}
                            <button type="button" @click="status = ''; applyFilters()">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                        <span
                            v-for="id in tagIds"
                            :key="id"
                            class="inline-flex items-center gap-1 rounded-full border border-border px-2.5 py-0.5 text-xs font-medium"
                            :style="{ backgroundColor: (tagById(id)?.color ?? '#6b7280') + '22', borderColor: tagById(id)?.color ?? '#6b7280' }"
                        >
                            {{ tagById(id)?.name ?? id }}
                            <button type="button" @click="toggleTag(id)">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                    </div>

                    <!-- Result count -->
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            <template v-if="documents.total > 0">
                                Showing
                                <span class="font-medium text-foreground">{{ documents.from }}–{{ documents.to }}</span>
                                of
                                <span class="font-medium text-foreground">{{ documents.total }}</span>
                                result{{ documents.total !== 1 ? 's' : '' }}
                            </template>
                            <template v-else>No results found</template>
                        </p>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="documents.data.length === 0"
                        class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 py-16 text-center"
                    >
                        <Search class="h-10 w-10 text-muted-foreground/40" />
                        <p class="text-base font-medium text-muted-foreground">
                            {{ query ? `No documents match "${query}"` : 'No documents found' }}
                        </p>
                        <p class="text-sm text-muted-foreground/70">
                            Try different keywords or remove some filters.
                        </p>
                    </div>

                    <!-- Document list -->
                    <ul v-else class="flex flex-col gap-3">
                        <li
                            v-for="doc in documents.data"
                            :key="doc.id"
                            class="group relative flex flex-col gap-2 rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-sidebar-border dark:bg-sidebar"
                        >
                            <!-- Lock badge -->
                            <span
                                v-if="doc.is_locked"
                                class="absolute right-3 top-3 flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
                            >
                                <Lock class="h-3 w-3" />
                                Locked
                            </span>

                            <!-- Title row -->
                            <div class="flex items-start gap-3 pr-16">
                                <FileText class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                                <div class="min-w-0">
                                    <Link
                                        :href="`/documents/${doc.ulid}`"
                                        class="line-clamp-2 font-medium text-foreground hover:underline"
                                    >
                                        {{ doc.title }}
                                    </Link>
                                    <p v-if="doc.description" class="mt-0.5 line-clamp-1 text-xs text-muted-foreground">
                                        {{ doc.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Meta row -->
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span
                                    class="rounded-full px-2 py-0.5 font-medium capitalize"
                                    :class="{
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': doc.status === 'published',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': doc.status === 'draft',
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': doc.status === 'archived',
                                    }"
                                >
                                    {{ doc.status }}
                                </span>

                                <span v-if="doc.current_version">
                                    v{{ doc.current_version.version_number }}
                                    &middot;
                                    {{ doc.current_version.formatted_size }}
                                </span>

                                <span v-if="doc.folder" class="flex items-center gap-1">
                                    <FolderOpen class="h-3.5 w-3.5" />
                                    {{ doc.folder.name }}
                                </span>

                                <span v-if="doc.owner">by {{ doc.owner.name }}</span>
                            </div>

                            <!-- Tags -->
                            <div v-if="doc.tags && doc.tags.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="tag in doc.tags"
                                    :key="tag.id"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :style="{ backgroundColor: (tag.color ?? '#6b7280') + '22', color: tag.color ?? '#6b7280' }"
                                >
                                    {{ tag.name }}
                                </span>
                            </div>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <nav
                        v-if="documents.last_page > 1"
                        class="flex items-center justify-center gap-1 pt-2"
                        aria-label="Pagination"
                    >
                        <Link
                            v-for="link in documents.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            :class="[
                                'rounded-md px-3 py-1.5 text-sm transition',
                                link.active
                                    ? 'bg-primary text-primary-foreground font-semibold pointer-events-none'
                                    : link.url
                                        ? 'border border-border hover:bg-accent'
                                        : 'pointer-events-none text-muted-foreground/50',
                            ]"
                            preserve-scroll
                            v-html="link.label"
                        />
                    </nav>
                </main>
            </div>
        </div>
    </AppLayout>
</template>
