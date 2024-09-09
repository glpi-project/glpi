<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Form\Destination;

use CommonGLPI;
use Glpi\Form\AnswersSet;
use Override;
use Search;

abstract class AbstractFormDestinationType extends CommonGLPI implements FormDestinationInterface
{
    #[Override]
    final public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // Only for answers set
        if (!($item instanceof AnswersSet)) {
            return "";
        }

        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = $this->countCreatedItemsForAnswersSet($item);
        }

        return self::createTabEntry(static::getTypeName(), $count, icon: static::getTargetItemtype()::getIcon());
    }

    #[Override]
    final public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        // Only for answers set
        if (!($item instanceof AnswersSet)) {
            return false;
        }

        Search::showList(static::getTargetItemtype(), [
            'criteria' => [
                [
                    'link'       => 'AND',
                    'field'      => static::getFilterByAnswsersSetSearchOptionID(), // Parent answer
                    'searchtype' => 'equals',
                    'value'      => $item->getID()
                ],
            ],
            'showmassiveactions' => false,
            'hide_controls'      => true,
            'order'              => 'DESC',
            'as_map'             => false,
        ]);
        return true;
    }

    /**
     * Count the number of created items for an answers set
     *
     * @param AnswersSet $answers
     *
     * @return int
     */
    final protected function countCreatedItemsForAnswersSet(AnswersSet $answers): int
    {
        return countElementsInTable(AnswersSet_FormDestinationItem::getTable(), [
            'forms_answerssets_id' => $answers->getID(),
            'itemtype'             => static::getTargetItemtype(),
        ]);
    }
}
