<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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


/**
 * ITILTemplateReadonlyField Class
 *
 * Predefined fields for ITIL template class
 *
 * @since 11.0.0
 **/
abstract class ITILTemplateReadonlyField extends ITILTemplateField
{
    public static function getTypeName($nb = 0)
    {
        return _n('Read only field', 'Read only fields', $nb);
    }

    public static function getIcon(): string
    {
        return 'ti ti-lock';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        // can exists for template
        if (
            $item instanceof ITILTemplate
            && Session::haveRight("itiltemplate", READ)
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    $this->getTable(),
                    [static::$items_id => $item->getID()]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }


    public function post_purgeItem()
    {
        global $DB;

        parent::post_purgeItem();

        $itil_object = getItemForItemtype(static::$itiltype);
        $itemtype_id = $itil_object->getSearchOptionIDByField('field', 'itemtype', $itil_object->getTable());
        $items_id_id = $itil_object->getSearchOptionIDByField('field', 'items_id', $itil_object->getTable());

        // Try to delete itemtype -> delete items_id
        if ($this->fields['num'] == $itemtype_id) {
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    static::$items_id => $this->fields[static::$itiltype],
                    'num'             => $items_id_id,
                ],
            ]);
            if (count($iterator)) {
                $result = $iterator->current();
                $a = new static();
                $a->delete(['id' => $result['id']]);
            }
        }
    }


    /**
     * Get Readonly fields for a template
     *
     * @param $ID                    integer  the template ID
     * @param $withtypeandcategory   boolean  with type and category (false by default)
     *
     * @return array of Readonly fields
     **/
    public function getReadonlyFields($ID, $withtypeandcategory = false)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [static::$items_id => $ID],
            'ORDER'  => 'id',
        ]);

        $tt             = getItemForItemtype(static::$itemtype);
        $allowed_fields = $tt->getAllowedFields($withtypeandcategory);
        $fields         = [];

        foreach ($iterator as $rule) {
            if (isset($allowed_fields[$rule['num']])) {
                $fields[$allowed_fields[$rule['num']]] = $rule['num'];
            }
        }
        return $fields;
    }


    /**
     * Return fields who doesn't need to be used for this part of template
     *
     * @return array the excluded fields (keys and values are equals)
     */
    public static function getExcludedFields()
    {
        return [
            175 => 175, // ticket's tasks (template)
            1   => 1,   // Title
            21  => 21,  // Description
            // Special cases, not yet handled:
            4   => 4,   // Requester
            5   => 5,   // Technician
            6   => 6,   // Assigned to a supplier
            8   => 8,   // Technician group
            13  => 13,  // Associated elements
            52  => 52,  // Approval
            65  => 65,  // Observer group
            66  => 66,  // Observer
            71  => 71,  // Requester group
            142 => 142, // Documents
            193 => 193, // Contract
        ];
    }
}
