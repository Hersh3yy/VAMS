import { onKeyStroke } from '@vueuse/core';
import { nextTick, watch, type Ref } from 'vue';

const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Traps keyboard focus within a container while it is visible, restores focus to the
 * previously focused element on close, and emits a close request on Escape.
 *
 * Intended for dialog/modal-style overlays that render conditionally via `v-if`.
 */
export function useFocusTrap(panelRef: Ref<HTMLElement | null>, isOpen: Ref<boolean>, onClose: () => void) {
    let previouslyFocusedElement: HTMLElement | null = null;

    function getFocusableElements(): HTMLElement[] {
        if (!panelRef.value) {
            return [];
        }
        return Array.from(panelRef.value.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR));
    }

    watch(isOpen, async opened => {
        if (opened) {
            previouslyFocusedElement = document.activeElement as HTMLElement | null;
            await nextTick();
            getFocusableElements()[0]?.focus();
        } else {
            previouslyFocusedElement?.focus();
            previouslyFocusedElement = null;
        }
    });

    onKeyStroke('Escape', () => {
        if (isOpen.value) {
            onClose();
        }
    });

    onKeyStroke('Tab', event => {
        if (!isOpen.value) {
            return;
        }

        const focusable = getFocusableElements();
        if (focusable.length === 0) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const current = document.activeElement;

        if (event.shiftKey && current === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && current === last) {
            event.preventDefault();
            first.focus();
        } else if (!panelRef.value?.contains(current)) {
            event.preventDefault();
            first.focus();
        }
    });
}
