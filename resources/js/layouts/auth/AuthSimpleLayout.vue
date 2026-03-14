<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, Sun, Moon, Monitor } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const { appearance, updateAppearance } = useAppearance();

function cycleTheme() {
    if (appearance.value === 'dark') updateAppearance('light');
    else if (appearance.value === 'light') updateAppearance('system');
    else updateAppearance('dark');
}
</script>

<template>
    <div class="relative flex min-h-svh flex-col items-center justify-center bg-gray-50 p-6 dark:bg-[#0f0f13] md:p-10">
        <!-- Background glow (dark only) -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute top-1/3 left-1/2 h-[500px] w-[700px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-600/5 blur-[120px] dark:bg-indigo-600/10" />
        </div>

        <!-- Theme toggle -->
        <button
            type="button"
            class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-400 transition-colors hover:border-gray-300 hover:text-gray-600 dark:border-white/10 dark:text-white/40 dark:hover:border-white/20 dark:hover:text-white"
            :title="appearance === 'dark' ? 'Switch to light' : appearance === 'light' ? 'Switch to system' : 'Switch to dark'"
            @click="cycleTheme"
        >
            <Sun v-if="appearance === 'light'" class="h-4 w-4" />
            <Moon v-else-if="appearance === 'dark'" class="h-4 w-4" />
            <Monitor v-else class="h-4 w-4" />
        </button>

        <div class="relative w-full max-w-sm">
            <div class="flex flex-col gap-8">

                <!-- Logo + heading -->
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500 shadow-lg shadow-indigo-500/30">
                            <FileText class="h-5 w-5 text-white" />
                        </div>
                        <span class="text-base font-semibold text-gray-900 dark:text-white">DocVault</span>
                    </Link>

                    <div class="space-y-1.5 text-center">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ title }}</h1>
                        <p class="text-sm text-gray-500 dark:text-white/50">{{ description }}</p>
                    </div>
                </div>

                <!-- Card -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-white/[0.03] dark:shadow-none dark:backdrop-blur-sm">
                    <slot />
                </div>

            </div>
        </div>
    </div>
</template>
