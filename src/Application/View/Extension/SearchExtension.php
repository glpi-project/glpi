<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class SearchExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('showItem', [$this, 'showItem']),
        ];
    }

    public function showItem(
        int $displaytype,
        string $value = null,
        int $num = 0,
        int $row = 0,
        string $extraparams = ""
    ): string {
       // This is mandatory as Search::showItem expected third param to be passed by reference...
        return Search::showItem($displaytype, $value, $num, $row, $extraparams);
    }
}
