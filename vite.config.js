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
                globPatterns: ['**/*.{js,css,woff2}'],
                cleanupOutdatedCaches: true,
                skipWaiting: true,
                clientsClaim: true,
                navigateFallback: null,
            },
            includeAssets: ['favicon.ico', 'apple-touch-icon.png'],
            manifest: {
                name: 'E-PNMLS',
                short_name: 'E-PNMLS',
                description: 'Gestion Intégrée des Ressources Humaines - Programme National Multisectoriel de Lutte contre le Sida',
                theme_color: '#0077B5',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait-primary',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: 'pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: 'pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    },
                    {
                        src: 'pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
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
