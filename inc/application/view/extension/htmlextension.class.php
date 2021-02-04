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

use Html;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @since x.x.x
 */
class HtmlExtension extends AbstractExtension implements ExtensionInterface {
   public function getFilters() {
      return [
         new TwigFilter('conv_datetime', [$this, 'convDateTime'], ['is_safe' => ['html']]),
      ];
   }

   public function getFunctions() {
      return [
         new TwigFunction('showMassiveActions', [$this, 'showMassiveActions']),
         new TwigFunction('isMassiveActionchecked', [$this, 'isMassiveActionchecked']),
      ];
   }

   public function convDateTime(string $datetime, string $format = null): string {
      return Html::convDateTime($datetime, $format);
   }

   public function showMassiveActions(array $params = []): string {
      return Html::showMassiveActions($params + ['display' => false]);
   }

   public function isMassiveActionchecked(string $itemtype = "", int $id = 0): bool {
      return isset($_SESSION['glpimassiveactionselected'][$itemtype][$id]);
   }
}
