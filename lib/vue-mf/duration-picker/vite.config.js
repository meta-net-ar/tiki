import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
const { resolve } = require('path')

// https://vitejs.dev/config/
export default defineConfig({
    build: {
        emptyOutDir: true,
        minify: process.env.NODE_ENV === 'production',
        // minify: false,
        rollupOptions: {
            external: ['vue', /^@vue-mf\/.+/],
            input: resolve(__dirname, 'src/main.js'),
            output: {
                dir: '../../../storage/public/vue-mf/duration-picker',
                manualChunks: undefined,
                format: 'es',
                assetFileNames: 'assets/[name].[ext]',
                entryFileNames: "vue-mf-duration-picker.min.js",
            },
            preserveEntrySignatures: true
        }
    },
    plugins: [vue({
        template: {
            transformAssetUrls: {
                base: resolve(__dirname, '/storage/public/vue-mf/duration-picker/assets'),
            }
        }
    })]
})
