// URLs configuration
// base_url is defined by CodeIgniter - if not available, use relative paths
const baseUrlFn = typeof base_url !== 'undefined' ? base_url : (path) => path;

const ASYNC_URLS = {
    // API endpoints
    api_base: baseUrlFn(''),
    
    // Routes
    dashboard: baseUrlFn('app/dashboard'),
    properties: baseUrlFn('app/properties/all'),
    leads: baseUrlFn('app/leads/all'),
    
    // Add more as needed
};
