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

use Glpi\Application\View\Extension\FrontEndAssetsExtension;
use Twig\Environment;
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

   public function __construct(string $rootdir = GLPI_ROOT) {
      $paths = [
         $rootdir . '/templates',
      ];

      $options = [
      ];

      $this->environment = new Environment(
         new FilesystemLoader($paths, $rootdir),
         $options
      );
      $this->environment->addExtension(new FrontEndAssetsExtension());
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
    * @return string
    */
   public function display(string $template, array $variables = []): void {
      $this->environment->load($template)->display($variables);
   }
}
