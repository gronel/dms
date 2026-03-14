import type { User } from './auth';
import type { Document } from './document';

export type DocumentComment = {
    id: number;
    document_id: number;
    user_id: number;
    user?: User;
    content: string;
    created_at: string;
    updated_at: string;
};

export type CommentFormData = {
    content: string;
};

export type DocumentWithComments = Document & {
    comments?: DocumentComment[];
    comments_count?: number;
};
