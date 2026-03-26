import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
// import { VitePWA } from 'vite-plugin-pwa' // TEMPORARILY DISABLED
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

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
        // VitePWA({
        //     registerType: 'autoUpdate',
        //     workbox: {
        //         globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        //         cleanupOutdatedCaches: true,
        //         skipWaiting: true,
        //         clientsClaim: true
        //     },
        //     includeAssets: ['favicon.ico', 'apple-touch-icon.png'],
        //     manifest: {
        //         name: 'Portail RH PNMLS',
        //         short_name: 'RH PNMLS',
        //         description: 'Gestion Intégrée des Ressources Humaines - Programme National Multisectoriel de Lutte contre le Sida',
        //         theme_color: '#0077B5',
        //         background_color: '#ffffff',
        //         display: 'standalone',
        //         orientation: 'portrait-primary',
        //         scope: '/',
        //         start_url: '/',
        //         icons: [
        //             {
        //                 src: 'pwa-192x192.png',
        //                 sizes: '192x192',
        //                 type: 'image/png'
        //             },
        //             {
        //                 src: 'pwa-512x512.png',
        //                 sizes: '512x512',
        //                 type: 'image/png'
        //             },
        //             {
        //                 src: 'pwa-512x512.png',
        //                 sizes: '512x512',
        //                 type: 'image/png',
        //                 purpose: 'any maskable'
        //             }
        //         ]
        //     }
        // })

        // PWA TEMPORARILY DISABLED - Will re-enable after deployment verification
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
