import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const isCodespaces = !!process.env.CODESPACES;
const host = '0.0.0.0';
// In Codespaces, construct the forwarding domain if not provided explicitly.
const forwardingDomain = process.env.GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN; // e.g. app.github.dev
const codespaceName = process.env.CODESPACE_NAME; // e.g. owner-project-abc123
const hmrPort = Number(process.env.VITE_HMR_PORT || 5173);
const derivedCodespacesHost =
    codespaceName && forwardingDomain ? `${codespaceName}-${hmrPort}.${forwardingDomain}` : undefined;
const hmrHost = process.env.VITE_HMR_HOST || derivedCodespacesHost || 'localhost';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host,
        port: 5173,
        strictPort: true,
        hmr: isCodespaces
            ? {
                  host: hmrHost,
                  // On Codespaces, HMR is served via TLS on port 443 with the original port encoded in the hostname.
                  port: 443,
                  clientPort: 443,
                  protocol: 'wss',
              }
            : { host: 'localhost', port: 5173 },
    },
});
