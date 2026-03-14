<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import {
    FolderOpen,
    Search,
    ShieldCheck,
    History,
    Tags,
    MessageSquare,
    FileText,
    Lock,
    BarChart3,
    ArrowRight,
    CheckCircle2,
    Sun,
    Moon,
    Monitor,
} from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    { canRegister: true }
);

const { appearance, updateAppearance } = useAppearance();

function cycleTheme() {
    if (appearance.value === 'dark') updateAppearance('light');
    else if (appearance.value === 'light') updateAppearance('system');
    else updateAppearance('dark');
}

const features = [
    { icon: FolderOpen,    title: 'Smart Organisation',   description: 'Organise documents into nested folders with drag-and-drop simplicity. Keep every file exactly where you need it.' },
    { icon: Search,        title: 'Full-Text Search',      description: 'Lightning-fast search powered by Meilisearch. Find any document by title, tag, or metadata in milliseconds.' },
    { icon: History,       title: 'Version Control',       description: 'Every upload creates a new version. Roll back to any previous revision with a single click and preserve your history.' },
    { icon: ShieldCheck,   title: 'Granular Permissions',  description: 'Grant per-document access to individual users. Control who can view, edit, or share — nothing more, nothing less.' },
    { icon: Tags,          title: 'Tags & Metadata',       description: 'Enrich documents with custom tags and metadata fields. Slice and filter your library any way you need.' },
    { icon: MessageSquare, title: 'Inline Comments',       description: 'Discuss documents directly in context. Comment threads keep feedback tied to the file, not buried in email.' },
    { icon: Lock,          title: 'Document Locking',      description: 'Lock a document to prevent simultaneous edits. Unlock when ready — with a full audit trail of every state change.' },
    { icon: BarChart3,     title: 'Audit Trail',           description: 'Every action is logged — uploads, edits, shares, and deletions. Stay compliant and always know who did what.' },
];

const highlights = [
    'Role-based access control',
    'Secure file storage',
    'Share links with expiry',
    'Two-factor authentication',
    'Soft-delete & recovery',
    'Real-time dashboard',
];
</script>

