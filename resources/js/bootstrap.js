import axios from 'axios';

// Make Axios available to any page that needs an AJAX request.
window.axios = axios;

// Mark browser requests so Laravel can distinguish them from standard form submits.
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
