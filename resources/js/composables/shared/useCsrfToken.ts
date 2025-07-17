import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useCsrfToken() {
    const page = usePage();
    
    // Function to update the CSRF token in the meta tag
    const updateCsrfToken = (token: string) => {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            const oldToken = metaTag.getAttribute('content');
            metaTag.setAttribute('content', token);
            console.log('CSRF token updated successfully', {
                old_token: oldToken?.substring(0, 8) + '...',
                new_token: token.substring(0, 8) + '...',
                timestamp: new Date().toISOString()
            });
        } else {
            console.error('CSRF meta tag not found');
        }
    };

    // Function to get current CSRF token
    const getCsrfToken = (): string | null => {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag?.getAttribute('content') || null;
    };

    // Watch for CSRF token refresh from the server
    watch(
        () => page.props.csrf_token_refresh as string | undefined,
        (newToken) => {
            if (newToken) {
                console.log('CSRF token refresh received from server', {
                    token: newToken.substring(0, 8) + '...',
                    timestamp: new Date().toISOString()
                });
                updateCsrfToken(newToken);
                // Clear the flash data after using it
                // This will be handled by the server on the next request
            }
        },
        { immediate: true }
    );

    // Function to refresh CSRF token from server
    const refreshCsrfToken = async (): Promise<string> => {
        try {
            const response = await fetch('/csrf-token', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to refresh CSRF token');
            }

            const data = await response.json();
            
            if (data.csrf_token) {
                console.log('CSRF token refreshed from manual request', {
                    token: data.csrf_token.substring(0, 8) + '...',
                    timestamp: new Date().toISOString()
                });
                updateCsrfToken(data.csrf_token);
                return data.csrf_token;
            } else {
                throw new Error('No CSRF token in response');
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
            throw error;
        }
    };

    return {
        getCsrfToken,
        updateCsrfToken,
        refreshCsrfToken
    };
} 