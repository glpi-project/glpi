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

use Toolbox;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class DocumentExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('getIconForFilename', [$this, 'getIconForFilename']),
         new TwigFunction('getSizeForFilePath', [$this, 'getSizeForFilePath']),
      ];
   }

   public function getIconForFilename(string $filename = ""): string {
      global $CFG_GLPI;

      $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      if (file_exists(GLPI_ROOT."/pics/icones/$extension-dist.png")) {
         return  $CFG_GLPI['root_doc']."/pics/icones/$extension-dist.png";
      }

      return $CFG_GLPI['root_doc']."/pics/timeline/file.png";
   }


   public function getSizeForFilePath(string $filepath = "", bool $is_relative = true): string {
      if ($is_relative) {
         $filepath = GLPI_VAR_DIR."/".$filepath;
      }

      if (file_exists($filepath)) {
         $filesize = filesize($filepath);
         return Toolbox::getSize($filesize);
      }

      return "";
   }
}
