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


    protected function computeFriendlyName()
    {

        $tt_class = static::$itemtype;
        $tt     = new $tt_class();
        $fields = $tt->getAllowedFieldsNames(true, true);

        if (isset($fields[$this->fields["num"]])) {
            return $fields[$this->fields["num"]];
        }
        return '';
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
            if ((string) $input['value'] === '0') {
                Session::addMessageAfterRedirect(
                    __('You must select an associated item'),
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
        /** @var \DBmysql $DB */
        global $DB;

        parent::post_purgeItem();

        $itil_class = static::$itiltype;
        $itil_object = new $itil_class();
        $itemtype_id = $itil_object->getSearchOptionIDByField('field', 'itemtype', $itil_object->getTable());
        $items_id_id = $itil_object->getSearchOptionIDByField('field', 'items_id', $itil_object->getTable());

       // Try to delete itemtype -> delete items_id
        if ($this->fields['num'] == $itemtype_id) {
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    static::$items_id => $this->fields[static::$items_id],
                    'num'             => $items_id_id
                ]
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
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

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
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [static::$items_id => $ID],
            'ORDER'  => 'id'
        ]);

        $tt_class       = static::$itemtype;
        $tt             = new $tt_class();
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
    public static function getMultiplePredefinedValues()
    {

        $itil_class = static::$itiltype;
        $itil_object = new $itil_class();

        $itemstable = null;
        switch ($itil_class) {
            case 'Change':
                $itemstable = 'glpi_changes_items';
                break;
            case 'Problem':
                $itemstable = 'glpi_items_problems';
                break;
            case 'Ticket':
                $itemstable = 'glpi_items_tickets';
                break;
            default:
                throw new \RuntimeException('Unknown ITIL type ' . $itil_class);
        }

        $fields = [
            $itil_object->getSearchOptionIDByField('field', 'name', 'glpi_documents'),
            $itil_object->getSearchOptionIDByField('field', 'items_id', $itemstable),
            $itil_object->getSearchOptionIDByField('field', 'name', 'glpi_tasktemplates')
        ];

        return $fields;
    }

    /**
     * Return fields who doesn't need to be used for this part of template
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


    /**
     * Print the predefined fields
     *
     * @since 0.83
     *
     * @param ITILTemplate $tt            ITIL Template
     * @param integer      $withtemplate  Template or basic item (default 0)
     *
     * @return void
     **/
    public static function showForITILTemplate(ITILTemplate $tt, $withtemplate = 0)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $ID = $tt->fields['id'];

        if (!$tt->getFromDB($ID) || !$tt->can($ID, READ)) {
            return false;
        }

        $canedit       = $tt->canEdit($ID);

        $fields        = $tt->getAllowedFieldsNames(true, true);
        $fields        = array_diff_key($fields, self::getExcludedFields());

        $itil_class    = static::$itiltype;
        $searchOption  = Search::getOptions($itil_class);
        $itil_object   = new $itil_class();
        $rand          = mt_rand();

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [static::$items_id => $ID],
            'ORDER'  => 'id'
        ]);

        $display_options = [
            'relative_dates' => true,
            'comments'       => true,
            'html'           => true
        ];

        $predeffields = [];
        $used         = [];
        $numrows      = count($iterator);
        foreach ($iterator as $data) {
            $predeffields[$data['id']] = $data;
            $used[$data['num']] = $data['num'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='changeproblem_form$rand' id='changeproblem_form$rand' method='post'
               action='" . static::getFormURL() . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='3'>" . __('Add a predefined field') . "</th></tr>";
            echo "<tr class='tab_bg_2'><td class='right top' width='30%'>";
            echo "<input type='hidden' name='" . static::$items_id . "' value='$ID'>";
            $display_fields[-1] = Dropdown::EMPTY_VALUE;
            $display_fields    += $fields;

           // Unset multiple items
            $multiple = self::getMultiplePredefinedValues();
            foreach ($multiple as $val) {
                if (isset($used[$val])) {
                    unset($used[$val]);
                }
            }

            $rand_dp  = Dropdown::showFromArray('num', $display_fields, ['used' => $used,
                'toadd'
            ]);
            echo "</td><td class='top'>";
            $paramsmassaction = ['id_field'         => '__VALUE__',
                'itemtype'         => static::$itiltype,
                'inline'           => true,
                'submitname'       => _sx('button', 'Add'),
                'options'          => ['relative_dates'     => 1,
                    'with_time'          => 1,
                    'with_days'          => 0,
                    'with_specific_date' => 0,
                    'itemlink_as_string' => 1,
                    'entity'             => $tt->getEntityID()
                ]
            ];

            Ajax::updateItemOnSelectEvent(
                "dropdown_num" . $rand_dp,
                "show_massiveaction_field",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveActionField.php",
                $paramsmassaction
            );
            echo "</td><td>";
            echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . static::getType() . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                'container'     => 'mass' . static::getType() . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='3'>";
        echo self::getTypeName($numrows);
        echo "</th></tr>";
        if ($numrows) {
            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_top    .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('mass' . static::getType() . $rand) . "</th>";
                $header_bottom .= "<th width='10'>";
                $header_bottom .= Html::getCheckAllAsCheckbox('mass' . static::getType() . $rand) . "</th>";
            }
            $header_end .= "<th>" . __('Name') . "</th>";
            $header_end .= "<th>" . __('Value') . "</th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            foreach ($predeffields as $data) {
                if (!isset($fields[$data['num']])) {
                   // could happen when itemtype removed and items_id present
                    continue;
                }
                echo "<tr class='tab_bg_2'>";
                if ($canedit) {
                    echo "<td>" . Html::getMassiveActionCheckBox(static::getType(), $data["id"]) . "</td>";
                }
                echo "<td>" . $fields[$data['num']] . "</td>";

                echo "<td>";
                $display_datas[$searchOption[$data['num']]['field']] = $data['value'];
                echo $itil_object->getValueToDisplay(
                    $searchOption[$data['num']],
                    $display_datas,
                    $display_options
                );
                echo "</td>";
                echo "</tr>";
            }
            echo $header_begin . $header_bottom . $header_end;
        } else {
            echo "<tr><th colspan='3'>" . __('No item found') . "</th></tr>";
        }

        echo "</table>";
        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }
}
