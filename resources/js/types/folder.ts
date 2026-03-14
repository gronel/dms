import type { User } from './auth';
import type { Document } from './document';

export type Folder = {
    id: number;
    parent_id: number | null;
    owner_id: number;
    owner?: User;
    name: string;
    full_path?: string;
    parent?: Folder;
    children?: Folder[];
    documents?: Document[];
    documents_count?: number;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};
