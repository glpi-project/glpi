<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
     * @param boolean      $withtemplate Template or basic item (default 0)
     *
     * @return void
     **/
    abstract public static function showForITILTemplate(ITILTemplate $tt, $withtemplate = 0);


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
