import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { VitePWA } from 'vite-plugin-pwa'
import path from 'path'
import fs from 'fs'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

// Plugin to copy .htaccess and serve-asset.php into public/build/ after build
function copyBuildAssets() {
    return {
        name: 'copy-build-assets',
        closeBundle() {
            const filesToCopy = ['.htaccess', 'serve-asset.php']
            for (const file of filesToCopy) {
                const src = path.resolve(__dirname, 'build', file)
                const dest = path.resolve(__dirname, 'public/build', file)
                if (fs.existsSync(src)) {
                    fs.copyFileSync(src, dest)
                }
            }
        }
    }
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    // Don't resolve absolute URLs as module imports
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            workbox: {
                globPatterns: ['**/*.{js,css,woff2,png,jpg,jpeg,svg,webmanifest,json}'],
                cleanupOutdatedCaches: true,
                skipWaiting: true,
                clientsClaim: true,
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pnmls-pages',
                            networkTimeoutSeconds: 3,
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 7 * 24 * 60 * 60,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ sameOrigin, url }) => sameOrigin && url.pathname.startsWith('/build/assets/'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'pnmls-build-assets',
                            expiration: {
                                maxEntries: 80,
                                maxAgeSeconds: 30 * 24 * 60 * 60,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ sameOrigin, request, url }) => sameOrigin
                            && request.destination === 'image'
                            && (
                                url.pathname.startsWith('/images/')
                                || url.pathname.startsWith('/build/assets/')
                                || url.pathname.startsWith('/pwa-')
                            ),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'pnmls-images',
                            expiration: {
                                maxEntries: 96,
                                maxAgeSeconds: 30 * 24 * 60 * 60,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
            },
            includeAssets: ['favicon.ico', 'images/icons/*.png', 'pwa-192x192.png', 'pwa-512x512.png'],
            manifest: {
                id: '/',
                name: 'E-PNMLS',
                short_name: 'E-PNMLS',
                description: 'Gestion Intégrée des Ressources Humaines - Programme National Multisectoriel de Lutte contre le Sida',
                theme_color: '#006c9f',
                background_color: '#f8fcff',
                display: 'standalone',
                display_override: ['window-controls-overlay', 'standalone', 'browser'],
                orientation: 'portrait-primary',
                scope: '/',
                start_url: '/',
                lang: 'fr',
                categories: ['business', 'productivity'],
                icons: [
                    {
                        src: 'images/icons/pnmls-48.png',
                        sizes: '48x48',
                        type: 'image/png'
                    },
                    {
                        src: 'images/icons/pnmls-64.png',
                        sizes: '64x64',
                        type: 'image/png'
                    },
                    {
                        src: 'images/icons/pnmls-128.png',
                        sizes: '128x128',
                        type: 'image/png'
                    },
                    {
                        src: 'images/icons/pnmls-192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: 'images/icons/pnmls-256.png',
                        sizes: '256x256',
                        type: 'image/png'
                    },
                    {
                        src: 'images/icons/pnmls-512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    },
                    {
                        src: 'images/icons/pnmls-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
                    }
                ],
                shortcuts: [
                    {
                        name: 'Accueil',
                        short_name: 'Accueil',
                        url: '/',
                        icons: [{ src: 'images/icons/pnmls-192.png', sizes: '192x192' }]
                    },
                    {
                        name: 'Mes tâches',
                        short_name: 'Tâches',
                        url: '/taches',
                        icons: [{ src: 'images/icons/pnmls-192.png', sizes: '192x192' }]
                    },
                    {
                        name: 'Documents',
                        short_name: 'Docs',
                        url: '/documents',
                        icons: [{ src: 'images/icons/pnmls-192.png', sizes: '192x192' }]
                    },
                    {
                        name: 'Congés',
                        short_name: 'Congés',
                        url: '/mon-planning-conges',
                        icons: [{ src: 'images/icons/pnmls-192.png', sizes: '192x192' }]
                    }
                ]
            }
        }),
        copyBuildAssets(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        proxy: {
            '/api': 'http://localhost',
            '/sanctum': 'http://localhost',
        },
    },
})
