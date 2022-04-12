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

/**
 * @since 10.1.0
 *
 * Problem_Problem Class
 *
 * Relation between Problems and other Problems
 **/
class Problem_Problem extends CommonITILObject_CommonITILObject
{
    // From CommonDBRelation
    public static $itemtype_1   = 'Problem';
    public static $items_id_1   = 'problems_id_1';

    public static $itemtype_2   = 'Problem';
    public static $items_id_2   = 'problems_id_2';

    public static function getTypeName($nb = 0)
    {
        return _n('Link Problem/Problem', 'Links Problem/Problem', $nb);
    }
}