<template>
    <Head title="DocVault — Modern Document Management" />

    <div class="min-h-screen bg-white text-gray-900 antialiased dark:bg-[#0f0f13] dark:text-white">

        <!-- Navigation -->
        <nav class="fixed inset-x-0 top-0 z-50 border-b border-gray-200/80 bg-white/80 backdrop-blur-md dark:border-white/5 dark:bg-[#0f0f13]/80">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <!-- Logo -->
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500">
                        <FileText class="h-4 w-4 text-white" />
                    </div>
                    <span class="text-sm font-semibold tracking-tight">DocVault</span>
                </div>

                <!-- Nav links -->
                <div class="hidden items-center gap-8 text-sm text-gray-500 dark:text-white/60 md:flex">
                    <a href="#features" class="transition-colors hover:text-gray-900 dark:hover:text-white">Features</a>
                    <a href="#highlights" class="transition-colors hover:text-gray-900 dark:hover:text-white">Why DocVault</a>
                </div>

                <!-- Right side: theme toggle + auth -->
                <div class="flex items-center gap-3">
                    <!-- Theme toggle -->
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-900 dark:border-white/10 dark:text-white/50 dark:hover:border-white/20 dark:hover:text-white"
                        :title="appearance === 'dark' ? 'Switch to light' : appearance === 'light' ? 'Switch to system' : 'Switch to dark'"
                        @click="cycleTheme"
                    >
                        <Sun v-if="appearance === 'light'" class="h-4 w-4" />
                        <Moon v-else-if="appearance === 'dark'" class="h-4 w-4" />
                        <Monitor v-else class="h-4 w-4" />
                    </button>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-500 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-indigo-400"
                    >
                        Dashboard <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg px-4 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-white/70 dark:hover:text-white"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-500 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-indigo-400"
                        >
                            Get started <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <section class="relative flex min-h-screen items-center justify-center overflow-hidden pt-16">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute top-1/4 left-1/2 h-[600px] w-[800px] -translate-x-1/2 -translate-y-1/4 rounded-full bg-indigo-600/10 blur-[120px]" />
                <div class="absolute right-0 bottom-0 h-[400px] w-[400px] rounded-full bg-violet-600/5 blur-[100px] dark:bg-violet-600/10" />
            </div>

            <div
                class="pointer-events-none absolute inset-0 opacity-[0.015] dark:opacity-[0.03]"
                style="background-image: linear-gradient(rgba(0,0,0,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,.6) 1px, transparent 1px); background-size: 64px 64px;"
            />

            <div class="relative mx-auto max-w-4xl px-6 py-24 text-center">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400" />
                    Enterprise Document Management
                </div>

                <h1 class="mb-6 text-5xl font-bold leading-[1.1] tracking-tight text-gray-900 dark:text-white sm:text-6xl lg:text-7xl">
                    Every document,
                    <span class="bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400">
                        exactly where<br />it should be.
                    </span>
                </h1>

                <p class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-gray-500 dark:text-white/50">
                    DocVault brings structure, security, and speed to your document workflow — with version control, granular permissions, full-text search, and a complete audit trail built in.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4">
                    <Link
                        v-if="!$page.props.auth.user && canRegister"
                        :href="register()"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-400 hover:shadow-indigo-400/30"
                    >
                        Start for free <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-6 py-3 text-sm font-semibold text-gray-700 transition-all hover:border-gray-300 hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-white/20 dark:hover:bg-white/10"
                    >
                        Sign in to your vault
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-400"
                    >
                        Go to Dashboard <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div class="mt-16 flex flex-wrap items-center justify-center gap-8 border-t border-gray-100 pt-10 text-sm text-gray-400 dark:border-white/5 dark:text-white/40">
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="h-4 w-4 text-indigo-500 dark:text-indigo-400" />
                        <span>End-to-end secure storage</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <History class="h-4 w-4 text-indigo-500 dark:text-indigo-400" />
                        <span>Unlimited version history</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <BarChart3 class="h-4 w-4 text-indigo-500 dark:text-indigo-400" />
                        <span>Full audit trail</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features grid -->
        <section id="features" class="py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        Everything your team needs
                    </h2>
                    <p class="mx-auto max-w-xl text-gray-500 dark:text-white/50">
                        A complete toolkit for managing documents at any scale — from solo users to large organisations.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-2xl border border-gray-100 bg-gray-50/50 p-6 transition-all hover:border-indigo-200 hover:bg-indigo-50/40 dark:border-white/5 dark:bg-white/[0.03] dark:hover:border-indigo-500/30 dark:hover:bg-white/[0.06]"
                    >
                        <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 transition-colors group-hover:bg-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:group-hover:bg-indigo-500/20">
                            <component :is="feature.icon" class="h-5 w-5" />
                        </div>
                        <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">{{ feature.title }}</h3>
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-white/40">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why DocVault -->
        <section id="highlights" class="py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-violet-50 p-px dark:border-white/5 dark:from-indigo-500/10 dark:via-white/[0.02] dark:to-violet-500/10">
                    <div class="rounded-3xl bg-white p-10 dark:bg-[#0f0f13] md:p-16">
                        <div class="grid items-center gap-12 md:grid-cols-2">
                            <div>
                                <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    Built for reliability
                                </div>
                                <h2 class="mb-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                                    Security and compliance<br />
                                    <span class="text-gray-400 dark:text-white/40">without the overhead.</span>
                                </h2>
                                <p class="mb-8 text-gray-500 dark:text-white/50">
                                    Whether you are managing contracts, HR records, or design assets — DocVault gives you the controls you need without the complexity you do not.
                                </p>
                                <div class="flex flex-wrap gap-4">
                                    <Link
                                        v-if="!$page.props.auth.user && canRegister"
                                        :href="register()"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-indigo-400"
                                    >
                                        Get started free <ArrowRight class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        v-if="!$page.props.auth.user"
                                        :href="login()"
                                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-white/20"
                                    >
                                        Sign in
                                    </Link>
                                    <Link
                                        v-if="$page.props.auth.user"
                                        :href="dashboard()"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-indigo-400"
                                    >
                                        Open dashboard <ArrowRight class="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div
                                    v-for="item in highlights"
                                    :key="item"
                                    class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 dark:border-white/5 dark:bg-white/[0.03]"
                                >
                                    <CheckCircle2 class="h-4 w-4 shrink-0 text-indigo-500 dark:text-indigo-400" />
                                    <span class="text-sm text-gray-700 dark:text-white/70">{{ item }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-100 py-10 dark:border-white/5">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 text-sm text-gray-400 dark:text-white/30 sm:flex-row">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-500/80">
                        <FileText class="h-3 w-3 text-white" />
                    </div>
                    <span class="font-medium text-gray-600 dark:text-white/50">DocVault</span>
                </div>
                <span>© {{ new Date().getFullYear() }} DocVault. All rights reserved.</span>
            </div>
        </footer>

    </div>
</template>
