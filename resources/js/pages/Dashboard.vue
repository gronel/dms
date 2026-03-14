<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { FileStack, CheckCircle2, FileEdit, Archive, HardDrive, Clock, ArrowRight } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, DashboardProps } from '@/types';

const props = defineProps<DashboardProps>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

function formatBytes(bytes: number): string {
    if (bytes < 1_024) return `${bytes} B`;
    if (bytes < 1_048_576) return `${(bytes / 1_024).toFixed(1)} KB`;
    if (bytes < 1_073_741_824) return `${(bytes / 1_048_576).toFixed(1)} MB`;
    return `${(bytes / 1_073_741_824).toFixed(2)} GB`;
}

function relativeTime(iso: string): string {
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60_000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
}

const actionLabels: Record<string, string> = {
    upload: 'Uploaded',
    update: 'Updated',
    delete: 'Deleted',
    download: 'Downloaded',
    restore: 'Restored',
    create: 'Created',
    lock: 'Locked',
    unlock: 'Unlocked',
    share_grant: 'Shared',
    share_revoke: 'Share revoked',
    comment_add: 'Commented',
    comment_delete: 'Deleted comment',
};

const statCards = computed(() => [
    {
        label: 'Total Documents',
        value: props.stats.total_documents,
        icon: FileStack,
        color: 'text-primary',
    },
    {
        label: 'Published',
        value: props.stats.published,
        icon: CheckCircle2,
        color: 'text-green-500',
    },
    {
        label: 'Drafts',
        value: props.stats.draft,
        icon: FileEdit,
        color: 'text-blue-500',
    },
    {
        label: 'Storage Used',
        value: formatBytes(props.stats.storage_bytes),
        icon: HardDrive,
        color: 'text-orange-500',
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">

            <!-- ── Stats cards ── -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="flex flex-col gap-2 rounded-xl border border-sidebar-border bg-sidebar/40 px-5 py-4"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">{{ card.label }}</span>
                        <component :is="card.icon" class="h-4 w-4" :class="card.color" />
                    </div>
                    <span class="text-2xl font-semibold tabular-nums">{{ card.value }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- ── Recent Documents ── -->
                <section class="rounded-xl border border-sidebar-border bg-sidebar/40">
                    <div class="flex items-center justify-between border-b border-sidebar-border px-5 py-3">
                        <h2 class="flex items-center gap-2 text-sm font-semibold">
                            <FileStack class="h-4 w-4 text-muted-foreground" />
                            Recent Documents
                        </h2>
                        <Link href="/documents" class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">
                            View all <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <div v-if="recentDocuments.length === 0" class="px-5 py-6 text-center text-sm text-muted-foreground">
                        No documents yet.
                        <Link href="/documents/create" class="ml-1 text-primary hover:underline">Upload one →</Link>
                    </div>
                    <ul v-else class="divide-y divide-sidebar-border">
                        <li v-for="doc in recentDocuments" :key="doc.id">
                            <Link
                                :href="`/documents/${doc.ulid}`"
                                class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-muted/50"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-sm font-medium">{{ doc.title }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ doc.current_version?.file_name ?? '—' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span
                                        v-if="doc.is_locked"
                                        class="rounded-full bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"
                                    >
                                        Locked
                                    </span>
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-xs font-medium capitalize"
                                        :class="{
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': doc.status === 'published',
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400':   doc.status === 'draft',
                                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400':      doc.status === 'archived',
                                        }"
                                    >
                                        {{ doc.status }}
                                    </span>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </section>

                <!-- ── Recent Activity ── -->
                <section class="rounded-xl border border-sidebar-border bg-sidebar/40">
                    <div class="flex items-center justify-between border-b border-sidebar-border px-5 py-3">
                        <h2 class="flex items-center gap-2 text-sm font-semibold">
                            <Clock class="h-4 w-4 text-muted-foreground" />
                            Recent Activity
                        </h2>
                        <Link href="/audit-logs" class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">
                            View all <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <div v-if="recentActivity.length === 0" class="px-5 py-6 text-center text-sm text-muted-foreground">
                        No activity yet.
                    </div>
                    <ul v-else class="divide-y divide-sidebar-border">
                        <li
                            v-for="log in recentActivity"
                            :key="log.id"
                            class="flex items-start gap-3 px-5 py-3"
                        >
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold uppercase">
                                {{ log.user?.name?.slice(0, 1) ?? '?' }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm">
                                    <span class="font-medium">{{ log.user?.name ?? 'System' }}</span>
                                    <span class="text-muted-foreground"> {{ actionLabels[log.action] ?? log.action }}</span>
                                </p>
                                <p v-if="log.description" class="truncate text-xs text-muted-foreground">{{ log.description }}</p>
                            </div>
                            <time class="shrink-0 text-xs text-muted-foreground">{{ relativeTime(log.created_at) }}</time>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- ── Storage bar ── -->
            <div
                v-if="stats.archived > 0"
                class="rounded-xl border border-sidebar-border bg-sidebar/40 px-5 py-4"
            >
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">Archived documents</span>
                    <span class="text-muted-foreground">{{ stats.archived }}</span>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

