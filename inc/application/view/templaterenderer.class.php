<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace Glpi\Application\View;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Application\View\Extension\CsrfExtension;
use Glpi\Application\View\Extension\ConfigExtension;
use Glpi\Application\View\Extension\FrontEndAssetsExtension;
use Glpi\Application\View\Extension\I18nExtension;
use Glpi\Application\View\Extension\ItemtypeExtension;
use Glpi\Application\View\Extension\NumberFormatExtension;
use Glpi\Application\View\Extension\RoutingExtension;
use Glpi\Application\View\Extension\SearchExtension;
use Glpi\Application\View\Extension\SessionExtension;
use Glpi\Application\View\Extension\ToolboxExtension;
use Session;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

/**
 * @since x.x.x
 *
 * @TODO Create a custom loader that will load template for plugin (e.g. when name matches "plugin_name:/path/to/template").
 * @TODO Create a custom loader that will automatically append ".twig" to template names.
 */
class TemplateRenderer {

   /**
    * @var Environment
    */
   private $environment;

   public function __construct(string $rootdir = GLPI_ROOT, string $cachedir = GLPI_CACHE_DIR) {
      $paths = [
         $rootdir . '/templates',
      ];

      $options = [
         'cache'       => $cachedir . '/templates',
         'debug'       => $_SESSION['glpi_use_mode'] ?? null === Session::DEBUG_MODE,
         'auto_reload' => true, // Force refresh
      ];

      $this->environment = new Environment(
         new FilesystemLoader($paths, $rootdir),
         $options
      );
      // Vendor extensions
      $this->environment->addExtension(new DebugExtension());
      $this->environment->addExtension(new StringExtension());
      // GLPI extensions
      $this->environment->addExtension(new ConfigExtension());
      $this->environment->addExtension(new CsrfExtension());
      $this->environment->addExtension(new FrontEndAssetsExtension());
      $this->environment->addExtension(new I18nExtension());
      $this->environment->addExtension(new ItemtypeExtension());
      $this->environment->addExtension(new NumberFormatExtension());
      $this->environment->addExtension(new RoutingExtension());
      $this->environment->addExtension(new SearchExtension());
      $this->environment->addExtension(new SessionExtension());
      $this->environment->addExtension(new ToolboxExtension());
   }

   /**
    * Return singleton instance of self.
    *
    * @return TemplateRenderer
    */
   public static function getInstance(): TemplateRenderer {
      static $instance = null;

      if ($instance === null) {
         $instance = new static();
      }

      return $instance;
   }

   /**
    * Renders a template.
    *
    * @param string $template
    * @param array  $variables
    *
    * @return string
    */
   public function render(string $template, array $variables = []): string {
      return $this->environment->load($template)->render($variables);
   }

   /**
    * Displays a template.
    *
    * @param string $template
    * @param array  $variables
    *
    * @return void
    */
   public function display(string $template, array $variables = []): void {
      $this->environment->load($template)->display($variables);
   }
}
