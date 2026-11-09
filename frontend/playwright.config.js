import {defineConfig, devices} from '@playwright/test';
export default defineConfig({
  testDir: './tests/browser', fullyParallel: false, workers: 1, retries: 0,
  timeout: 45000, expect: {timeout: 10000},
  reporter: [['list'], ['html', {open:'never'}]],
  use: {baseURL: process.env.TEST_BASE_URL || 'http://127.0.0.1:8080', trace:'retain-on-failure', screenshot:'only-on-failure'},
  projects: [
    {name:'desktop',use:{...devices['Desktop Chrome'],viewport:{width:1440,height:1000}}},
    {name:'mobile-360',use:{...devices['Pixel 5'],viewport:{width:360,height:800}}},
    {name:'mobile-430',use:{...devices['Pixel 5'],viewport:{width:430,height:932}}}
  ]
});
