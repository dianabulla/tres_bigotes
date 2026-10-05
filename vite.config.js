import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  root: 'view',
  envDir: '..',
  publicDir: 'public',
  plugins: [react()],
})
