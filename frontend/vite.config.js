import {defineConfig} from 'vite';
export default defineConfig({server:{host:'0.0.0.0',allowedHosts:['terminal.local'],proxy:{'/api':{target:process.env.API_PROXY||'http://127.0.0.1:8081',changeOrigin:false}}}});
