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
 * RegisteredID class
 * @since 0.85
 **/
class RegisteredID extends CommonDBChild
{
    // From CommonDBTM
    public $auto_message_on_action = false;

    // From CommonDBChild
    public static $itemtype        = 'itemtype';
    public static $items_id        = 'items_id';
    public $dohistory              = true;

    public static function getRegisteredIDTypes(): array
    {
        return [
            'PCI' => __('PCI'),
            'USB' => __('USB'),
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Registered ID (issued by PCI-SIG)', 'Registered IDs (issued by PCI-SIG)', $nb);
    }

    #[Override()]
    public static function getJSCodeToAddForItemChild($field_name, $child_count_js_var): string
    {
        $html = "<select name='" . htmlescape($field_name) . "_type[-__JS_PLACEHOLDER__]'>"
            . "<option value=''>" . htmlescape(Dropdown::EMPTY_VALUE) . "</option>";

        foreach (self::getRegisteredIDTypes() as $name => $label) {
            $name = htmlescape($name);
            $label = htmlescape($label);
            $html .= "<option value='$name'>$label</option>";
        }
        $html .= "</select> : ";
        $html .= "<input type='text' size='30' name='" . htmlescape($field_name) . "[-__JS_PLACEHOLDER__]'>";

        return str_replace(
            '__JS_PLACEHOLDER__',
            "'+{$child_count_js_var}+'", // string closing, + operator, JS variable name, + operator, string reopening
            jsescape($html)
        );
    }

    public function showChildForItemForm($canedit, $field_name, $id, bool $display = true)
    {
        if (self::isNewID($this->getID())) {
            $value = '';
        } else {
            $value = $this->getName();
        }
        $value             = htmlescape($value);
        $result            = "";
        $main_field        = htmlescape($field_name . "[$id]");
        $type_field        = htmlescape($field_name . "_type[$id]");
        $registeredIDTypes = self::getRegisteredIDTypes();

        if ($canedit) {
            $result .= "<select name='$type_field' class='form-select w-auto d-inline'>";
            $result .= "<option value=''>" . Dropdown::EMPTY_VALUE . "</option>";
            foreach ($registeredIDTypes as $name => $label) {
                $result .= sprintf(
                    "<option value='%s'%s>%s</option>",
                    htmlescape($name),
                    $this->fields['device_type'] === $name ? " selected" : "",
                    htmlescape($label)
                );
            }
            $result .= "</select> : <input type='text' size='30' name='$main_field' value='$value' class='form-control'>\n";
        } else {
            $result .= "<input type='hidden' name='$main_field' value='$value' class='form-control'>";
            if (!empty($this->fields['device_type'])) {
                $result .= sprintf(
                    __s('%1$s: %2$s'),
                    htmlescape($registeredIDTypes[$this->fields['device_type']]),
                    $value
                );
            } else {
                $result .= $value;
            }
        }

        if ($display) {
            echo $result;
        } else {
            return $result;
        }
    }
}
