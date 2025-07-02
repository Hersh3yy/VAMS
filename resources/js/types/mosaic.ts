export interface Album {
    id: string;
    title: string;
    description: string;
    created_at: string;
    updated_at: string;
    cover_image_path?: string;
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
    created_at: string;
    updated_at: string;
}

export interface Mosaic {
    id: string;
    title: string;
    description?: string;
    columns: number;
    display_settings?: any;
    created_at: string;
    updated_at: string;
    items: MosaicItem[];
}

export interface MosaicItem {
    id: string;
    type: 'album' | 'media' | 'color' | 'text' | 'video';
    column_index: number;
    order: number;
    content?: string | string[];
    album_id?: string;
    album?: Album;
    properties?: MosaicItemProperties;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface MosaicItemProperties {
    // Common properties
    aspect_ratio?: string;
    height?: number;
    title?: string;
    caption?: string;
    has_link?: boolean;
    link_url?: string;
    edit_text?: string;

    // Album properties
    album_id?: string | null;
    album?: {
        id: string;
        title: string;
        cover_image_path?: string;
        images?: AlbumImage[];
    };
    selected_image?: {
        id: string;
        path: string;
        title?: string | null;
        caption?: string | null;
        properties?: any;
    };

    // Media properties
    media_url?: string | null;
    media?: {
        type: 'image' | 'video';
        path: string;
        position?: string;
        scale?: number;
        mime_type?: string;
        original_name?: string;
        size?: number;
        webp_url?: string;
    };

    // Color properties
    color?: string;

    // Text overlay properties
    show_text?: boolean;
    text?: {
        enabled?: boolean;
        content: string;
        color: string;
    };
}

export interface MosaicDisplaySettings {
    grid_columns: number;
    gap: number;
    padding: number;
    show_titles: boolean;
    show_captions: boolean;
}

export interface MosaicUploadProgress {
    percentage: number;
    status: 'uploading' | 'complete' | 'error';
    error?: string;
}

export interface MosaicConfirmation {
    title: string;
    message: string;
    action: () => void;
} 