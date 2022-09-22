import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
const { resolve } = require('path')

// https://vitejs.dev/config/
export default defineConfig({
    build: {
        emptyOutDir: true,
        minify: process.env.NODE_ENV === "production",
        sourcemap: process.env.NODE_ENV !== "production",
        cssCodeSplit: false,
        rollupOptions: {
            external: ["vue", /^@vue-mf\/.+/],
            input: resolve(__dirname, "src/main.js"),
            output: {
                dir: null,
                file: "../../../storage/public/vue-mf/kanban/vue-mf-kanban.min.js",
                manualChunks: undefined,
                format: "system",
                assetFileNames: "assets/vue-mf-kanban.min[extname]"
            },
            preserveEntrySignatures: true
        }
    },
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: resolve(__dirname, "/storage/public/vue-mf/kanban/assets")
                }
            }
        })
    ]
});
