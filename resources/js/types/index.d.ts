import { Config } from 'ziggy-js';

export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at?: string;
    is_admin?: boolean;
    is_approved?: boolean;
    approved_at?: string;
    created_at?: string;
    updated_at?: string;
    logo_url?: string;
    album_display_settings?: {
        caption?: boolean;
        altText?: boolean;
        dateCreated?: boolean;
        location?: boolean;
        tags?: boolean;
        title?: boolean;
        author?: boolean;
        main_color?: string;
        secondary_color?: string;
    };
    theme_settings?: Record<string, any>;
    site_settings?: Record<string, any>;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
    flash?: {
        success?: string;
        error?: string;
    };
};
