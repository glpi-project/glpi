<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Application\View\Extension;

use DBmysql;
use Entity;
use Html;
use Plugin;
use Session;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since x.x.x
 */
class FrontEndAssetsExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('asset_path', [$this, 'assetPath']),
         new TwigFunction('css_path', [$this, 'cssPath']),
         new TwigFunction('js_path', [$this, 'jsPath']),
         new TwigFunction('custom_css', [$this, 'customCss'], ['is_safe' => ['html']]),
         new TwigFunction('locales_js', [$this, 'localesJs'], ['is_safe' => ['html']]),
      ];
   }

   /**
    * Return domain-relative path of an asset.
    *
    * @param string $path
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function assetPath(string $path): string {
      return Html::getPrefixedUrl($path);
   }

   /**
    * Return domain-relative path of a CSS file.
    *
    * @param string $path
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function cssPath(string $path): string {
      $is_debug = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] === Session::DEBUG_MODE;

      if (preg_match('/\.scss$/', $path)) {
         $compiled_file = Html::getScssCompilePath($path);

         if (!$is_debug && file_exists($compiled_file)) {
            $path = str_replace(GLPI_ROOT, '', $compiled_file);
         } else {
            $path = '/front/css.php?file=' . $path . ($is_debug ? '&debug=1' : '');
         }
      } else {
         $minified_path = str_replace('.css', '.min.css', $path);

         if (!$is_debug && file_exists(GLPI_ROOT . '/' . $minified_path)) {
            $path = $minified_path;
         }
      }

      $path = Html::getPrefixedUrl($path);
      $path = $this->getVersionnedPath($path);

      return $path;
   }

   /**
    * Return domain-relative path of a JS file.
    *
    * @param string $path
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function jsPath(string $path): string {
      $is_debug = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] === Session::DEBUG_MODE;

      $minified_path = str_replace('.js', '.min.js', $path);

      if (!$is_debug && file_exists(GLPI_ROOT . '/' . $minified_path)) {
         $path = $minified_path;
      }

      $path = Html::getPrefixedUrl($path);
      $path = $this->getVersionnedPath($path);

      return $path;
   }

   /**
    * Get path suffixed with asset version.
    *
    * @param string $path
    *
    * @return string
    */
   private function getVersionnedPath(string $path): string {
      // @TODO Adapt version to plugin version if path is related to a specific plugin
      $version = GLPI_VERSION;
      $path .= (strpos($path, '?') !== false ? '&' : '?') . 'v=' . $version;

      return $path;
   }

   /**
    * Return custom CSS for active entity.
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function customCss(): string {
      global $DB;

      $css = '';

      if ($DB instanceof DBmysql && $DB->connected) {
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
    *
    * @TODO Add a unit test.
    */
   public function localesJs(): string {
      if (!isset($_SESSION['glpilanguage'])) {
         return '';
      }

      // Compute available translation domains
      $locales_domains = ['glpi' => GLPI_VERSION];
      $plugins = Plugin::getPlugins();
      foreach ($plugins as $plugin) {
         $locales_domains[$plugin] = Plugin::getInfo($plugin, 'version');
      }

      $script = <<<JAVASCRIPT
         $(function() {
            i18n.setLocale('{$_SESSION['glpilanguage']}');
         });
JAVASCRIPT;

      foreach ($locales_domains as $locale_domain => $locale_version) {
         $locales_path = Html::getPrefixedUrl(
            '/front/locale.php'
            . '?domain=' . $locale_domain
            . '&version=' . $locale_version
            . ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? '&debug' : '')
         );
         $script .= <<<JAVASCRIPT
            $(function() {
               $.ajax({
                  type: 'GET',
                  url: '{$locales_path}',
                  success: function(json) {
                     i18n.loadJSON(json, '{$locale_domain}');
                  }
               });
            });
JAVASCRIPT;
      }

      return Html::scriptBlock($script);
   }
}
