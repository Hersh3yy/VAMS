import { onMounted, ref, watch } from 'vue';

const STORAGE_KEY = 'vams-dark-mode';

// Shared across every component instance so the toggle stays in sync app-wide.
const isDark = ref(true);
let initialized = false;

function applyClass(dark: boolean) {
    document.documentElement.classList.toggle('dark', dark);
}

/**
 * User-controlled dark mode toggle, persisted to localStorage. Defaults to
 * dark (matching the app's previous always-on behavior) so existing users
 * see no change until they opt out.
 */
export function useDarkMode() {
    onMounted(() => {
        if (initialized) {
            applyClass(isDark.value);

            return;
        }

        initialized = true;
        const stored = localStorage.getItem(STORAGE_KEY);
        isDark.value = stored === null ? true : stored === 'true';
        applyClass(isDark.value);
    });

    watch(isDark, dark => {
        applyClass(dark);
        localStorage.setItem(STORAGE_KEY, String(dark));
    });

    const toggleDarkMode = () => {
        isDark.value = !isDark.value;
    };

    return { isDark, toggleDarkMode };
}
