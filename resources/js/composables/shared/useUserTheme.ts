import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

/**
 * Converts a `#rrggbb` hex color into a space-separated `r g b` triplet, the
 * format Tailwind's `rgb(var(--x) / <alpha-value>)` color functions expect so
 * opacity modifiers like `bg-secondary/20` work against user-supplied colors.
 */
function hexToRgbTriplet(hex: string, fallback: string): string {
    const match = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

    if (!match) {
        return fallback;
    }

    const [, r, g, b] = match;

    return [r, g, b].map(channel => parseInt(channel, 16)).join(' ');
}

/**
 * Applies the current user's saved main/secondary colors (or the app-wide
 * defaults shared from config/theme.php) as CSS custom properties. Used by
 * every authenticated layout so theming stays consistent regardless of
 * which layout a given page renders through.
 */
export function useUserTheme() {
    const page = usePage<PageProps>();

    const themeStyle = computed(() => {
        const settings = page.props.auth?.user?.album_display_settings;
        const defaults = page.props.theme_defaults;

        const mainColor = settings?.main_color || defaults?.main_color || '#000000';
        const secondaryColor = settings?.secondary_color || defaults?.secondary_color || '#EAB308';

        return {
            '--primary-color': hexToRgbTriplet(mainColor, '0 0 0'),
            '--secondary-color': hexToRgbTriplet(secondaryColor, '234 179 8')
        };
    });

    watch(
        themeStyle,
        newStyle => {
            Object.entries(newStyle).forEach(([key, value]) => {
                document.documentElement.style.setProperty(key, value);
            });
        },
        { immediate: true }
    );

    return { themeStyle };
}
