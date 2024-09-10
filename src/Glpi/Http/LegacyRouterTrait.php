<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Http;

use Symfony\Component\HttpFoundation\Request;

trait LegacyRouterTrait
{
    protected function isTargetAPhpScript(string $path): bool
    {
        // Check extension on path directly to be able to recognize that target is supposed to be a PHP
        // script even if it not exists. This is usefull to send most appropriate response code (i.e. 403 VS 404).
        return preg_match('/^php\d*$/', pathinfo($path, PATHINFO_EXTENSION)) === 1;
    }

    protected function isPathAllowed(string $path): bool
    {
        // Check global exclusion patterns.
        $excluded_path_patterns = [
            // hidden file or `.`/`..` path traversal component
            '\/\.',
            // config files
            '^\/config\/',
            // data files
            '^\/files\/',
            // tests files (should anyway not be present in dist)
            '^\/tests\/',
            // old-styles CLI tools (should anyway not be present in dist)
            '^\/tools\/',
            // `node_modules` and `vendor`, in GLPI root directory or in any plugin root directory
            '^(\/(marketplace|plugins)\/[^\/]+)?\/(node_modules|vendor)\/',
        ];

        if (preg_match('/(' . implode('|', $excluded_path_patterns) . ')/i', $path) === 1) {
            return false;
        }

        // Check rules related to PHP files.
        // Check extension on path even if file not exists, to be able to send a 403 error even if file not exists.
        if ($this->isTargetAPhpScript($path)) {
            $glpi_path_patterns = [
                // `/ajax/` scripts
                'ajax\/',
                // `/front/` scripts
                'front\/',
                // install/update scripts
                'install\/(install|update)\.php$',
            ];

            $plugins_path_patterns = [
                // `/ajax/` scripts
                'ajax\/',
                // `/front/` scripts
                'front\/',
                // `/public/` scripts
                'public\/',
                // Any `index.php` script
                '(.+\/)?index\.php',
                // PHP scripts located in root directory, except `setup.php` and `hook.php`
                '(?!setup\.php|hook\.php)[^\/]+\.php',
                // dynamic CSS and JS files
                '.+\.(css|js)\.php',
                // `reports` plugin specific URLs (used by many plugins)
                'report\/',
            ];

            $allowed_path_pattern = '/^'
                . '('
                . sprintf('\/(%s)', implode('|', $glpi_path_patterns))
                . '|'
                . sprintf('\/(marketplace|plugins)\/[^\/]+\/(%s)', implode('|', $plugins_path_patterns))
                . ')'
                . '/';
            return preg_match($allowed_path_pattern, $path) === 1;
        }

        // Check rules related to non-PHP files.
        $allowed_path_pattern = [
            // files in `/public` directories
            '^(\/(marketplace|plugins)\/[^\/]+)?\/public\/',
            // static pages
            '\.html?$',
            // JS/CSS files
            '\.(js|css)$',
            // JS/CSS files sourcemaps used in dev env (it is to the publisher responsibility to remove them in dist packages)
            '\.(js|css)\.map$',
            // Vue components
            '\.vue$',
            // images
            '\.(gif|jpe?g|png|svg)$',
            // audios
            '\.(mp3|ogg|wav)$',
            // videos
            '\.(mp4|ogm|ogv|webm)$',
            // webfonts
            '\.(eot|otf|ttf|woff2?)$',
            // JSON files in plugins (except `composer.json`, `package.json` and `package-lock.json` located on root)
            '^\/(marketplace|plugins)\/[^\/]+\/(?!composer\.json|package\.json|package-lock\.json).+\.json$',
            // favicon
            '(^|\/)favicon\.ico$',
        ];

        return preg_match('/(' . implode('|', $allowed_path_pattern) . ')/i', $path) === 1;
    }

    protected function extractPathAndPrefix(Request $request): array
    {
        $script_name = $request->server->get('SCRIPT_NAME');
        $request_uri = $request->server->get('REQUEST_URI');

        if (
            $script_name === '/public/index.php'
            && preg_match('/^\/public/', $request_uri) !== 1
        ) {
            // When requested URI does not start with '/public' but `$request->server->get('SCRIPT_NAME')` is '/public/index.php',
            // it means that document root is the GLPI root directory, but a rewrite rule redirects the request to the PHP router.
            // This case happen when redirection to PHP router is made by an `.htaccess` file placed in the GLPI root directory,
            // and has to be handled to support shared hosting where it is not possible to change the web server root directory.
            $uri_prefix = '';
        } else {
            // `$request->server->get('SCRIPT_NAME')` corresponds to the script path relative to server document root.
            // -> if server document root is `/public`, then `$request->server->get('SCRIPT_NAME')` will be equal to `/index.php`
            // -> if script is located into a `/glpi-alias` alias directory, then `$request->server->get('SCRIPT_NAME')` will be equal to `/glpi-alias/index.php`
            $uri_prefix = rtrim(str_replace('\\', '/', dirname($script_name)), '/');
        }

        // Get URI path relative to GLPI (i.e. without alias directory prefix).
        $request_uri = preg_replace('/\/{2,}/', '/', $request_uri); // remove duplicates `/`
        $path = preg_replace(
            '/^' . preg_quote($uri_prefix, '/') . '/',
            '',
            parse_url($request_uri, PHP_URL_PATH)
        );

        return [$uri_prefix, $path];
    }
}
