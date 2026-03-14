import type { AuditLog } from './audit';
import type { Document } from './document';

export type DashboardStats = {
    total_documents: number;
    draft: number;
    published: number;
    archived: number;
    storage_bytes: number;
};

export type DashboardProps = {
    stats: DashboardStats;
    recentActivity: AuditLog[];
    recentDocuments: Document[];
};
