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

use Document;
use DocumentType;
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
         new TwigFunction('getMaxUploadSize', [Document::class, 'getMaxUploadSize'], ['is_safe' => ['html']]),
         new TwigFunction('showAvailableTypesLink', [DocumentType::class, 'showAvailableTypesLink'], ['is_safe' => ['html']]),
         new TwigFunction('isImage', [$this, 'isImage'], ['is_safe' => ['html']]),
         new TwigFunction('getImage', [$this, 'getImage'], ['is_safe' => ['html']]),
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

   public function isImage(string $filepath, bool $is_relative = true): bool {
      if ($is_relative) {
         $filepath = GLPI_VAR_DIR."/".$filepath;
      }
      return Document::isImage($filepath);
   }

   public function getImage(string $filepath, string $context, bool $is_relative = true, $mwidth = null, $mheight = null): string {
      if ($is_relative) {
         $filepath = GLPI_VAR_DIR."/".$filepath;
      }
      return Document::getImage($filepath, $context, $mwidth, $mheight);
   }
}
