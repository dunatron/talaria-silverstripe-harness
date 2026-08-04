import { defineConfig } from 'vite';
import path from 'node:path';

const themeRoot = path.resolve(__dirname, 'themes/talaria');

export default defineConfig({
  root: themeRoot,
  build: {
    outDir: path.resolve(themeRoot, 'dist'),
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      input: {
        blog: path.resolve(themeRoot, 'src/ts/main.ts'),
        nav: path.resolve(themeRoot, 'src/ts/nav.ts'),
        main: path.resolve(themeRoot, 'src/scss/main.scss'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name ?? '';
          if (name.endsWith('.css')) {
            return 'css/main.css';
          }
          return 'assets/[name][extname]';
        },
      },
    },
  },
});
