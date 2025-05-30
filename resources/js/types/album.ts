export interface Album {
    id: number;
    title: string;
    description: string;
    created_at: string;
    updated_at: string;
}

export interface AlbumImage {
    id: number;
    path: string;
    title?: string;
    caption?: string;
    order: number;
    created_at: string;
    updated_at: string;
}

export interface AlbumVideo {
    id: number;
    path: string;
    title?: string;
    caption?: string;
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