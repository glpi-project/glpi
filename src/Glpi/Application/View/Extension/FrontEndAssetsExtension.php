<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Application\View\Extension;

use Config;
use DBmysql;
use Entity;
use Glpi\Application\ImportMapGenerator;
use Glpi\Toolbox\FrontEnd;
use Glpi\UI\Theme;
use Glpi\UI\ThemeManager;
use Html;
use Plugin;
use Session;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function Safe\json_encode;
use function Safe\parse_url;
use function Safe\preg_match;

/**
 * @since 10.0.0
 */
class FrontEndAssetsExtension extends AbstractExtension
{
    /**
     * GLPI root dir.
     */
    private string $root_dir;

    public function __construct(string $root_dir = GLPI_ROOT)
    {
        $this->root_dir = $root_dir;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_path', [$this, 'assetPath']),
            new TwigFunction('css_path', [$this, 'cssPath']),
            new TwigFunction('js_path', [$this, 'jsPath']),
            new TwigFunction('custom_css', [$this, 'customCss'], ['is_safe' => ['html']]),
            new TwigFunction('config_js', [$this, 'configJs'], ['is_safe' => ['html']]),
            new TwigFunction('locales_js', [$this, 'localesJs'], ['is_safe' => ['html']]),
            new TwigFunction('current_theme', [$this, 'currentTheme']),
            new TwigFunction('get_custom_themes_paths', [$this, 'getCustomThemesPaths']),
            new TwigFunction('importmap', [$this, 'importmap'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Current theme
     *
     * @return Theme
     */
    public function currentTheme(): Theme
    {
        return ThemeManager::getInstance()->getCurrentTheme();
    }

    /**
     * Get all the custom themes CSS files paths.
     *
     * @return string[]
     */
    public function getCustomThemesPaths(): array
    {
        return ThemeManager::getInstance()->getCustomThemesPaths();
    }

    /**
     * Return domain-relative path of an asset.
     *
     * @param string $path
     *
     * @return string
     */
    public function assetPath(string $path): string
    {
        return Html::getPrefixedUrl($path);
    }

    /**
     * Return domain-relative path of a CSS file.
     *
     * @param string $path
     * @param array $options
     *
     * @return string
     */
    public function cssPath(string $path, array $options = []): string
    {
        $is_debug = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] === Session::DEBUG_MODE;

        $clean_path = parse_url($path, PHP_URL_PATH); // Strip potential quey string from path

        $extra_params = parse_url($path, PHP_URL_QUERY) ?: '';

        if (
            preg_match('/\.scss$/', $clean_path)
            || (
                str_contains($extra_params, 'is_custom_theme=1')
                && ThemeManager::getInstance()->getTheme($clean_path)
            )
        ) {
            // FIXME Compiled SCSS is not supported for plugins, should we support it?
            $compiled_file = Html::getScssCompilePath($clean_path, $this->root_dir);

            if (!$is_debug && file_exists($compiled_file)) {
                $path = str_replace($this->root_dir . '/public', '', $compiled_file);
            } else {
                $path = '/front/css.php?file=' . $clean_path;
                if ($is_debug) {
                    $extra_params .= ($extra_params !== '' ? '&' : '') . 'debug=1';
                }
            }
        } else {
            if (!str_starts_with($clean_path, '/')) {
                $clean_path = '/' . $clean_path; // be sure to have a path starting with `/`

                // FIXME Trigger a warning in GLPI 12.0.
            }

            if (!$is_debug) {
                $path_matches = [];
                if (preg_match(Plugin::PLUGIN_RESOURCE_PATTERN, $clean_path, $path_matches) === 1) {
                    $fs_path  = Plugin::getPhpDir($path_matches['plugin_key']) . '/public' . $path_matches['plugin_resource'];
                } else {
                    $fs_path  = $this->root_dir . '/public' . $clean_path;
                }

                if (file_exists(str_replace('.css', '.min.css', $fs_path))) {
                    $clean_path = str_replace('.css', '.min.css', $clean_path);
                }
            }

            $path = $clean_path;
        }

        if ($extra_params !== '') {
            // Append query string from initial path, if any
            $path .= (str_contains($path, '?') ? '&' : '?') . $extra_params;
        }

        $path = Html::getPrefixedUrl($path);
        $path = $this->getVersionnedPath($path, $options);

        return $path;
    }

    /**
     * Return domain-relative path of a JS file.
     *
     * @param string $path
     * @param array $options
     *
     * @return string
     */
    public function jsPath(string $path, array $options = []): string
    {
        $is_debug = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] === Session::DEBUG_MODE;

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path; // be sure to have a path starting with `/`

            // FIXME Trigger a warning in GLPI 12.0.
        }

        if (!$is_debug) {
            $path_matches = [];
            if (preg_match(Plugin::PLUGIN_RESOURCE_PATTERN, $path, $path_matches) === 1) {
                $fs_path  = Plugin::getPhpDir($path_matches['plugin_key']) . '/public' . $path_matches['plugin_resource'];
            } else {
                $fs_path  = $this->root_dir . '/public' . $path;
            }

            if (file_exists(str_replace('.js', '.min.js', $fs_path))) {
                $path = str_replace('.js', '.min.js', $path);
            }
        }

        $path = Html::getPrefixedUrl($path);
        $path = $this->getVersionnedPath($path, $options);

        return $path;
    }

