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

namespace Glpi\Application\View\Extension;

use Search;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since x.x.x
 */
class SearchExtension extends AbstractExtension implements ExtensionInterface {
   public function getFunctions() {
      return [
         new TwigFunction('showItem', [$this, 'showItem']),
         new TwigFunction('displayConfigItem', [$this, 'displayConfigItem']),
         new TwigFunction('computeTitle', [$this, 'computeTitle']),
      ];
   }

   public function showItem(
      int $displaytype,
      string $value = "",
      int $num = 0,
      int $row = 0,
      string $extraparams = ""
   ): string {
      return Search::showItem($displaytype, $value, $num, $row, $extraparams);
   }

   /**
    * Return string with extra attributes for a td col.
    *
    * @param string $itemtype
    * @param int $id
    * @param array array
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
    public function displayConfigItem(string $itemtype, int $id, array $data = []): string {
      return Search::displayConfigItem($itemtype, $id, $data);
   }


   public function computeTitle(array $data = []):string {
      return Search::computeTitle($data);
   }
}
