export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | undefined;
    logo_url?: string;
    api_key?: string;
    is_admin?: boolean;
    album_display_settings?: {
        main_color?: string;
        secondary_color?: string;
        caption?: boolean;
        altText?: boolean;
        dateCreated?: boolean;
        location?: boolean;
        tags?: boolean;
        title?: boolean;
        author?: boolean;
    };
}

export interface PageProps {
    auth: {
        user: User;
    };
    is_impersonating?: boolean;
    ziggy: {
        location: string;
        url: string;
        port: number;
        defaults: Record<string, any>;
        routes: Record<string, any>;
    };
    [key: string]: any;
} 