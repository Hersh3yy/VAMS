declare module 'vue3-toastify' {
    import { Plugin } from 'vue';

    export interface ToastOptions {
        position?:
            | 'top-right'
            | 'top-center'
            | 'top-left'
            | 'bottom-right'
            | 'bottom-center'
            | 'bottom-left';
        autoClose?: number;
        hideProgressBar?: boolean;
        closeOnClick?: boolean;
        pauseOnHover?: boolean;
    }

    export const toast: {
        success: (message: string, options?: ToastOptions) => void;
        error: (message: string, options?: ToastOptions) => void;
        info: (message: string, options?: ToastOptions) => void;
        warning: (message: string, options?: ToastOptions) => void;
    };

    const ToastPlugin: Plugin;
    export default ToastPlugin;
}
