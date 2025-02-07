<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Toolbox;

trait LegacyRouterTrait
{
    /**
     * GLPI root directory.
     */
    protected string $glpi_root;

    /**
     * GLPI plugins directories.
     * @var string[]
     */
    protected array $plugin_directories;

    protected function isTargetAPhpScript(string $path): bool
    {
        // Check extension on path directly to be able to recognize that target is supposed to be a PHP
        // script even if it not exists. This is usefull to send most appropriate response code (i.e. 403 VS 404).
        return preg_match('/^php\d*$/i', pathinfo($path, PATHINFO_EXTENSION)) === 1;
    }

    protected function isHiddenFile(string $path): bool
    {
        return preg_match('#/\.#i', $path) === 1;
    }

    protected function getTargetFile(string $path): ?string
    {
        $path_matches = [];
        if (preg_match('#^/plugins/(?<plugin_key>[^\/]+)(?<plugin_resource>/.+)$#', $path, $path_matches) === 1) {
            $plugin_dir = null;
            foreach ($this->plugin_directories as $plugins_directory) {
                $to_check = $plugins_directory . DIRECTORY_SEPARATOR . $path_matches['plugin_key'];
                if (is_dir($to_check)) {
                    $plugin_dir = $to_check;
                    break;
                }
            }
            if ($plugin_dir === null) {
                // The requested plugin does not exist, the target file does not exists.
                return null;
            }

            $relative_path = $path_matches['plugin_resource'];
            if (
                $this->isTargetAPhpScript($path)
                && \preg_match('#^/(ajax|front|report)/#', $relative_path)
                && \is_file($plugin_dir . $relative_path)
            ) {
                // legacy scripts located in the `/ajax`, `/front` or `/report` directory
                $filename = $plugin_dir . $relative_path;
            } else {
                // preprend `/public` to expose the public resources only
                $filename = $plugin_dir . '/public' . $relative_path;
            }
        } elseif (
            (
                // legacy scripts located in the `/ajax`, `/front` or `/install` directory
                (\preg_match('#^(/ajax/|/front/|/install/(install|update)\.php)#', $path) && $this->isTargetAPhpScript($path))
                // JS scripts located in the `/js` directory
                || preg_match('#^/js/.+\.js$#', $path)
            )
            && is_file($this->glpi_root . $path)
        ) {
            $filename = $this->glpi_root . $path;
        } else {
            // preprend `/public` to expose the public resources only
            $filename = $this->glpi_root . '/public' . $path;
        }

        return \is_file($filename) ? $filename : null;
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

        // Normalize plugins paths.
        // All plugins resources should now be accessed using the `/plugins/${plugin_key}/${resource_path}`.
        if (str_starts_with($path, '/marketplace/')) {
            // /!\ `/marketplace/` URLs were massively used prior to GLPI 11.0.
            //
            // To not break URLs than can be found in the wild (in e-mail, forums, external apps configuration, ...),
            // please do not remove this behaviour before, at least, 2030 (about 5 years after GLPI 11.0.0 release).
            Toolbox::deprecated('Accessing the plugins resources from the `/marketplace/` path is deprecated. Use the `/plugins/` path instead.');
            $path = preg_replace(
                '#^/marketplace/#',
                '/plugins/',
                parse_url($request_uri, PHP_URL_PATH)
            );
        }

        // Parse URI to find requested script and PathInfo
        $init_path = $path;
        $path = '';

        $slash_pos = 0;
        while ($slash_pos !== false && ($dot_pos = strpos($init_path, '.', $slash_pos)) !== false) {
            $slash_pos = strpos($init_path, '/', $dot_pos);
            $filepath = substr($init_path, 0, $slash_pos !== false ? $slash_pos : strlen($init_path));
            if ($this->getTargetFile($filepath) !== null) {
                $path = $filepath;
                break;
            }

            // All plugins public resources should now be accessed without explicitely using the `/public` path,
            // e.g. `/plugins/myplugin/public/css.php` -> `/plugins/myplugin/css.php`.
            $path_matches = [];
            if (preg_match('#^/plugins/(?<plugin_key>[^\/]+)/public(?<plugin_resource>/.+)$#', $filepath, $path_matches) === 1) {
                $new_path = sprintf('/plugins/%s%s', $path_matches['plugin_key'], $path_matches['plugin_resource']);
                if ($this->getTargetFile($new_path) !== null) {
                    // To not break URLs than can be found in the wild (in e-mail, forums, external apps configuration, ...),
                    // please do not remove this behaviour before, at least, 2030 (about 5 years after GLPI 11.0.0 release).
                    Toolbox::deprecated('Plugins URLs containing the `/public` path are deprecated. You should remove the `/public` prefix from the URL.');
                    $path = $new_path;
                    break;
                }
            }
        }

        if ($path === '') {
            // Fallback to requested URI
            $path = $init_path;

            // Clean trailing `/`.
            $path = rtrim($path, '/');

            // If URI matches a directory path, consider `index.php` is the requested script.
            if ($this->getTargetFile($path . '/index.php') !== null) {
                $path .= '/index.php';
            }
        }

        return [$uri_prefix, $path];
    }
}
