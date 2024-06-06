<?php

namespace Glpi\Http;

trait LegacyRouterTrait
{
    public function isTargetAPhpScript(string $path): bool
    {
        // Check extension on path directly to be able to recognize that target is supposed to be a PHP
        // script even if it not exists. This is usefull to send most appropriate response code (i.e. 403 VS 404).
        return preg_match('/^php\d*$/', pathinfo($path, PATHINFO_EXTENSION)) === 1;
    }

    public function isPathAllowed(string $path): bool
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
                // endpoints located on root directory
                '(api|apirest|caldav|index|status)\.php',
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
}
