<script setup lang="ts">
import { FileText } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-indigo-500"
    >
        <FileText class="size-4 text-white" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">DocVault</span>
    </div>
</template>
