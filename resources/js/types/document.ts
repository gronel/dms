import type { User } from './auth';
import type { Folder } from './folder';
import type { Tag, DocumentMetadata } from './tag';
import type { DocumentComment } from './comment';

export type DocumentStatus = 'draft' | 'published' | 'archived' | 'deleted';

export type DocumentVersion = {
    id: number;
    document_id: number;
    version_number: number;
    storage_path: string;
    storage_disk: string;
    file_name: string;
    mime_type: string;
    size_bytes: number;
    formatted_size: string;
    checksum_sha256: string;
    uploaded_by: number;
    uploader?: User;
    change_summary: string | null;
    is_current: boolean;
    created_at: string;
};

export type Document = {
    id: number;
    ulid: string;
    title: string;
    description: string | null;
    owner_id: number;
    owner?: User;
    folder_id: number | null;
    folder?: Folder;
    status: DocumentStatus;
    is_locked: boolean;
    locked_by: number | null;
    current_version?: DocumentVersion;
    versions?: DocumentVersion[];
    tags?: Tag[];
    metadata?: DocumentMetadata[];
    comments?: DocumentComment[];
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};
