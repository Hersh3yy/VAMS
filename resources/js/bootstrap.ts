import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set the base URL for axios to match the current origin
window.axios.defaults.baseURL = window.location.origin;
