<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Share2, Link2, UserPlus, Trash2, Copy, Check, X, RefreshCw } from 'lucide-vue-next';
import type { DocumentPermission, PermissionLevel, ShareLinkResponse, Document } from '@/types';

const props = defineProps<{
    modelValue: boolean;
    document: Document;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const page = usePage();

// ────────────────────────────────────────────────────────────────────────────
// Named-user grants
// ────────────────────────────────────────────────────────────────────────────
const permissions = ref<DocumentPermission[]>([]);
const loadingPerms = ref(false);
const permsError = ref<string | null>(null);

const grantForm = ref({
    email: '',
    permission: 'view' as PermissionLevel,
    expires_at: '',
});
const grantError = ref<string | null>(null);
const grantLoading = ref(false);

async function fetchPermissions() {
    loadingPerms.value = true;
    permsError.value = null;
    try {
        const res = await fetch(`/documents/${props.document.ulid}/permissions`, {
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(await res.text());
        permissions.value = await res.json();
    } catch (e: unknown) {
        permsError.value = e instanceof Error ? e.message : 'Failed to load permissions';
    } finally {
        loadingPerms.value = false;
    }
}

async function grantPermission() {
    grantError.value = null;
    grantLoading.value = true;
    try {
        const body: Record<string, string> = {
            email: grantForm.value.email,
            permission: grantForm.value.permission,
        };
        if (grantForm.value.expires_at) body.expires_at = grantForm.value.expires_at;
        const res = await fetch(`/documents/${props.document.ulid}/permissions`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrf(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(err.message ?? JSON.stringify(err));
        }
        grantForm.value = { email: '', permission: 'view', expires_at: '' };
        await fetchPermissions();
    } catch (e: unknown) {
        grantError.value = e instanceof Error ? e.message : 'Failed to grant permission';
    } finally {
        grantLoading.value = false;
    }
}

async function revokePermission(id: number) {
    if (!confirm('Revoke this permission?')) return;
    try {
        const res = await fetch(`/documents/${props.document.ulid}/permissions/${id}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(await res.text());
        await fetchPermissions();
    } catch {
        permsError.value = 'Failed to revoke permission';
    }
}

// ────────────────────────────────────────────────────────────────────────────
// Share link
// ────────────────────────────────────────────────────────────────────────────
const shareLink = ref<ShareLinkResponse | null>(null);
const shareLinkLoading = ref(false);
const shareLinkError = ref<string | null>(null);
const expiresInHours = ref(24);
const copied = ref(false);

async function generateShareLink() {
    shareLinkLoading.value = true;
    shareLinkError.value = null;
    try {
        const res = await fetch(`/documents/${props.document.ulid}/share-link`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrf(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ expires_in_hours: expiresInHours.value }),
        });
        if (!res.ok) throw new Error(await res.text());
        shareLink.value = await res.json();
    } catch (e: unknown) {
        shareLinkError.value = e instanceof Error ? e.message : 'Failed to generate link';
    } finally {
        shareLinkLoading.value = false;
    }
}

async function revokeShareLink() {
    if (!confirm('Revoke the public share link?')) return;
    shareLinkLoading.value = true;
    shareLinkError.value = null;
    try {
        const res = await fetch(`/documents/${props.document.ulid}/share-link`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(await res.text());
        shareLink.value = null;
    } catch (e: unknown) {
        shareLinkError.value = e instanceof Error ? e.message : 'Failed to revoke link';
    } finally {
        shareLinkLoading.value = false;
    }
}

async function copyLink() {
    if (!shareLink.value) return;
    await navigator.clipboard.writeText(shareLink.value.share_url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

// ────────────────────────────────────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────────────────────────────────────
function getCsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const permissionLabels: Record<PermissionLevel, string> = {
    view: 'View',
    download: 'Download',
    edit: 'Edit',
};

function formatExpiry(val: string | null): string {
    if (!val) return 'Never';
    return new Date(val).toLocaleDateString();
}

const namedPerms = computed(() =>
    permissions.value.filter((p) => p.user_id !== null),
);

// Fetch data when modal opens
watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            fetchPermissions();
            shareLink.value = null;
        }
    },
);

function close() {
    emit('update:modelValue', false);
}
</script>

<template>
    <!-- Backdrop -->
    <Teleport to="body">
        <Transition
            enter-from-class="opacity-0"
            enter-active-class="transition-opacity duration-150"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
                @click="close"
            />
        </Transition>

        <Transition
            enter-from-class="opacity-0 scale-95"
            enter-active-class="transition duration-150 origin-center"
            leave-active-class="transition duration-150 origin-center"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
            >
                <div
                    class="w-full max-w-lg rounded-xl border border-sidebar-border bg-background shadow-xl"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-sidebar-border px-5 py-4">
                        <div class="flex items-center gap-2">
                            <Share2 class="h-4 w-4 text-muted-foreground" />
                            <span class="font-semibold">Share "{{ document.title }}"</span>
                        </div>
                        <button
                            type="button"
                            class="rounded-md p-1 hover:bg-muted"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto px-5 py-4 space-y-6">
                        <!-- ── Named-user permissions ─────────────────────── -->
                        <section>
                            <h3 class="mb-3 flex items-center gap-1.5 text-sm font-semibold">
                                <UserPlus class="h-4 w-4 text-muted-foreground" />
                                Grant access to users
                            </h3>

                            <!-- Grant form -->
                            <form class="space-y-2" @submit.prevent="grantPermission">
                                <div class="flex gap-2">
                                    <input
                                        v-model="grantForm.email"
                                        type="email"
                                        placeholder="user@example.com"
                                        required
                                        class="flex-1 rounded-md border border-input bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                    />
                                    <select
                                        v-model="grantForm.permission"
                                        class="rounded-md border border-input bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                    >
                                        <option value="view">View</option>
                                        <option value="download">Download</option>
                                        <option value="edit">Edit</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-muted-foreground whitespace-nowrap">Expires:</label>
                                    <input
                                        v-model="grantForm.expires_at"
                                        type="datetime-local"
                                        class="flex-1 rounded-md border border-input bg-background px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="grantLoading"
                                        class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                    >
                                        {{ grantLoading ? 'Granting…' : 'Grant' }}
                                    </button>
                                </div>
                                <p v-if="grantError" class="text-xs text-destructive">{{ grantError }}</p>
                            </form>

                            <!-- Permissions list -->
                            <div v-if="loadingPerms" class="mt-3 text-xs text-muted-foreground">Loading…</div>
                            <p v-else-if="permsError" class="mt-3 text-xs text-destructive">{{ permsError }}</p>
                            <div v-else-if="namedPerms.length === 0" class="mt-3 text-xs text-muted-foreground">
                                No users have been granted access.
                            </div>
                            <ul v-else class="mt-3 divide-y divide-sidebar-border rounded-lg border border-sidebar-border">
                                <li
                                    v-for="perm in namedPerms"
                                    :key="perm.id"
                                    class="flex items-center justify-between gap-2 px-3 py-2"
                                >
                                    <span class="flex-1 min-w-0">
                                        <span class="block truncate text-sm">{{ perm.user?.name ?? perm.user?.email }}</span>
                                        <span class="text-xs text-muted-foreground">
                                            {{ permissionLabels[perm.permission] }}
                                            &middot; expires {{ formatExpiry(perm.expires_at) }}
                                        </span>
                                    </span>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded p-1 text-destructive hover:bg-destructive/10"
                                        title="Revoke"
                                        @click="revokePermission(perm.id)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </li>
                            </ul>
                        </section>

                        <!-- Divider -->
                        <div class="border-t border-sidebar-border" />

                        <!-- ── Public share link ───────────────────────────── -->
                        <section>
                            <h3 class="mb-3 flex items-center gap-1.5 text-sm font-semibold">
                                <Link2 class="h-4 w-4 text-muted-foreground" />
                                Public share link
                            </h3>

                            <div v-if="!shareLink" class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-muted-foreground whitespace-nowrap">Expires in:</label>
                                    <select
                                        v-model="expiresInHours"
                                        class="rounded-md border border-input bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                    >
                                        <option :value="1">1 hour</option>
                                        <option :value="24">24 hours</option>
                                        <option :value="72">3 days</option>
                                        <option :value="168">7 days</option>
                                        <option :value="720">30 days</option>
                                    </select>
                                    <button
                                        type="button"
                                        :disabled="shareLinkLoading"
                                        class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                        @click="generateShareLink"
                                    >
                                        <RefreshCw v-if="shareLinkLoading" class="h-3.5 w-3.5 animate-spin" />
                                        <span v-else>Generate link</span>
                                    </button>
                                </div>
                            </div>

                            <div v-else class="space-y-2">
                                <!-- URL display -->
                                <div class="flex items-center gap-2 rounded-md border border-input bg-muted/40 px-3 py-2">
                                    <span class="flex-1 min-w-0 truncate font-mono text-xs">{{ shareLink.share_url }}</span>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded p-1 hover:bg-muted"
                                        :title="copied ? 'Copied!' : 'Copy'"
                                        @click="copyLink"
                                    >
                                        <Check v-if="copied" class="h-4 w-4 text-green-500" />
                                        <Copy v-else class="h-4 w-4 text-muted-foreground" />
                                    </button>
                                </div>
                                <div class="flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Expires {{ formatExpiry(shareLink.expires_at) }}</span>
                                    <button
                                        type="button"
                                        class="text-destructive hover:underline"
                                        :disabled="shareLinkLoading"
                                        @click="revokeShareLink"
                                    >
                                        Revoke link
                                    </button>
                                </div>
                            </div>

                            <p v-if="shareLinkError" class="mt-1 text-xs text-destructive">{{ shareLinkError }}</p>
                        </section>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
