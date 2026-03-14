<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { MessageSquare, Trash2 } from 'lucide-vue-next';
import type { Document, DocumentComment } from '@/types';

const props = defineProps<{
    document: Document;
    currentUserId: number;
}>();

const form = useForm({ content: '' });

function submitComment() {
    form.post(`/documents/${props.document.ulid}/comments`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function deleteComment(comment: DocumentComment) {
    if (!confirm('Delete this comment?')) return;
    useForm({}).delete(`/documents/${props.document.ulid}/comments/${comment.id}`, {
        preserveScroll: true,
    });
}

function canDelete(comment: DocumentComment): boolean {
    return comment.user_id === props.currentUserId || props.document.owner_id === props.currentUserId;
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function getInitials(name?: string): string {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}
</script>

<template>
    <div class="space-y-4">
        <!-- Comment list -->
        <div v-if="document.comments && document.comments.length > 0" class="space-y-3">
            <div
                v-for="comment in document.comments"
                :key="comment.id"
                class="flex gap-3 rounded-lg border border-sidebar-border bg-sidebar/40 p-4"
            >
                <!-- Avatar -->
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                    {{ getInitials(comment.user?.name) }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ comment.user?.name ?? 'Unknown' }}</span>
                            <span class="text-xs text-muted-foreground">{{ formatDate(comment.created_at) }}</span>
                        </div>
                        <button
                            v-if="canDelete(comment)"
                            type="button"
                            class="text-muted-foreground transition-colors hover:text-destructive"
                            title="Delete comment"
                            @click="deleteComment(comment)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p class="mt-1 whitespace-pre-wrap text-sm">{{ comment.content }}</p>
                </div>
            </div>
        </div>

        <p v-else class="text-sm text-muted-foreground italic">No comments yet. Be the first to comment.</p>

        <!-- Add comment form -->
        <form class="flex flex-col gap-2" @submit.prevent="submitComment">
            <textarea
                v-model="form.content"
                rows="3"
                maxlength="2000"
                placeholder="Write a comment…"
                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
                :disabled="form.processing"
            />
            <div class="flex items-center justify-between">
                <span v-if="form.errors.content" class="text-xs text-destructive">{{ form.errors.content }}</span>
                <span v-else class="text-xs text-muted-foreground">{{ form.content.length }}/2000</span>
                <button
                    type="submit"
                    :disabled="form.processing || form.content.trim().length === 0"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground transition-opacity disabled:opacity-50"
                >
                    <MessageSquare class="h-3.5 w-3.5" />
                    Post
                </button>
            </div>
        </form>
    </div>
</template>
