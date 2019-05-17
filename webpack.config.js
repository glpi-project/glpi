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

const CleanWebpackPlugin = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const path = require('path');

const libOutputPath = 'public/lib';

/*
 * GLPI core files build configuration.
 */
var glpiConfig = {
    entry: {
        'glpi': './js/main.js',
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
    entry: {
        'jquery-ui': path.resolve(__dirname, 'node_modules/jquery-ui/themes/base/all.css'),
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, libOutputPath),
    },
    optimization: {
        splitChunks: {
            cacheGroups: {
                // Force jQueryUI CSS to be bundled into a unique file
                jQueryUiCss: {
                    name: 'jquery-ui',
                    test: /jquery-ui\/themes\/base\/all\.css$/,
                    chunks: 'all',
                    enforce: true
                },
            },
        },
    },
    module: {
        rules: [
            {
                // Build jQuery UI styles
                test: /\.css$/,
                include: path.resolve(__dirname, 'node_modules/jquery-ui'),
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
                // Convert images to base64 strings
                test: /\.(png|jp(e*)g|svg)$/,
                use: ['url-loader']
            },
        ],
    },
    plugins: [
        new CleanWebpackPlugin([libOutputPath]), // Clean lib dir content
        new MiniCssExtractPlugin({ filename: '[name]/[name].css' }), // Extract styles into CSS files
    ]
};

var libs = {
    /*
     * Nota:
     * PHP file 'UploadHandler.php' is not fetch when installing with npm and is not available
     * on packagist repository.
     * This dependency is managed manually for the moment.
    'blueimp-file-upload': [
        {
            from: '{js/jquery.fileupload.js,js/jquery.iframe-transport.js}',
        }
    ],
    */
    'chartist': [
        {
            context: 'dist',
            from: '{chartist.css,chartist.js}',
        }
    ],
    'chartist-plugin-legend': [
        {
            from: 'chartist-plugin-legend.js',
        }
    ],
    'chartist-plugin-tooltips': [
        {
            context: 'dist',
            from: '{chartist-plugin-tooltip.css,chartist-plugin-tooltip.js}',
        }
    ],
    'codemirror': [
        {
            context: 'lib',
            from: '{codemirror.css,codemirror.js}',
        },
        {
            from: 'addon/fold/{brace-fold.js,comment-fold.js,foldcode.js,foldgutter.css,foldgutter.js}',
        },
        {
            from: 'addon/hint/{css-hint.js,show-hint.css,show-hint.js}',
        },
        {
            from: 'mode/css/{css.js,}',
        }
    ],
    'diff-match-patch':[
        {
            from: 'index.js',
        }
    ],
    '@fortawesome/fontawesome-free': [
        {
            from: '{css/all.css,webfonts/*}',
        }
    ],
    'fullcalendar': [
        {
            context: 'dist',
            from: '{fullcalendar{,.print}.css,fullcalendar.js,locale/*.js}',
        }
    ],
    'fuzzy': [
        {
            context: 'lib',
            from: 'fuzzy.js',
        }
    ],
    'gridstack': [
        {
            context: 'dist',
            from: '{gridstack{,-extra}.css,gridstack{,.jQueryUI}.js}',
        }
    ],
    'jquery': [
        {
            context: 'dist',
            from: 'jquery.js',
        }
    ],
    'jquery-mousewheel': [
        {
            from: 'jquery.mousewheel.js',
        }
    ],
    'jquery-prettytextdiff': [
        {
            from: 'jquery.pretty-text-diff.js',
        }
    ],
    'jquery-ui': [
        {
            context: 'ui',
            from: 'i18n/*.js',
        }
    ],
    'jquery-ui-dist': [
        {
            from: 'jquery-ui.js',
        }
    ],
    'jquery-ui-timepicker-addon': [
        {
            context: 'dist',
            from: '{jquery-ui-timepicker-addon.css,jquery-ui-timepicker-addon.js,i18n/jquery-ui-timepicker-*.js}',
            ignore: ['i18n/jquery-ui-timepicker-addon-i18n{,.min}.js'],
        }
    ],
    'jquery.autogrow-textarea': [
        {
            from: 'jquery.autogrow-textarea.js',
        }
    ],
    'jquery.rateit': [
        {
            context: 'scripts',
            from: '{jquery.rateit.js,rateit.css,*.gif}',
        }
    ],
    'jstree': [
        {
            context: 'dist',
            from: 'jstree.js',
        }
    ],
    'leaflet': [
        {
            context: 'dist',
            from: '{leaflet.css,leaflet.js,images/*}',
        }
    ],
    'leaflet-fullscreen': [
        {
            context: 'dist',
            from: '{leaflet.fullscreen.css,Leaflet.fullscreen.js,*.png}',
        }
    ],
    'leaflet-spin': [
        {
            from: 'leaflet.spin.js',
        }
    ],
    'leaflet.awesome-markers': [
        {
            context: 'dist',
            from: '{leaflet.awesome-markers.css,leaflet.awesome-markers.js,images/*}',
        }
    ],
    'leaflet.markercluster': [
        {
            context: 'dist',
            from: '{leaflet.markercluster-src.js,MarkerCluster{,.Default}.css}',
        }
    ],
    'lodash': [
        {
            from: 'lodash.js',
        }
    ],
    'moment': [
        {
            from: '{moment.js,locale/*.js}',
        }
    ],
    'prismjs': [
        {
            from: '{components/prism-{core,apacheconf,bash,clike,json,nginx}.js,themes/prism-coy.css}',
        }
    ],
    'qtip2': [
        {
            context: 'dist',
            from: '{jquery.qtip.css,jquery.qtip.js}',
        }
    ],
    'select2': [
        {
            context: 'dist',
            from: '{css/select2.css,js/select2.full.js,js/i18n/*.js}',
        }
    ],
    'spectrum-colorpicker': [
        {
            from: '{spectrum.css,spectrum.js}',
        }
    ],
    'spin.js': [
        {
            from: 'spin.js',
        }
    ],
    'tinymce': [
        {
            from: '{tinymce.js,plugins/**/*,themes/**/*}',
            ignore: ['*min.css', '*min.js'],
        }
    ],
    'tinymce-i18n': [
        {
            from: 'langs/*.js',
        }
    ],
    'unorm': [
        {
            context: 'lib',
            from: 'unorm.js',
        }
    ],
};

for (let packageName in libs) {
    let libPackage = libs[packageName];
    let to = libOutputPath + '/' + packageName;

    let matches = packageName.match(/^@[^/]*\/(.*)$/);
    if (null !== matches) {
        to = libOutputPath + '/' + matches[1]; // Remove package prefix for destination dir
    }

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
