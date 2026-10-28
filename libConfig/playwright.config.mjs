import { defineConfig, devices } from '@playwright/test';

// The browser half of the E2E; tests/e2e.sh is the HTTP half. Chromium opens the pages through
// the same *.local domain and TLS ingress a developer's browser uses, so it needs the CA trusted
// the way that browser does (README「ブラウザでの E2E」).
export default defineConfig({
    testDir: '../tests',
    testMatch: 'e2e.spec.mjs',
    outputDir: '../test-results',
    forbidOnly: Boolean(process.env.CI),
    reporter: process.env.CI ? 'github' : 'list',
    projects: [{ name: 'chromium', use: devices['Desktop Chrome'] }],
});
