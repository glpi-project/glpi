import { defineConfig, devices } from '@playwright/test';
import { config } from 'dotenv' ;
import { Config } from './tests/playwright/utils/Config';

// Load .env file so it is available everywhere.
config({path: './tests/playwright/.env.local', quiet: true});
config({path: './tests/playwright/.env', quiet: true});

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
    testDir: './tests/playwright',

    // Run tests in files in parallel
    fullyParallel: true,

    // Fail the build on CI if you accidentally left test.only in the source code
    forbidOnly: !!process.env.CI,

    // Retry on CI only
    retries: process.env.CI ? 2 : 0,

    // Reporter to use. See https://playwright.dev/docs/test-reporters
    reporter: process.env.CI ? [
        ['html'],
        ['dot'],
        ['playwright-ctrf-json-reporter']
    ] : 'html',

    // Stop the tests after 15 failures on the CI. Prevent wasting time on a total failure scenario.
    maxFailures: process.env.CI ? 15 : undefined,

    // Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions.
    use: {
        // Base URL to use in actions like `await page.goto('/')`.
        baseURL: Config.getBaseUrl(),

        // Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer
        trace: 'on-first-retry',
    },

    // Folder for test artifacts such as screenshots, videos, traces, etc.
    outputDir: 'tests/playwright/test-results',

    // Configure projects for major browsers
    projects: [
        {
            // Execute the PHP bootstrap script that will setup the data on the
            // first run and reinitialize global parameters if needed.
            name: "bootstrap",
            testDir: "tests/playwright/setup/",
            testMatch: /bootstrap\.setup\.ts/,
        },
        {
            // This project contains tests that are safe to run in parallel and
            // do not modify GLPI's global configuration.
            // All tests should be here by default.
            name: 'main',
            use: { ...devices['Desktop Chrome'] },
            testDir: 'tests/playwright/specs/main',
            dependencies: ['bootstrap'],
        },
    ],
});
