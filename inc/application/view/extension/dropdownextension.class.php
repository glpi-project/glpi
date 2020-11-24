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

use Dropdown;
use TicketValidation;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class DropdownExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('getDropdownName', [Dropdown::class, 'getDropdownName']),
         new TwigFunction('Dropdown__showGlobalSwitch', [Dropdown::class, 'showGlobalSwitch']),
         new TwigFunction('Dropdown__showNumber', [Dropdown::class, 'showNumber'], ['is_safe' => ['html']]),
         new TwigFunction('Dropdown__showFromArray', [Dropdown::class, 'showFromArray'], ['is_safe' => ['html']]),
         new TwigFunction('Dropdown__showYesNo', [Dropdown::class, 'showYesNo'], ['is_safe' => ['html']]),
         new TwigFunction('Dropdown__showTimestamp', [Dropdown::class, 'showTimestamp'], ['is_safe' => ['html']]),
         new TwigFunction('Dropdown__showItemTypes', [Dropdown::class, 'showItemTypes'], ['is_safe' => ['html']]),
         new TwigFunction('Dropdown__showHours', [$this, 'showHours'], ['is_safe' => ['html']]),
         new TwigFunction('Dropdown__dropdownIcons', [Dropdown::class, 'dropdownIcons'], ['is_safe' => ['html']]),
         new TwigFunction('TicketValidation__dropdownStatus', [TicketValidation::class, 'dropdownStatus'], ['is_safe' => ['html']]),
         new TwigFunction('TicketValidation__dropdownValidator', [TicketValidation::class, 'dropdownValidator'], ['is_safe' => ['html']]),
         new TwigFunction('SoftwareVersion__dropdownForOneSoftware', [$this, 'dropdownForOneSoftware'], ['is_safe' => ['html']]),
      ];
   }

   public function showHours($name, $options = []) {
      // Suppress returned ID value
      Dropdown::showHours($name, $options);
   }

   public function dropdownForOneSoftware($options = []) {
      // Suppress returned ID value
      \SoftwareVersion::dropdownForOneSoftware($options);
   }
}
