<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/**
 * @since 10.0.7
 */
final class ProxyRouter
{
    /**
     * GLPI root directory.
     * @var string
     */
    private string $root_dir;

    /**
     * Target path.
     * @var string
     */
    private string $path;

    /**
     * PathInfo (extra information added next to path).
     * @var string
     */
    private ?string $pathinfo;

    public function __construct(string $root_dir, string $path)
    {
        $this->root_dir = $root_dir;

        $path = preg_replace('/\/{2,}/', '/', $path); // remove duplicates `/`

        $path_matches = [];
        if (
            preg_match('/^(?<path>.+\.[^\/]+)(?<pathinfo>\/.*)$/', $path, $path_matches) === 1
            && is_file($this->root_dir . $path_matches['path'])
        ) {
            // Separate path and pathinfo.
            $path     = $path_matches['path'];
            $pathinfo = $path_matches['pathinfo'];
        } else {
            // Clean trailing `/`.
            $path = rtrim($path, '/');

            // If URI matches a directory path, consider `index.php` is the requested script.
            if (is_dir($this->root_dir . $path) && is_file($this->root_dir . $path . '/index.php')) {
                $path .= '/index.php';
            }

            $pathinfo = null;
        }

        $this->path     = $path;
        $this->pathinfo = $pathinfo;
    }

    /**
     * Return target path.
     *
     * @return string
     */
    public function getTargetPath(): string
    {
        return $this->path;
    }

    /**
     * Return target PathInfo.
     *
     * @return string|null
     */
    public function getTargetPathInfo(): ?string
    {
        return $this->pathinfo;
    }

    /**
     * Return target file.
     *
     * @return string|null
     */
    public function getTargetFile(): ?string
    {
        $target_file = $this->root_dir . $this->path;
        return is_file($target_file) ? $target_file : null;
    }

    /**
     * Determine whether the requested path is allowed.
     *
     * @param string $file_relative_path
     * @return bool
     */
    public function isPathAllowed(): bool
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
        if (preg_match('/(' . implode('|', $excluded_path_patterns) . ')/i', $this->path) === 1) {
            return false;
        }

        // Check rules related to PHP files.
        // Check extension on path even if file not exists, to be able to send a 403 error even if file not exists.
        if ($this->isTargetAPhpScript()) {
            $glpi_path_patterns = [
                // `/ajax/` scripts
                'ajax\/',
                // `/front/` scripts
                'front\/',
                // install/update scripts
                'install\/(install|update)\.php$',
                // endpoints located on root directory
                '(apirest|apixmlrpc|caldav|index|status)\.php',
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
            return preg_match($allowed_path_pattern, $this->path) === 1;
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
        if (preg_match('/(' . implode('|', $allowed_path_pattern) . ')/i', $this->path) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Check whether target is a PHP script.
     *
     * @return bool
     */
    public function isTargetAPhpScript(): bool
    {
        // Check extension on path directly to be able to recognize that target is supposed to be a PHP
        // script even if it not exists. This is usefull to send most appropriate response code (i.e. 403 VS 404).
        if (preg_match('/^php\d*$/', pathinfo($this->path, PATHINFO_EXTENSION)) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Serve the requested file.
     *
     * @return void
     */
    public function proxify(): void
    {
        if ($this->isPathAllowed() === false) {
            http_response_code(403);
            return;
        }

        $target_file = $this->getTargetFile();

        if ($target_file === null) {
            http_response_code(404);
            return;
        }

        if ($this->isTargetAPhpScript()) {
            // PHP specific case.
            // Requested file has to be included in global scope so is done in `/public/index.php` script.
            // Anyway, this check has to be kept to ensure that PHP scripts are not served as static files.
            http_response_code(500); // 500 error as this is a bug if such case happen
            return;
        }

        $extension = pathinfo($target_file, PATHINFO_EXTENSION);

        // Serve static files if web server is not configured to do it directly.
        $etag = md5_file($target_file);
        $last_modified = filemtime($target_file);
        $mime = null;
        switch ($extension) {
            case 'css':
                $mime = 'text/css';
                break;
            case 'js':
                $mime = 'application/javascript';
                break;
            case 'woff':
                $mime = 'font/woff';
                break;
            case 'woff2':
                $mime = 'font/woff2';
                break;
            default:
                $mime = mime_content_type($target_file);

                if ($mime === false) {
                    $mime = 'application/octet-stream';
                }
                break;
        }

        // HTTP_IF_NONE_MATCH takes precedence over HTTP_IF_MODIFIED_SINCE.
        // see http://tools.ietf.org/html/rfc7232#section-3.3
        $is_not_modified = trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag
            || @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '') >= $last_modified;

        http_response_code($is_not_modified ? 304 : 200);
        header(sprintf('Last-Modified: %s GMT', gmdate('D, d M Y H:i:s', $last_modified)));
        header(sprintf('Etag: %s', $etag));
        header_remove('Pragma');
        header('Cache-Control: public, max-age=2592000, must-revalidate'); // 30 days cache
        header(sprintf('Content-type: %s', $mime));

        if ($is_not_modified) {
            // Do not send contents if not modified.
            return;
        }

        header(sprintf('Content-Length: %s', filesize($target_file)));
        readfile($target_file);
    }
}
