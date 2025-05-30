export interface Album {
    id: number;
    title: string;
    cover_image_path?: string;
}

export interface Mosaic {
    id: number;
    title: string;
    description: string;
    created_at: string;
    updated_at: string;
    items: MosaicItem[];
}

export interface MosaicItem {
    id: number;
    type: 'album' | 'media' | 'color';
    properties: MosaicItemProperties;
    order: number;
    created_at: string;
    updated_at: string;
}

export interface MosaicItemProperties {
    // Album properties
    album_id?: number | null;
    album?: {
        id: number;
        title: string;
        cover_image_path?: string;
    };

    // Media properties
    media_url?: string | null;
    media?: {
        type: 'image' | 'video';
        path: string;
    };

    // Color properties
    color?: string;

    // Text overlay properties
    show_text: boolean;
    text?: {
        enabled: boolean;
        content: string;
        color: string;
    };

    // Common properties
    title: string;
    caption: string;
    has_link: boolean;
    link_url: string;
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