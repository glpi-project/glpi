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

namespace Glpi\Application\View;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Application\ErrorHandler;
use Glpi\Application\View\Extension\AjaxExtension;
use Glpi\Application\View\Extension\AlertExtension;
use Glpi\Application\View\Extension\CommonITILObjectExtension;
use Glpi\Application\View\Extension\ConfigExtension;
use Glpi\Application\View\Extension\CsrfExtension;
use Glpi\Application\View\Extension\DBExtension;
use Glpi\Application\View\Extension\DocumentExtension;
use Glpi\Application\View\Extension\DropdownExtension;
use Glpi\Application\View\Extension\EntityExtension;
use Glpi\Application\View\Extension\EventExtension;
use Glpi\Application\View\Extension\FrontEndAssetsExtension;
use Glpi\Application\View\Extension\HtmlExtension;
use Glpi\Application\View\Extension\I18nExtension;
use Glpi\Application\View\Extension\InfocomExtension;
use Glpi\Application\View\Extension\ItemtypeExtension;
use Glpi\Application\View\Extension\ModelExtension;
use Glpi\Application\View\Extension\NumberFormatExtension;
use Glpi\Application\View\Extension\PluginExtension;
use Glpi\Application\View\Extension\RichTextExtension;
use Glpi\Application\View\Extension\RoutingExtension;
use Glpi\Application\View\Extension\SearchExtension;
use Glpi\Application\View\Extension\SessionExtension;
use Glpi\Application\View\Extension\ToolboxExtension;
use Glpi\Application\View\Extension\UserExtension;
use Glpi\Application\View\Extension\ValidationExtension;
use Plugin;
use Session;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Extension\DebugExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

/**
 * @since 10.0.0
 */
class TemplateRenderer {

   /**
    * @var Environment
    */
   private $environment;

   public function __construct(string $rootdir = GLPI_ROOT, string $cachedir = GLPI_CACHE_DIR) {
      $loader = new FilesystemLoader($rootdir . '/templates', $rootdir);

      $active_plugins = Plugin::getPlugins();
      foreach ($active_plugins as $plugin_key) {
         // Add a dedicated namespace for each active plugin, so templates would be loadable using
         // `@my_plugin/path/to/template.html.twig` where `my_plugin` is the plugin key and `path/to/template.html.twig`
         // is the path of the template inside the `/templates` directory of the plugin.
         $loader->addPath(Plugin::getPhpDir($plugin_key . '/templates'), $plugin_key);
      }

      $this->environment = new Environment(
         $loader,
         [
            'cache'       => $cachedir . '/templates',
            'debug'       => $_SESSION['glpi_use_mode'] ?? null === Session::DEBUG_MODE,
            'auto_reload' => true, // Force refresh
         ]
      );
      // Vendor extensions
      $this->environment->addExtension(new DebugExtension());
      $this->environment->addExtension(new StringExtension());
      // GLPI extensions
      $this->environment->addExtension(new AjaxExtension());
      $this->environment->addExtension(new AlertExtension());
      $this->environment->addExtension(new CommonITILObjectExtension());
      $this->environment->addExtension(new ConfigExtension());
      $this->environment->addExtension(new CsrfExtension());
      $this->environment->addExtension(new DBExtension());
      $this->environment->addExtension(new DocumentExtension());
      $this->environment->addExtension(new DropdownExtension());
      $this->environment->addExtension(new EntityExtension());
      $this->environment->addExtension(new EventExtension());
      $this->environment->addExtension(new FrontEndAssetsExtension());
      $this->environment->addExtension(new I18nExtension());
      $this->environment->addExtension(new InfocomExtension());
      $this->environment->addExtension(new ItemtypeExtension());
      $this->environment->addExtension(new HtmlExtension());
      $this->environment->addExtension(new ModelExtension());
      $this->environment->addExtension(new NumberFormatExtension());
      $this->environment->addExtension(new PluginExtension());
      $this->environment->addExtension(new RichTextExtension());
      $this->environment->addExtension(new RoutingExtension());
      $this->environment->addExtension(new SearchExtension());
      $this->environment->addExtension(new SessionExtension());
      $this->environment->addExtension(new ToolboxExtension());
      $this->environment->addExtension(new UserExtension());
      $this->environment->addExtension(new ValidationExtension());

      // add superglobals
      $this->environment->addGlobal('_post', $_POST);
      $this->environment->addGlobal('_get', $_GET);
      $this->environment->addGlobal('_request', $_REQUEST);
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
      try {
         return $this->environment->load($template)->render($variables);
      } catch (\Twig\Error\Error $e) {
         $this->handleError($e, $template);
      }
      return '';
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
      try {
         $this->environment->load($template)->display($variables);
      } catch (\Twig\Error\Error $e) {
         $this->handleError($e, $template);
      }
   }

   /**
    * Log Twig error using GLPI error handler.
    *
    * @param \Twig\Error\Error $error
    *
    * @param string $template
    */
   private function handleError(Error $error, string $template): void {
      global $GLPI;

      $error_handler = $GLPI->getErrorHandler();
      if ($error_handler instanceof ErrorHandler) {
         $error_handler->handleTwigError($error, $template);
      }
   }
}
