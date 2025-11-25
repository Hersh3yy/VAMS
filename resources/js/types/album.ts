export interface Album {
    id: string;
    title: string;
    description: string;
    created_at: string;
    updated_at: string;
    cover_image_path?: string;
    published?: boolean;
    images?: AlbumImage[];
}

export interface AlbumImage {
    id: string;
    album_id: string;
    path: string;
    title?: string | null;
    caption?: string | null;
    alt_text?: string | null;
    author?: string | null;
    date_created?: string | null;
    location?: string | null;
    tags?: string | null;
    properties?: {
        type?: 'video';
        video_url?: string;
        thumbnail_url?: string;
        [key: string]: any;
    } | null;
    order: number;
    published?: boolean;
    created_at: string;
    updated_at: string;
}

export interface AlbumVideo {
    id: string;
    album_id: string;
    path: string;
    title?: string | null;
    caption?: string | null;
    embed_url: string;
    order: number;
    created_at: string;
    updated_at: string;
}

export interface AlbumDisplaySettings {
    grid_columns: number;
    show_titles: boolean;
    show_captions: boolean;
}

export interface AlbumUploadProgress {
    uploading: boolean;
    progress: number;
}

export interface AlbumConfirmation {
    show: boolean;
    title: string;
    message: string;
    action: () => void;
}
