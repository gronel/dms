<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, ArrowLeft } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AuditLog, BreadcrumbItem, Document, Paginator } from '@/types';

const props = defineProps<{
    document: Document;
    logs: Paginator<AuditLog>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documents', href: '/documents' },
    ...(props.document.folder ? [{ title: props.document.folder.name, href: `/folders/${props.document.folder.id}` }] : []),
    { title: props.document.title, href: `/documents/${props.document.ulid}` },
    { title: 'Audit Trail', href: '#' },
];

const actionColors: Record<string, string> = {
    upload:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    update:   'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    delete:   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    download: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    restore:  'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    create:   'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
};

function actionClass(action: string): string {
    return actionColors[action] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Audit Trail — ${document.title}`" />

        <div class="flex flex-col gap-4 px-4 py-5 sm:px-6">
            <!-- Back + title -->
            <div class="flex items-center gap-3">
                <Link
                    :href="`/documents/${document.ulid}`"
                    class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to document
                </Link>
            </div>

            <div class="flex items-center gap-2">
                <ClipboardList class="h-5 w-5 text-muted-foreground" />
                <h1 class="text-lg font-semibold">Audit Trail</h1>
                <span class="text-muted-foreground">—</span>
                <span class="text-muted-foreground">{{ document.title }}</span>
            </div>

            <!-- Empty state -->
            <div
                v-if="logs.data.length === 0"
                class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 py-16 text-center"
            >
                <ClipboardList class="h-10 w-10 text-muted-foreground/40" />
                <p class="text-base font-medium text-muted-foreground">No audit events for this document yet</p>
            </div>

            <!-- Timeline -->
            <ol v-else class="relative ml-3 border-l border-border">
                <li
                    v-for="log in logs.data"
                    :key="log.id"
                    class="mb-6 ml-6"
                >
                    <!-- Dot -->
                    <span class="absolute -left-2 flex h-4 w-4 items-center justify-center rounded-full bg-background ring-2 ring-border">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                    </span>

                    <div class="rounded-lg border border-sidebar-border/70 bg-white p-3 shadow-sm dark:bg-sidebar">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                :class="actionClass(log.action)"
                            >
                                {{ log.action }}
                            </span>
                            <time class="text-xs text-muted-foreground">{{ formatDate(log.created_at) }}</time>
                            <span v-if="log.user" class="text-xs text-muted-foreground">by {{ log.user.name }}</span>
                            <span v-if="log.ip_address" class="ml-auto text-xs text-muted-foreground">{{ log.ip_address }}</span>
                        </div>

                        <p v-if="log.description" class="mt-1 text-sm">{{ log.description }}</p>

                        <!-- Diff -->
                        <details
                            v-if="(log.old_values && Object.keys(log.old_values).length > 0) || (log.new_values && Object.keys(log.new_values).length > 0)"
                            class="mt-1.5"
                        >
                            <summary class="cursor-pointer text-xs text-primary hover:underline">Show changes</summary>
                            <div class="mt-1 grid grid-cols-2 gap-2 font-mono text-xs">
                                <div v-if="log.old_values && Object.keys(log.old_values).length > 0" class="rounded border border-red-200 bg-red-50 p-2 dark:border-red-900/40 dark:bg-red-950/20">
                                    <p class="mb-1 font-semibold text-red-600 dark:text-red-400">Before</p>
                                    <pre class="whitespace-pre-wrap break-all">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                                </div>
                                <div v-if="log.new_values && Object.keys(log.new_values).length > 0" class="rounded border border-green-200 bg-green-50 p-2 dark:border-green-900/40 dark:bg-green-950/20">
                                    <p class="mb-1 font-semibold text-green-600 dark:text-green-400">After</p>
                                    <pre class="whitespace-pre-wrap break-all">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                                </div>
                            </div>
                        </details>
                    </div>
                </li>
            </ol>

            <!-- Pagination -->
            <nav
                v-if="logs.last_page > 1"
                class="flex items-center justify-center gap-1"
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
    </AppLayout>
</template>
