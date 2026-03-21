import { loadEnv, defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const proxyTarget = env.VITE_BACKEND_ORIGIN;

  return {
    plugins: [
      react(),
      VitePWA({
        registerType: 'autoUpdate',
        includeAssets: ['icons/app-icon.svg', 'icons/app-icon-maskable.svg'],
        manifest: {
          name: 'IFOS Mobile PWA',
          short_name: 'IFOS',
          description: 'A React Native Web progressive web app for the IFOS mobile ordering APIs.',
          theme_color: '#0b1726',
          background_color: '#071018',
          display: 'standalone',
          orientation: 'portrait-primary',
          start_url: '/',
          scope: '/',
          icons: [
            {
              src: 'icons/app-icon.svg',
              sizes: 'any',
              type: 'image/svg+xml',
              purpose: 'any'
            },
            {
              src: 'icons/app-icon-maskable.svg',
              sizes: 'any',
              type: 'image/svg+xml',
              purpose: 'maskable'
            }
          ]
        },
        workbox: {
          globPatterns: ['**/*.{js,css,html,svg,ico,png,webmanifest}'],
          runtimeCaching: [
            {
              urlPattern: /\/api\//,
              handler: 'NetworkFirst',
              options: {
                cacheName: 'ifos-api-cache',
                networkTimeoutSeconds: 5,
                expiration: {
                  maxEntries: 50,
                  maxAgeSeconds: 60 * 60 * 24
                }
              }
            }
          ]
        }
      })
    ],
    resolve: {
      alias: [{ find: /^react-native$/, replacement: 'react-native-web' }]
    },
    server: {
      host: '0.0.0.0',
      port: 4173,
      proxy: proxyTarget
        ? {
            '/api': {
              target: proxyTarget,
              changeOrigin: true,
              secure: false
            }
          }
        : undefined
    }
  };
});
