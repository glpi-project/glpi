
const webpack = require('webpack');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const { globSync } = require('glob');
const path = require('path');

const libOutputPath = 'public/lib';
const scssOutputPath = 'css/lib';

/*
 * External libraries files build configuration.
 */
let config = {
    entry: function () {
        // Create an entry per *.js file in lib/bundle directory.
        // Entry name will be name of the file (without ext).
        let entries = {};

        const files = globSync(path.resolve(__dirname, 'lib/bundles') + '/!(*.min).js');
        for (const file of files) {
            entries[path.basename(file, '.js')] = file;
        }

        return entries;
    },
    output: {
        path: path.resolve(__dirname, libOutputPath),
        publicPath: '', // keep URLs relative to output path
    },
    module: {
        rules: [
            {
            // Load scripts with no compilation for packages that are directly providing "dist" files.
            // This prevents useless compilation pass and can also
            // prevents incompatibility issues with the webpack require feature.
            // It also removes existing sourcemaps that cannot be used correctly.
                test: /\.js$/,
                include: [
                    path.resolve(__dirname, 'node_modules/@fullcalendar'),
                    path.resolve(__dirname, 'node_modules/codemirror'),
                    path.resolve(__dirname, 'node_modules/cystoscape'),
                    path.resolve(__dirname, 'node_modules/cytoscape-context-menus'),
                    path.resolve(__dirname, 'node_modules/jquery-migrate'),
                    path.resolve(__dirname, 'node_modules/photoswipe'),
                    path.resolve(__dirname, 'node_modules/rrule'),
                    path.resolve(__dirname, 'lib/blueimp/jquery-file-upload'),
                ],
                use: ['script-loader', 'strip-sourcemap-loader'],
            },
            {
                // Test for a polyfill (or any file) and it won't be included in your
                // bundle
                test: path.resolve(__dirname, 'node_modules/jquery.fancytree/dist/modules/jquery.fancytree.ui-deps.js'),
                use: 'null-loader',
            },
            {
            // Build styles
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
            // Copy images and fonts
                test: /\.((gif|png|jp(e?)g)|(eot|ttf|svg|woff2?))$/,
                type: 'asset/resource',
                generator: {
                    filename: function (pathData) {
                        // Keep only relative path
                        var sanitizedPath = path.relative(__dirname, pathData.filename);

                        // Sanitize name
                        sanitizedPath = sanitizedPath.replace(/[^\\/\w-.]/, '');

                        // Remove the first directory (lib, node_modules, ...) and empty parts
                        // and replace directory separator by '/' (windows case)
                        sanitizedPath = sanitizedPath.split(path.sep)
                            .filter(function (part, index) {
                                return '' != part && index != 0;
                            }).join('/');

                        return sanitizedPath;
                    },
                },
            },
        ],
    },
    plugins: [
        new webpack.ProvidePlugin(
            {
                process: 'process/browser', // required by some libs (including `popper.js`)
            }
        ),
        new CleanWebpackPlugin(
            {
                cleanOnceBeforeBuildPatterns: [
                    path.join(process.cwd(), libOutputPath + '/**/*'),
                    path.join(process.cwd(), scssOutputPath + '/**/*')
                ]
            }
        ), // Clean lib dir content
        new MiniCssExtractPlugin(), // Extract styles into CSS files
    ],
    resolve: {
        // Use only main file in requirement resolution as we do not yet handle modules correctly
        mainFields: [
            'main',
        ],
    },
    mode: 'none', // Force 'none' mode, as optimizations will be done on release process
    devtool: 'source-map', // Add sourcemap to files
    stats: {
        // Limit verbosity to only usefull information
        all: false,
        errors: true,
        errorDetails: true,
        warnings: true,

        entrypoints: true,
        timings: true,
    },
};

// Copy raw JS and SCSS files
var filesToCopy = [
    // JS files
    {
        package: '@fullcalendar/core',
        from: 'locales/*.js',
    },
    {
        package: 'flatpickr',
        context: 'dist',
        from: 'l10n/*.js',
    },
    {
        package: 'flatpickr',
        context: 'dist',
        from: 'themes/*.css',
    },
    {
        package: 'select2',
        context: 'dist',
        from: 'js/i18n/*.js',
    },
    {
        package: 'tinymce',
        from: 'skins/**/*',
    },
    {
        package: 'tinymce-i18n',
        from: 'langs6/*.js',
    },
    // SCSS files
    {
        package: '@fontsource/inter',
        from: '{scss/mixins.scss,files/*all-[0-9]00*.woff,files/*[0-9]00*.woff2}',
        to: scssOutputPath,
    },
    {
        package: '@tabler/core',
        from: 'src/scss/**/*.scss',
        to: scssOutputPath,
    },
    {
        package: '@tabler/icons-webfont',
        from: '{fonts/*,tabler-icons.scss}',
        to: scssOutputPath,
    },
    {
        package: 'bootstrap',
        from: 'scss/**/*.scss',
        to: scssOutputPath,
    },
    {
        package: 'select2',
        from: 'src/scss/**/*.scss',
        to: scssOutputPath,
    },
];

let copyPatterns = [];
for (let s = 0; s < filesToCopy.length; s++) {
    let specs = filesToCopy[s];
    let to = (specs.to || libOutputPath) + '/' + specs.package.replace(/^@/, ''); // remove leading @ in case of prefixed package

    let context = 'node_modules/' + specs.package;
    if (Object.prototype.hasOwnProperty.call(specs, 'context')) {
        context += '/' + specs.context;
    }

    let copyParams = {
        context: path.resolve(__dirname, context),
        from:    specs.from,
        to:      path.resolve(__dirname, to),
        toType:  'dir',
    };

    if (Object.prototype.hasOwnProperty.call(specs, 'ignore')) {
        copyParams.ignore = specs.ignore;
    }

    copyPatterns.push(copyParams);
}

config.plugins.push(new CopyWebpackPlugin({patterns:copyPatterns}));

module.exports = config;
