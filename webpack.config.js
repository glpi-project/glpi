/*
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const glob = require('glob');
const path = require('path');

const libOutputPath = 'public/lib';

/*
 * GLPI core files build configuration.
 */
var glpiConfig = {
    entry: {
        'glpi': path.resolve(__dirname, 'js/main.js'),
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'public/build'),
    },
};

/*
 * External libraries files build configuration.
 */
var libsConfig = {
    entry: function () {
        // Create an entry per *.js file in lib/bundle directory.
        // Entry name will be name of the file (without ext).
        var entries = {};

        let files = glob.sync(path.resolve(__dirname, 'lib/bundles') + '/*.js');
        files.forEach(function (file) {
            entries[path.basename(file, '.js')] = file;
        });

        return entries;
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, libOutputPath),
    },
    module: {
        rules: [
            {
                // Load scripts with no compilation for packages that are directly providing "dist" files.
                // This prevents useless compilation pass and can also 
                // prevents incompatibility issues with the webpack require feature.
                test: /\.js$/,
                include: [
                    path.resolve(__dirname, 'node_modules/@fullcalendar'),
                    path.resolve(__dirname, 'node_modules/codemirror'),
                    path.resolve(__dirname, 'node_modules/gridstack'),
                    path.resolve(__dirname, 'node_modules/jstree'),
                    path.resolve(__dirname, 'node_modules/spectrum-colorpicker'),
                ],
                use: ['script-loader'],
            },
            {
                // Build styles
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
                // Copy images and fonts
                test: /\.((gif|png|jp(e?)g)|(eot|ttf|svg|woff2?))$/,
                use: {
                    loader: 'file-loader',
                    options: {
                        name: function (filename) {
                            // Keep only relative path
                            var sanitizedPath = path.relative(__dirname, filename);

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
            },
        ],
    },
    plugins: [
        new CleanWebpackPlugin(), // Clean lib dir content
        new MiniCssExtractPlugin({ filename: '[name].css' }), // Extract styles into CSS files
    ]
};

var libs = {
    '@fullcalendar': [
        {
            context: 'core',
            from: 'locales/*.js',
        }
    ],
    'jquery-ui': [
        {
            context: 'ui',
            from: 'i18n/*.js',
        }
    ],
    'jquery-ui-timepicker-addon': [
        {
            context: 'dist',
            from: 'i18n/jquery-ui-timepicker-*.js',
            ignore: ['i18n/jquery-ui-timepicker-addon-i18n{,.min}.js'],
        }
    ],
    'select2': [
        {
            context: 'dist',
            from: 'js/i18n/*.js',
        }
    ],
    'tinymce-i18n': [
        {
            from: 'langs/*.js',
        }
    ],
};

for (let packageName in libs) {
    let libPackage = libs[packageName];
    let to = libOutputPath + '/' + packageName.replace(/^@/, ''); // remove leading @ in case of prefixed package

    for (let e = 0; e < libPackage.length; e++) {
        let packageEntry = libPackage[e];

        let context = 'node_modules/' + packageName;
        if (packageEntry.hasOwnProperty('context')) {
            context += '/' + packageEntry.context;
        }

        let copyParams = {
            context: path.resolve(__dirname, context),
            from:    packageEntry.from,
            to:      path.resolve(__dirname, to),
            toType:  'dir',
        };

        if (packageEntry.hasOwnProperty('ignore')) {
            copyParams.ignore = packageEntry.ignore;
        }

        libsConfig.plugins.push(new CopyWebpackPlugin([copyParams]));
    }
}

module.exports = (env, argv) => {
    var configs = [glpiConfig, libsConfig];

    for (let config of configs) {
        // Limit verbosity to only usefull informations
        config.stats = {
            all: false,
            errors: true,
            errorDetails: true,
            warnings: true,

            entrypoints: true,
            timings: true,
        };

        if (argv.mode === 'development') {
            config.devtool = 'source-map';
        }
    }

    return configs;
};
