import { defineConfig, devices } from '@playwright/test';
import { config } from 'dotenv' ;
import { Config } from './tests/playwright/utils/Config';

// Load .env file so it is available everywhere.
config({path: './tests/playwright/.env'});

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

    // Stop the tests after 15 failures on the CI
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
            // Will setup the needed config to run the tests (clean previous tests
            // data + enable API + create entities and users).
            name: "setup",
            testDir: "tests/playwright/setup",
            testMatch: /\.setup\.ts/,
        },
        {
            // This project contains tests that are safe to run in parallel.
            // All tests should be here by default.
            name: 'shared',
            use: { ...devices['Desktop Chrome'] },
            testDir: 'tests/playwright/specs/shared',
            dependencies: ['setup'],
        },
        {
            // This project must contain any tests that will modify the global
            // state of the application: global display preferences, general
            // configuration, ...
            // It will be executed after the 'shared' suite to reduce the risk of
            // messing with the others tests.
            // Ideally, it  should be forced to run with a single worker too
            // but playwright do not support setting the worker setting at a per
            // project level.
            // See: https://github.com/microsoft/playwright/issues/21970.
            name: 'isolated',
            use: { ...devices['Desktop Chrome'] },
            testDir: 'tests/playwright/specs/isolated',
            dependencies: ['setup', 'shared'],
        },
    ],
});
