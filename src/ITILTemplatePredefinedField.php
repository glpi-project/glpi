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
 * ITILTemplatePredefinedField Class
 *
 * Predefined fields for ITIL template class
 *
 * @since 9.5.0
 **/
abstract class ITILTemplatePredefinedField extends ITILTemplateField
{
    public static function getTypeName($nb = 0)
    {
        return _n('Predefined field', 'Predefined fields', $nb);
    }

    public static function getIcon(): string
    {
        return 'ti ti-forms';
    }

    protected function computeFriendlyName()
    {

        $tt     = getItemForItemtype(static::$itemtype);
        $fields = $tt->getAllowedFieldsNames(true, true);
        return $fields[$this->fields["num"]] ?? '';
    }


    public function prepareInputForAdd($input)
    {
        // Use massiveaction system to manage add system.
        // Need to update data : value not set but
        if (!isset($input['value'])) {
            if (isset($input['field']) && isset($input[$input['field']])) {
                $input['value'] = $input[$input['field']];
                unset($input[$input['field']]);
                unset($input['field']);
            }
        }

        if ((int) $input['num'] === 13) { // 13 - Search option ID for Associated Items for CommonITILObject types
            // An itemtype must be selected
            if ((string) $input['value'] === '0') {
                Session::addMessageAfterRedirect(
                    __s('You must select an associated item'),
                    true,
                    ERROR
                );
                return false;
            }

            // An item must be selected
            if (isset($input['add_items_id']) && $input['add_items_id'] == 0) {
                Session::addMessageAfterRedirect(
                    __s('You must select an associated item'),
                    true,
                    ERROR
                );
                return false;
            }
        }

        return parent::prepareInputForAdd($input);
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
                    static::$items_id => $this->fields[static::$items_id],
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


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof ITILTemplate) {
            return false;
        }

        self::showForITILTemplate($item, $withtemplate);
        return true;
    }


    /**
     * Get predefined fields for a template
     *
     * @since 0.83
     *
     * @param integer $ID                   the template ID
     * @param boolean $withtypeandcategory  with type and category (false by default)
     *
     * @return array of predefined fields
     **/
    public function getPredefinedFields($ID, $withtypeandcategory = false)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [static::$items_id => $ID],
            'ORDER'  => 'id',
        ]);

        $tt             = getItemForItemtype(static::$itemtype);
        $allowed_fields = $tt->getAllowedFields($withtypeandcategory, true);
        $fields         = [];
        $multiple       = self::getMultiplePredefinedValues();
        foreach ($iterator as $rule) {
            if (isset($allowed_fields[$rule['num']])) {
                if (in_array($rule['num'], $multiple)) {
                    if ($allowed_fields[$rule['num']] == 'items_id') {
                        $item_itemtype = explode("_", $rule['value']);
                        if (count($item_itemtype) != 2) {
                            // Invalid value. Just ignore.
                            continue;
                        }
                        $fields[$allowed_fields[$rule['num']]][$item_itemtype[0]][$item_itemtype[1]] = $item_itemtype[1];
                    } else {
                        $fields[$allowed_fields[$rule['num']]][] = $rule['value'];
                    }
                } else {
                    $fields[$allowed_fields[$rule['num']]] = $rule['value'];
                }
            }
        }
        return $fields;
    }


    /**
     * @since 0.85
     **/
    public static function getMultiplePredefinedValues(): array
    {

        $itil_class = static::$itiltype;
        $itil_object = getItemForItemtype(static::$itiltype);

        $itemstable = null;
        switch ($itil_class) {
            case Change::class:
                $itemstable = 'glpi_changes_items';
                break;
            case Problem::class:
                $itemstable = 'glpi_items_problems';
                break;
            case Ticket::class:
                $itemstable = 'glpi_items_tickets';
                break;
            default:
                throw new RuntimeException('Unknown ITIL type ' . $itil_class);
        }

        $fields = [
            $itil_object->getSearchOptionIDByField('field', 'name', 'glpi_documents'),
            $itil_object->getSearchOptionIDByField('field', 'items_id', $itemstable),
            $itil_object->getSearchOptionIDByField('field', 'name', 'glpi_tasktemplates'),
        ];

        return $fields;
    }

    /**
     * Return fields who don't need to be used for this part of template
     *
     * @since 9.2
     *
     * @return array the excluded fields (keys and values are equals)
     */
    public static function getExcludedFields()
    {
        return [
            -2 => -2, // validation request
            52  => 52, // global_validation
            142  => 142, // documents
        ];
    }
}
