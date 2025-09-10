// Global route helper function for templates
declare global {
    function route(name: string, params?: any): string;
}

// Simple route helper implementation
(window as any).route = function (name: string, params?: any): string {
    const routes: { [key: string]: string | ((params?: any) => string) } = {
        'crm.contacts.index': '/crm/contacts',
        'crm.contacts.create': '/crm/contacts/create',
        'crm.contacts.show': (id: number) => `/crm/contacts/${id}`,
        'crm.contacts.edit': (id: number) => `/crm/contacts/${id}/edit`,
        'crm.contacts.store': '/crm/contacts',
        'crm.contacts.update': (id: number) => `/crm/contacts/${id}`,
        'crm.contacts.destroy': (id: number) => `/crm/contacts/${id}`,
        'crm.contacts.web_restore': (id: number) => `/crm/contacts/${id}/restore`,
        'dashboard': '/dashboard',
    };

    const route = routes[name];
    if (typeof route === 'function') {
        return route(params);
    }
    return route || '#';
};