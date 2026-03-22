import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Change the proxy target to your backend (e.g., http://localhost:8000)
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false
      }
    }
  }
})
