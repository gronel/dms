import type { Document } from './document';
import type { Tag } from './tag';

export type SearchSort = 'relevance' | 'title_asc' | 'title_desc' | 'newest' | 'oldest';

export type SearchFilters = {
    q: string;
    status: string | null;
    tag_ids: number[];
    per_page: number;
    sort: SearchSort;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Laravel paginator shape returned by paginate() */
export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

export type SearchPageProps = {
    documents: Paginator<Document>;
    allTags: Tag[];
    filters: SearchFilters;
};
