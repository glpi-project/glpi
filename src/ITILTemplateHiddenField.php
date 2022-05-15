<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * ITILTemplateHiddenField Class
 *
 * Predefined fields for ITIL template class
 *
 * @since 9.5.0
 **/
abstract class ITILTemplateHiddenField extends ITILTemplateField
{
    public static function getTypeName($nb = 0)
    {
        return _n('Hidden field', 'Hidden fields', $nb);
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


    public function post_purgeItem()
    {
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
                    static::$items_id => $this->fields[static::$itiltype],
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


    /**
     * Get hidden fields for a template
     *
     * @since 0.83
     *
     * @param $ID                    integer  the template ID
     * @param $withtypeandcategory   boolean  with type and category (false by default)
     *
     * @return array of hidden fields
     **/
    public function getHiddenFields($ID, $withtypeandcategory = false)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [static::$items_id => $ID],
            'ORDER'  => 'id'
        ]);

        $tt_class       = static::$itemtype;
        $tt             = new $tt_class();
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
     * @since 9.2
     *
     * @return array the excluded fields (keys and values are equals)
     */
    public static function getExcludedFields()
    {
        return [
            175 => 175, // ticket's tasks (template)
        ];
    }


    /**
     * Print the hidden fields
     *
     * @since 0.83
     *
     * @param ITILTemplate $tt            ITIL Template
     * @param boolean      $withtemplate  Template or basic item (default 0)
     *
     * @return void
     **/
    public static function showForITILTemplate(ITILTemplate $tt, $withtemplate = 0)
    {
        global $DB;

        $ID = $tt->fields['id'];

        if (!$tt->getFromDB($ID) || !$tt->can($ID, READ)) {
            return false;
        }
        $canedit = $tt->canEdit($ID);
        $ttm     = new static();
        $fields  = $tt->getAllowedFieldsNames(false);
        $fields  = array_diff_key($fields, self::getExcludedFields());
        $rand    = mt_rand();

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [static::$items_id => $ID]
        ]);

        $numrows = count($iterator);

        $hiddenfields = [];
        $used         = [];
        foreach ($iterator as $data) {
            $hiddenfields[$data['id']] = $data;
            $used[$data['num']]        = $data['num'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='changeproblem_form$rand' id='changeproblem_form$rand' method='post'
                  action='" . $ttm->getFormURL() . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add a hidden field') . "</th></tr>";
            echo "<tr class='tab_bg_2'><td class='right'>";
            echo "<input type='hidden' name='" . static::$items_id . "' value='$ID'>";
            Dropdown::showFromArray('num', $fields, ['used' => $used]);
            echo "</td><td class='center'>";
            echo "&nbsp;<input type='submit' name='add' value=\"" . _sx('button', 'Add') .
                        "\" class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . $ttm->getType() . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                'container'     => 'mass' . $ttm->getType() . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='2'>";
        echo static::getTypeName(count($iterator));
        echo "</th></tr>";
        if ($numrows) {
            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_top    .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('mass' . $ttm->getType() . $rand) . "</th>";
                $header_bottom .= "<th width='10'>";
                $header_bottom .= Html::getCheckAllAsCheckbox('mass' . $ttm->getType() . $rand) . "</th>";
            }
            $header_end .= "<th>" . __('Name') . "</th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            foreach ($hiddenfields as $data) {
                echo "<tr class='tab_bg_2'>";
                if ($canedit) {
                    echo "<td>" . Html::getMassiveActionCheckBox($ttm->getType(), $data["id"]) . "</td>";
                }
                echo "<td>" . $fields[$data['num']] . "</td>";
                echo "</tr>";
            }
            echo $header_begin . $header_bottom . $header_end;
        } else {
            echo "<tr><th colspan='2'>" . __('No item found') . "</th></tr>";
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
