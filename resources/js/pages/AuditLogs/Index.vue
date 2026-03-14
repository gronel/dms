<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardList, Filter, X, ChevronRight } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AuditLog, AuditLogIndexProps, BreadcrumbItem } from '@/types';

const props = defineProps<AuditLogIndexProps>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audit Log', href: '/audit-logs' },
];

const actionFilter = ref(props.filters.action ?? '');
const typeFilter   = ref<string>(props.filters.type ?? '');

let debounce: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        '/audit-logs',
        {
            action: actionFilter.value || undefined,
            type:   typeFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clearFilters() {
    actionFilter.value = '';
    typeFilter.value   = '';
    applyFilters();
}

watch(() => props.filters, (f) => {
    actionFilter.value = f.action ?? '';
    typeFilter.value   = f.type ?? '';
});

const actionLabels: Record<string, string> = {
    upload:   'Upload',
    update:   'Update',
    delete:   'Delete',
    download: 'Download',
    restore:  'Restore',
    create:   'Create',
    tag_sync: 'Tag sync',
};

const actionColors: Record<string, string> = {
    upload:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    update:   'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    delete:   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    download: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    restore:  'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    create:   'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    tag_sync: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};

function actionClass(action: string): string {
    return actionColors[action] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
}

function shortType(type: string): string {
    return type.split('\\').pop() ?? type;
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function hasChanges(log: AuditLog): boolean {
    return (log.old_values !== null && Object.keys(log.old_values).length > 0) ||
           (log.new_values !== null && Object.keys(log.new_values).length > 0);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Audit Log" />

        <div class="flex h-full flex-col gap-0">
            <!-- Header bar -->
            <div class="flex items-center justify-between border-b border-sidebar-border/70 bg-background px-4 py-4 sm:px-6">
                <div class="flex items-center gap-2">
                    <ClipboardList class="h-5 w-5 text-muted-foreground" />
                    <h1 class="text-lg font-semibold">Audit Trail</h1>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2">
                    <Filter class="h-4 w-4 text-muted-foreground" />

                    <!-- Action filter -->
                    <select
                        v-model="actionFilter"
                        class="rounded-md border border-input bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        @change="applyFilters"
                    >
                        <option value="">All actions</option>
                        <option v-for="a in actions" :key="a" :value="a">
                            {{ actionLabels[a] ?? a }}
                        </option>
                    </select>

                    <!-- Type filter -->
                    <select
                        v-model="typeFilter"
                        class="rounded-md border border-input bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        @change="applyFilters"
                    >
                        <option value="">All types</option>
                        <option value="document">Documents</option>
                        <option value="folder">Folders</option>
                    </select>

                    <button
                        v-if="actionFilter || typeFilter"
                        type="button"
                        class="flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:text-foreground"
                        @click="clearFilters"
                    >
                        <X class="h-3.5 w-3.5" />
                        Clear
                    </button>
                </div>
            </div>

            <!-- Log table -->
            <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                <!-- Result count -->
                <p class="mb-3 text-sm text-muted-foreground">
                    <template v-if="logs.total > 0">
                        Showing <span class="font-medium text-foreground">{{ logs.from }}–{{ logs.to }}</span>
                        of <span class="font-medium text-foreground">{{ logs.total }}</span> events
                    </template>
                    <template v-else>No audit events found</template>
                </p>

                <!-- Empty state -->
                <div
                    v-if="logs.data.length === 0"
                    class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 py-16 text-center"
                >
                    <ClipboardList class="h-10 w-10 text-muted-foreground/40" />
                    <p class="text-base font-medium text-muted-foreground">No events yet</p>
                    <p class="text-sm text-muted-foreground/70">Audit events will appear here when you upload, edit, or delete documents.</p>
                </div>

                <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Time</th>
                                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Action</th>
                                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Type</th>
                                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Description</th>
                                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Changes</th>
                                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="bg-background transition hover:bg-muted/30"
                            >
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-muted-foreground">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                        :class="actionClass(log.action)"
                                    >
                                        {{ actionLabels[log.action] ?? log.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ shortType(log.auditable_type) }} #{{ log.auditable_id }}
                                </td>
                                <td class="max-w-xs px-4 py-3 text-sm">
                                    {{ log.description ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <details v-if="hasChanges(log)" class="group">
                                        <summary class="cursor-pointer list-none text-xs text-primary hover:underline">
                                            View diff
                                        </summary>
                                        <div class="mt-1 rounded border border-border bg-muted/50 p-2 font-mono text-xs">
                                            <div v-if="log.old_values && Object.keys(log.old_values).length > 0">
                                                <p class="font-semibold text-red-600 dark:text-red-400">Before</p>
                                                <pre class="whitespace-pre-wrap break-all">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                                            </div>
                                            <div v-if="log.new_values && Object.keys(log.new_values).length > 0" class="mt-1">
                                                <p class="font-semibold text-green-600 dark:text-green-400">After</p>
                                                <pre class="whitespace-pre-wrap break-all">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                                            </div>
                                        </div>
                                    </details>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ log.ip_address ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="logs.last_page > 1"
                    class="mt-4 flex items-center justify-center gap-1"
                    aria-label="Pagination"
                >
                    <Link
                        v-for="link in logs.links"
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
            </div>
        </div>
    </AppLayout>
</template>
