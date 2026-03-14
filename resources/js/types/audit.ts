import type { User } from './auth';
import type { Paginator } from './search';

export type AuditAction =
    | 'upload'
    | 'update'
    | 'delete'
    | 'download'
    | 'restore'
    | 'create'
    | 'tag_sync'
    | string;

export type AuditLog = {
    id: number;
    user_id: number | null;
    user?: User | null;
    auditable_type: string;
    auditable_id: number;
    action: AuditAction;
    description: string | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
};

export type AuditLogFilters = {
    action: string | null;
    type: 'document' | 'folder' | null;
    per_page: number;
};

export type AuditLogIndexProps = {
    logs: Paginator<AuditLog>;
    actions: string[];
    filters: AuditLogFilters;
};