    /**
     * Get path suffixed with asset version.
     *
     * @param string $path
     *
     * @return string
     */
    private function getVersionnedPath(string $path, array $options = []): string
    {
        $version = $options['version'] ?? GLPI_VERSION;
        $path .= (str_contains($path, '?') ? '&' : '?') . 'v=' . FrontEnd::getVersionCacheKey($version);

        return $path;
    }

    /**
     * Return custom CSS for active entity.
     *
     * @return string
     */
    public function customCss(): string
    {
        /** @var DBmysql|null $DB */
        global $DB;

        $css = '';

        if (\DBConnection::isDbAvailable() && $DB->tableExists(Entity::getTable())) {
            $entity = new Entity();
            if (isset($_SESSION['glpiactive_entity'])) {
                // Apply active entity styles
                $entity->getFromDB($_SESSION['glpiactive_entity']);
            } else {
                // Apply root entity styles
                $entity->getFromDB('0');
            }
            $css = $entity->getCustomCssTag();
        }

        return $css;
    }

    /**
     * Return locales JS code.
     *
     * @return string
     */
    public function localesJs(): string
    {
        global $CFG_GLPI;

        if (!isset($_SESSION['glpilanguage'])) {
            return '';
        }

        // Compute available translation domains
        $locales_domains = ['glpi' => GLPI_VERSION];
        $plugins = Plugin::getPlugins();
        foreach ($plugins as $plugin) {
            $locales_domains[$plugin] = Plugin::getPluginFilesVersion($plugin);
        }

        $script = "
            $(function() {
                i18n.setLocale('" . \jsescape($_SESSION['glpilanguage']) . "');
            });

            $.fn.select2.defaults.set(
                'language',
                '" . \jsescape($CFG_GLPI['languages'][$_SESSION['glpilanguage']][2]) . "',
            );
        ";

        foreach ($locales_domains as $locale_domain => $locale_version) {
            $locales_path = Html::getPrefixedUrl(
                '/front/locale.php'
                . '?domain=' . $locale_domain
                . '&lang=' . $_SESSION['glpilanguage']
                . '&v=' . FrontEnd::getVersionCacheKey($locale_version)
            );
            $script .= "
                $(function() {
                    $.ajax({
                        type: 'GET',
                        url: '" . \jsescape($locales_path) . "',
                        success: function(json) {
                            i18n.loadJSON(json, '" . \jsescape($locale_domain) . "');
                        }
                    });
                });
            ";
        }

        return Html::scriptBlock($script);
    }

    /**
     * Return config (CFG_GLPI) JS code.
     *
     * @return string
     */
    public function configJs(): string
    {
        global $CFG_GLPI;

        $cfg_glpi = [
            'url_base' => $CFG_GLPI['url_base'] ?? '', // may not be defined during the install process
            'root_doc' => $CFG_GLPI['root_doc'],
        ];
        if (Session::getLoginUserID(true) !== false) {
            // expose full config only for connected users
            $cfg_glpi += Config::getSafeConfig(true);
        }

        $plugins_path = \array_combine(
            Plugin::getPlugins(),
            \array_map(fn(string $plugin_key) => "/plugins/{$plugin_key}", Plugin::getPlugins())
        );

        $script = sprintf('window.CFG_GLPI = %s;', json_encode($cfg_glpi, JSON_PRETTY_PRINT))
            . "\n"
            . sprintf('window.GLPI_PLUGINS_PATH = %s;', json_encode($plugins_path, JSON_PRETTY_PRINT));

        return Html::scriptBlock($script);
    }

    /**
     * Generate an import map for JavaScript modules
     *
     * @return string HTML script tag containing the import map
     */
    public function importmap(): string
    {
        $import_map = ImportMapGenerator::getInstance()->generate();

        return '<script type="importmap">' . json_encode(
            $import_map,
            JSON_PRETTY_PRINT
        ) . '</script>';
    }
}
