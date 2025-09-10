// Simple route helpers until Wayfinder routes are generated
export const dashboard = () => '/dashboard';
export const login = () => '/login';
export const register = () => '/register';
export const home = () => '/';

// Admin routes
export const admin = {
    dashboard: () => ({ url: '/admin' }),
};

// CRM routes
export const contacts = {
    index: () => '/crm/contacts',
    create: () => '/crm/contacts/create',
    show: (id: number) => `/crm/contacts/${id}`,
    edit: (id: number) => `/crm/contacts/${id}/edit`,
};