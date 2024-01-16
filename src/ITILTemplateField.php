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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Search\SearchOption;

/**
 * ITILTemplateMandatoryField Class
 *
 * Predefined fields for ITIL template class
 *
 * @since 9.5.0
 **/
abstract class ITILTemplateField extends CommonDBChild
{
    public static $itemtype; //to be filled in subclass
    public static $items_id; //to be filled in subclass
    public static $itiltype; //to be filled in subclass

    private $all_fields;

   // From CommonDBTM
    public $dohistory = true;


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'clone';
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * Get fields list
     *
     * @param ITILTemplate $tt ITIL Template
     *
     * @return array
     */
    public function getAllFields(ITILTemplate $tt)
    {
        $this->all_fields = $tt->getAllowedFieldsNames(true);
        $this->all_fields = array_diff_key($this->all_fields, static::getExcludedFields());
        return $this->all_fields;
    }


    protected function computeFriendlyName()
    {
        $tt_class = static::$itemtype;
        $tt     = new $tt_class();
        $fields = $tt->getAllowedFieldsNames(true);

        if (isset($fields[$this->fields["num"]])) {
            return $fields[$this->fields["num"]];
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        static::showForITILTemplate($item, $withtemplate);
        return true;
    }


    /**
     * Return fields who doesn't need to be used for this part of template
     *
     * @since 9.2
     *
     * @return array the excluded fields (keys and values are equals)
     */
    abstract public static function getExcludedFields();


    /**
     * Print the fields
     *
     * @since 0.83
     *
     * @param ITILTemplate $tt           ITIL Template
     * @param integer      $withtemplate Template or basic item (default 0)
     *
     * @return void
     **/
    public static function showForITILTemplate(ITILTemplate $tt, $withtemplate = 0)
    {
        /**
         * @var \DBmysql $DB
         * @var array $CFG_GLPI
         */
        global $DB, $CFG_GLPI;

        $ID = $tt->fields['id'];

        if (!$tt->getFromDB($ID) || !$tt->can($ID, READ)) {
            return false;
        }
        $canedit = $tt->canEdit($ID);
        $fields  = $tt->getAllowedFieldsNames(false);
        $fields  = array_diff_key($fields, static::getExcludedFields());
        $simplified_fields = $tt->getSimplifiedInterfaceFields();
        $both_interfaces_label = sprintf(__('%1$s + %2$s'), __('Simplified interface'), __('Standard interface'));
        $display_options = [
            'relative_dates' => true,
            'comments'       => true,
            'html'           => true
        ];
        $itil_class    = static::$itiltype;
        $searchOption  = SearchOption::getOptionsForItemtype($itil_class);
        $itil_object   = new $itil_class();
        $rand = mt_rand();

        $crtiteria = [
            'SELECT' => ['id', 'num'],
            'FROM'   => static::getTable(),
            'WHERE'  => [static::$items_id => $ID]
        ];
        if (is_subclass_of(static::class, ITILTemplatePredefinedField::class)) {
            $crtiteria['SELECT'][] = 'value';
        }
        $iterator = $DB->request($crtiteria);
        $entries = [];

        $numrows = count($iterator);

        $used         = [];
        foreach ($iterator as $data) {
            if (!array_key_exists($data['num'], $fields)) {
                // Ignore deleted/unavailable fields
                continue;
            }
            $interface_label = in_array($data['num'], $simplified_fields, false) ? $both_interfaces_label : __('Standard interface');
            $entry = [
                'itemtype' => static::class,
                'id'       => $data['id'],
                'name'     => $fields[$data['num']],
                'interface' => $interface_label,
            ];
            if (is_subclass_of(static::class, ITILTemplatePredefinedField::class)) {
                $display_datas[$searchOption[$data['num']]['field']] = $data['value'];
                $value_label = $itil_object->getValueToDisplay(
                    $searchOption[$data['num']],
                    $display_datas,
                    $display_options
                );
                $entry['value'] = $value_label;
            }
            $entries[] = $entry;
            $used[$data['num']]        = $data['num'];
        }

        $fields_dropdown_values = [];
        foreach ($fields as $k => $field) {
            $is_simple_field = in_array($k, $simplified_fields, false);
            $label = sprintf(__('%1$s (%2$s)'), $field, $is_simple_field ? $both_interfaces_label : __('Standard interface'));
            $fields_dropdown_values[$k] = $label;
        }

        if (is_subclass_of(static::class, ITILTemplatePredefinedField::class)) {
            $fields_dropdown_values = array_replace([
                -1 => Dropdown::EMPTY_VALUE
            ], $fields_dropdown_values);
        }

        if ($canedit) {
            $extra_form_html = '';
            if (is_subclass_of(static::class, ITILTemplatePredefinedField::class)) {
                $embedded_ma_params = [
                    'id_field'         => '__VALUE__',
                    'itemtype'         => static::$itiltype,
                    'inline'           => true,
                    'submitname'       => _sx('button', 'Add'),
                    'options'          => [
                        'relative_dates'     => 1,
                        'with_time'          => 1,
                        'with_days'          => 0,
                        'with_specific_date' => 0,
                        'itemlink_as_string' => 1,
                        'entity'             => $tt->getEntityID()
                    ]
                ];
                $extra_form_html = Ajax::updateItemOnSelectEvent(
                    "dropdown_num{$rand}",
                    "show_massiveaction_field",
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveActionField.php",
                    $embedded_ma_params,
                    false
                );
                $extra_form_html .= "<div id='show_massiveaction_field'></div>";
            }

            $twig_params = [
                'form_url' => static::getFormURL(),
                'items_id_field' => static::$items_id,
                'itemtype_name' => static::getTypeName(),
                'used' => $used,
                'id' => $ID,
                'fields' => $fields_dropdown_values,
                'extra_form_html' => $extra_form_html,
                'rand' => $rand,
                'show_submit' => !is_subclass_of(static::class, ITILTemplatePredefinedField::class)
            ];
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
                <div>
                    <form name="itiltemplatehidden_form{{ rand }}" method="post" action="{{ form_url }}" data-submit-once>
                        {{ inputs.hidden('_glpi_csrf_token', csrf_token()) }}
                        {{ inputs.hidden(items_id_field, id) }}
                        <div class="d-flex justify-content-center flex-wrap">
                            {{ fields.dropdownArrayField('num', 0, fields, null, {
                                no_label: true,
                                used: used,
                                add_field_attribs: {
                                    'aria-label': itemtype_name
                                },
                                rand: rand
                            }) }}
                            {{ extra_form_html|raw }}
                            {% if show_submit %}
                                <div class="ms-2">{{ inputs.submit('add', _x('button', 'Add')) }}</div>
                            {% endif %}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $columns = [
            'name' => __('Name'),
            'interface' => __("Profile's interface")
        ];
        if (is_subclass_of(static::class, ITILTemplatePredefinedField::class)) {
            $columns['value'] = __('Value');
        }
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => $columns,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                'container'     => 'mass' . static::class . $rand
            ],
        ]);
    }


    /**
     * Get field num from its name
     *
     * @param ITILTemplate $tt   ITIL Template
     * @param string       $name Field name to look for
     *
     * @return integer|false
     */
    public function getFieldNum(ITILTemplate $tt, $name)
    {
        if ($this->all_fields === null) {
            $this->getAllFields($tt);
        }
        return array_search($name, $this->all_fields);
    }


    public function getItem($getFromDB = true, $getEmpty = true)
    {
        $item_class = static::$itemtype;
        if ($item_class == 'ITILTemplate') {
            if (isset($this->fields['itiltype'])) {
                $item_class = $this->fields['itiltype'] . 'Template';
            }
            if (isset($this->input['itiltype'])) {
                $item_class = $this->input['itiltype'] . 'Template';
            }
        }

        return $this->getConnexityItem(
            $item_class,
            static::$items_id,
            $getFromDB,
            $getEmpty
        );
    }
}
