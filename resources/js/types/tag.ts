export type Tag = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    is_system: boolean;
    created_at?: string;
    updated_at?: string;
};

export type MetadataValueType = 'string' | 'date' | 'number' | 'boolean';

export type DocumentMetadata = {
    id: number;
    document_id: number;
    key: string;
    value: string;
    value_type: MetadataValueType;
    created_at: string;
    updated_at: string;
};
