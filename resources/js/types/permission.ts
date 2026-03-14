import type { User } from './auth';

export type PermissionLevel = 'view' | 'download' | 'edit';

export type DocumentPermission = {
    id: number;
    document_id: number;
    user_id: number | null;
    user?: User | null;
    granted_by: number;
    granted_by_user?: User | null;
    permission: PermissionLevel;
    expires_at: string | null;
    share_token: string | null;
    created_at: string;
    updated_at: string;
};

export type ShareLinkResponse = {
    share_url: string;
    expires_at: string;
    token: string;
};
