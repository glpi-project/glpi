<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Event;

/**
 * KnowbaseItemTranslation Class
 *
 * @since 0.85
 **/
class KnowbaseItemTranslation extends CommonDBChild
{
    public static string $itemtype = KnowbaseItem::class;
    public static string $items_id = 'knowbaseitems_id';
    public bool $dohistory       = true;
    public static bool $logs_for_parent = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Translation', 'Translations', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-language';
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    /**
     * Get a translation for a value
     *
     * @param KnowbaseItem $item   item to translate
     * @param string       $field  field to return (default 'name')
     *
     * @return string  the field translated if a translation is available, or the original field if not
     **/
    public static function getTranslatedValue(KnowbaseItem $item, $field = "name")
    {
        $obj   = new self();
        $found = $obj->find([
            'knowbaseitems_id'   => $item->getID(),
            'language'           => $_SESSION['glpilanguage'],
        ]);

        if (
            (count($found) > 0)
            && in_array($field, ['name', 'answer'])
        ) {
            $first = array_shift($found);
            if ($first[$field] !== null && $first[$field] !== "") {
                return $first[$field];
            }
        }
        return $item->fields[$field] ?? "";
    }

    /**
     * Return the number of translations for an item
     *
     * @param KnowbaseItem $item
     *
     * @return int  the number of translations for this item
     **/
    public static function getNumberOfTranslationsForItem($item)
    {
        return countElementsInTable(
            getTableForItemType(self::class),
            ['knowbaseitems_id' => $item->getID()]
        );
    }

    /**
     * Get already translated languages for item
     *
     * @param KnowbaseItem $item
     *
     * @return array Array of already translated languages
     **/
    public static function getAlreadyTranslatedForItem(KnowbaseItem $item): array
    {
        global $DB;

        $tab = [];

        $iterator = $DB->request([
            'SELECT' => ['language'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['knowbaseitems_id' => $item->getID()],
        ]);

        foreach ($iterator as $data) {
            $tab[$data['language']] = $data['language'];
        }
        return $tab;
    }

    public function pre_updateInDB()
    {
        $revision = new KnowbaseItem_Revision();
        $translation = new KnowbaseItemTranslation();
        $translation->getFromDB($this->getID());
        $revision->createNew($translation);
    }

    /**
     * Reverts item translation contents to specified revision
     *
     * @param int $revid Revision ID
     *
     * @return bool
     */
    public function revertTo($revid)
    {
        $revision = new KnowbaseItem_Revision();
        $revision->getFromDB($revid);

        $values = [
            'id'     => $this->getID(),
            'name'   => $revision->fields['name'],
            'answer' => $revision->fields['answer'],
        ];

        if ($this->update($values)) {
            Event::log(
                $this->getID(),
                "knowbaseitemtranslation",
                5,
                "tools",
                //TRANS: %1$s is the user login, %2$s the revision number
                sprintf(__('%1$s reverts item translation to revision %2$s'), $_SESSION["glpiname"], $revision->fields['revision'])
            );
            return true;
        }

        return false;
    }
}
