import {defineConfig, devices} from '@playwright/test';
export default defineConfig({
 testDir:'./tests/capture',workers:1,retries:0,timeout:120000,
 use:{...devices['Desktop Chrome'],viewport:{width:1440,height:1000},baseURL:process.env.TEST_BASE_URL||'http://127.0.0.1:8080',actionTimeout:10000},
 reporter:'list'
});
