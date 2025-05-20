export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    album_display_settings?: {
        caption: boolean;
        altText: boolean;
        dateCreated: boolean;
        location: boolean;
        tags: boolean;
        title: boolean;
        author: boolean;
        main_color?: string;
        secondary_color?: string;
    };
    api_key: string;
} 