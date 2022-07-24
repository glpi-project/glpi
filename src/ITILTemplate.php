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
 * ITIL Template class
 *
 * since version 0.83
 **/
abstract class ITILTemplate extends CommonDropdown
{
   // From CommonDBTM
    public $dohistory                 = true;

   // From CommonDropdown
    public $first_level_menu          = "helpdesk";

    public $display_dropdowntitle     = false;

    public static $rightname                 = 'itiltemplate';

    public $can_be_translated            = false;

   // Specific fields
   /// Mandatory Fields
    public $mandatory  = [];
   /// Hidden fields
    public $hidden     = [];
   /// Predefined fields
    public $predefined = [];
   /// Related ITIL type


    /**
     * Retrieve an item from the database with additional datas
     *
     * @since 0.83
     *
     * @param $ID                    integer  ID of the item to get
     * @param $withtypeandcategory   boolean  with type and category (true by default)
     *
     * @return true if succeed else false
     **/
    public function getFromDBWithData($ID, $withtypeandcategory = true)
    {
        if ($this->getFromDB($ID)) {
            $itiltype = str_replace('Template', '', static::getType());
            $itil_object  = new $itiltype();
            $itemstable = $itil_object->getItemsTable();
            $tth_class = $itiltype . 'TemplateHiddenField';
            $tth          = new $tth_class();
            $this->hidden = $tth->getHiddenFields($ID, $withtypeandcategory);

           // Force items_id if itemtype is defined
            if (
                isset($this->hidden['itemtype'])
                && !isset($this->hidden['items_id'])
            ) {
                $this->hidden['items_id'] = $itil_object->getSearchOptionIDByField(
                    'field',
                    'items_id',
                    $itemstable
                );
            }
           // Always get all mandatory fields
            $ttm_class = $itiltype . 'TemplateMandatoryField';
            $ttm             = new $ttm_class();
            $this->mandatory = $ttm->getMandatoryFields($ID);

           // Force items_id if itemtype is defined
            if (
                isset($this->mandatory['itemtype'])
                && !isset($this->mandatory['items_id'])
            ) {
                $this->mandatory['items_id'] = $itil_object->getSearchOptionIDByField(
                    'field',
                    'items_id',
                    $itemstable
                );
            }

            $ttp_class = $itiltype . 'TemplatePredefinedField';
            $ttp              = new $ttp_class();
            $this->predefined = $ttp->getPredefinedFields($ID, $withtypeandcategory);
           // Compute time_to_resolve
            if (isset($this->predefined['time_to_resolve'])) {
                $this->predefined['time_to_resolve']
                        = Html::computeGenericDateTimeSearch($this->predefined['time_to_resolve'], false);
            }
            if (isset($this->predefined['time_to_own'])) {
                $this->predefined['time_to_own']
                        = Html::computeGenericDateTimeSearch($this->predefined['time_to_own'], false);
            }

           // Compute internal_time_to_resolve
            if (isset($this->predefined['internal_time_to_resolve'])) {
                $this->predefined['internal_time_to_resolve']
                = Html::computeGenericDateTimeSearch($this->predefined['internal_time_to_resolve'], false);
            }
            if (isset($this->predefined['internal_time_to_own'])) {
                $this->predefined['internal_time_to_own']
                = Html::computeGenericDateTimeSearch($this->predefined['internal_time_to_own'], false);
            }

           // Compute date
            if (isset($this->predefined['date'])) {
                $this->predefined['date']
                        = Html::computeGenericDateTimeSearch($this->predefined['date'], false);
            }
            return true;
        }
        return false;
    }


    public static function getTypeName($nb = 0)
    {
        $itiltype = str_replace('Template', '', static::getType());
       //TRANS %1$S is the ITIL type
        return sprintf(
            _n('%1$s template', '%1$s templates', $nb),
            $itiltype::getTypeName()
        );
    }


    /**
     * @param boolean $withtypeandcategory (default 0)
     * @param boolean $withitemtype        (default 0)
     **/
    public static function getAllowedFields($withtypeandcategory = 0, $withitemtype = 0)
    {

        static $allowed_fields = [];

        $itiltype = str_replace('Template', '', static::getType());

       // For integer value for index
        if ($withtypeandcategory) {
            $withtypeandcategory = 1;
        } else {
            $withtypeandcategory = 0;
        }

        if ($withitemtype) {
            $withitemtype = 1;
        } else {
            $withitemtype = 0;
        }

        if (!isset($allowed_fields[$itiltype][$withtypeandcategory][$withitemtype])) {
            $itil_object = new $itiltype();
            $itemstable = $itil_object->getItemsTable();

           // SearchOption ID => name used for options
            $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype] = [
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'name',
                    $itil_object->getTable()
                )   => 'name',
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'content',
                    $itil_object->getTable()
                )   => 'content',
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'status',
                    $itil_object->getTable()
                )   => 'status',
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'urgency',
                    $itil_object->getTable()
                )   => 'urgency',
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'impact',
                    $itil_object->getTable()
                )   => 'impact',
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'priority',
                    $itil_object->getTable()
                )   => 'priority',
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'date',
                    $itil_object->getTable()
                )   => 'date',
                4                 => '_users_id_requester',
                71                => '_groups_id_requester',
                5                 => '_users_id_assign',
                8                 => '_groups_id_assign',
                $itil_object->getSearchOptionIDByField(
                    'field',
                    'name',
                    'glpi_suppliers'
                ) => '_suppliers_id_assign',

                66                => '_users_id_observer',
                65                => '_groups_id_observer',
            ];

            if ($withtypeandcategory) {
                $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype]
                [$itil_object->getSearchOptionIDByField(
                    'field',
                    'completename',
                    'glpi_itilcategories'
                )]  = 'itilcategories_id';
            }

            if ($withitemtype) {
                $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype]
                [$itil_object->getSearchOptionIDByField(
                    'field',
                    'itemtype',
                    $itemstable
                )] = 'itemtype';
            }

            $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype]
            [$itil_object->getSearchOptionIDByField(
                'field',
                'items_id',
                $itemstable
            )] = 'items_id';

           // Add validation request
            $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype][-2] = '_add_validation';

           // Add document
            $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype]
               [$itil_object->getSearchOptionIDByField(
                   'field',
                   'name',
                   'glpi_documents'
               )] = '_documents_id';

           // Add ITILTask (from task templates)
            $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype]
               [$itil_object->getSearchOptionIDByField(
                   'field',
                   'name',
                   TaskTemplate::getTable()
               )] = '_tasktemplates_id';

           //add specific itil type fields
            $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype] += static::getExtraAllowedFields($withtypeandcategory, $withitemtype);
        }

        return $allowed_fields[$itiltype][$withtypeandcategory][$withitemtype];
    }

    /**
     * Get extra allowed field names that can be used in templates_id
     *
     * @since 9.5.0
     *
     * @param boolean $withtypeandcategory (default 0)
     * @param boolean $withitemtype        (default 0)
     *
     * @return array
     *
     * @see self::getAllowedFields()
     */
    public static function getExtraAllowedFields($withtypeandcategory = 0, $withitemtype = 0)
    {
        return [];
    }


    /**
     * @param $withtypeandcategory   (default 0)
     * @param $with_items_id         (default 0)
     **/
    public function getAllowedFieldsNames($withtypeandcategory = 0, $with_items_id = 0)
    {

        $itiltype = str_replace('Template', '', static::getType());
        $searchOption = Search::getOptions($itiltype);
        $tab          = $this->getAllowedFields($withtypeandcategory, $with_items_id);
        foreach (array_keys($tab) as $ID) {
            switch ($ID) {
                case -2:
                    $tab[-2] = __('Approval request');
                    break;

                case 175:
                    $tab[175] = CommonITILTask::getTypeName();
                    break;

                default:
                    if (isset($searchOption[$ID]['name'])) {
                        $tab[$ID] = $searchOption[$ID]['name'];
                    }
            }
        }

        return $tab;
    }


    /**
     *  ??since version 0.84
     **/
    public function getSimplifiedInterfaceFields()
    {

        $ticket = new Ticket();
        $fields = [$ticket->getSearchOptionIDByField('field', 'name', 'glpi_tickets'),
            $ticket->getSearchOptionIDByField('field', 'content', 'glpi_tickets'),
            $ticket->getSearchOptionIDByField('field', 'urgency', 'glpi_tickets'),
            $ticket->getSearchOptionIDByField('field', 'completename', 'glpi_locations'),
            $ticket->getSearchOptionIDByField('field', 'itemtype', 'glpi_tickets'),
            $ticket->getSearchOptionIDByField(
                'field',
                'completename',
                'glpi_itilcategories'
            ),
            $ticket->getSearchOptionIDByField('field', 'type', 'glpi_tickets'),
            $ticket->getSearchOptionIDByField('field', 'items_id', 'glpi_tickets'),
            $ticket->getSearchOptionIDByField('field', 'name', 'glpi_documents'),
            66 // users_id_observer
        ];
        return $fields;
    }


    public function defineTabs($options = [])
    {
        $ong          = [];
        $this->addDefaultFormTab($ong);
        $itiltype = str_replace('Template', '', static::getType());
        $this->addStandardTab($itiltype . 'TemplateMandatoryField', $ong, $options);
        $this->addStandardTab($itiltype . 'TemplatePredefinedField', $ong, $options);
        $this->addStandardTab($itiltype . 'TemplateHiddenField', $ong, $options);
        $this->addStandardTab($itiltype . 'Template', $ong, $options);
        $this->addStandardTab('ITILCategory', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof ITILTemplate) {
            switch ($tabnum) {
                case 1:
                    $item->showCentralPreview($item);
                    return true;
            }
        }
        return false;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (Session::haveRight(static::$rightname, READ)) {
            switch ($item->getType()) {
                case 'TicketTemplate':
                    return [
                        1 => __('Standard interface'),
                        2 => __('Simplified interface')
                    ];
                case 'ChangeTemplate':
                case 'ProblemTemplate':
                    return [1 => __('Preview')];
            }
        }
        return '';
    }


    /**
     * Get mandatory mark if field is mandatory
     *
     * @since 0.83
     *
     * @param $field  string   field
     * @param $force  boolean  force display based on global config (false by default)
     *
     * @return string to display
     **/
    public function getMandatoryMark($field, $force = false)
    {

        if ($force || $this->isMandatoryField($field)) {
            return "<span class='required'>*</span>";
        }
        return '';
    }


    /**
     * Is it an hidden field ?
     *
     * @since 0.83
     *
     * @param $field string field
     *
     * @return bool
     **/
    public function isHiddenField($field)
    {

        if (isset($this->hidden[$field])) {
            return true;
        }
        return false;
    }


    /**
     * Is it an predefined field ?
     *
     * @since 0.83
     *
     * @param $field string field
     *
     * @return bool
     **/
    public function isPredefinedField($field)
    {

        if (isset($this->predefined[$field])) {
            return true;
        }
        return false;
    }


    /**
     * Is it an mandatory field ?
     *
     * @since 0.83
     *
     * @param $field string field
     *
     * @return bool
     **/
    public function isMandatoryField($field)
    {

        if (isset($this->mandatory[$field])) {
            return true;
        }
        return false;
    }


    /**
     * Print preview for ITIL template
     *
     * @since 0.83
     *
     * @param $tt ITILTemplate object
     *
     * @return void
     **/
    public static function showCentralPreview(ITILTemplate $tt)
    {

        if (!$tt->getID()) {
            return false;
        }
        if ($tt->getFromDBWithData($tt->getID())) {
            $itiltype = str_replace('Template', '', static::getType());
            $itil_object = new $itiltype();
            $itil_object->showForm(0, ['template_preview' => $tt->getID()]);
        }
    }


    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if (
            $isadmin
            &&  $this->maybeRecursive()
            && (count($_SESSION['glpiactiveentities']) > 1)
        ) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'merge'] = __('Merge and assign to current entity');
        }

        return $actions;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'merge':
                echo "&nbsp;" . $_SESSION['glpiactive_entity_shortname'];
                echo "<br><br>" . Html::submit(_x('button', 'Merge'), ['name' => 'massiveaction']);
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'merge':
                foreach ($ids as $key) {
                    if ($item->can($key, UPDATE)) {
                        if ($item->getEntityID() == $_SESSION['glpiactive_entity']) {
                            if (
                                $item->update(['id'           => $key,
                                    'is_recursive' => 1
                                ])
                            ) {
                                 $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $input2 = $item->fields;
                         // Change entity
                            $input2['entities_id']  = $_SESSION['glpiactive_entity'];
                            $input2['is_recursive'] = 1;
                            $input2 = Toolbox::addslashes_deep($input2);

                            if (!$item->import($input2)) {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                            }
                        }
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * Merge fields linked to template
     *
     * @since 0.90
     *
     * @param $target_id
     * @param  $source_id
     **/
    public function mergeTemplateFields($target_id, $source_id)
    {
        global $DB;

       // Tables linked to ticket template
        $to_merge = ['predefinedfields', 'mandatoryfields', 'hiddenfields'];
        $itiltype = str_replace('Template', '', static::getType());

       // Source fields
        $source = [];
        foreach ($to_merge as $merge) {
            $source[$merge] = $this->formatFieldsToMerge(
                getAllDataFromTable(
                    'glpi_' . $itiltype . 'template' . $merge,
                    [$itiltype . 'templates_id' => $source_id]
                )
            );
        }

       // Target fields
        $target = [];
        foreach ($to_merge as $merge) {
            $target[$merge] = $this->formatFieldsToMerge(
                getAllDataFromTable(
                    'glpi_' . $itiltype . 'template' . $merge,
                    [$itiltype . 'templates_id' => $target_id]
                )
            );
        }

       // Merge
        foreach ($source as $merge => $data) {
            foreach ($data as $key => $val) {
                if (!array_key_exists($key, $target[$merge])) {
                    $DB->update(
                        'glpi_' . $itiltype . 'template' . $merge,
                        [
                            $itiltype . 'templates_id' => $target_id
                        ],
                        [
                            'id' => $val['id']
                        ]
                    );
                }
            }
        }
    }


    /**
     * Merge Itilcategories linked to template
     *
     * @since 0.90
     *
     * @param $target_id
     * @param $source_id
     */
    public function mergeTemplateITILCategories($target_id, $source_id)
    {
        global $DB;

        $to_merge = [];
        switch (static::getType()) {
            case Ticket::getType():
                $to_merge = ['tickettemplates_id_incident', 'tickettemplates_id_demand'];
                break;
            default:
                $to_merge = [strtolower($this->getType() . 'templates_id')];
                break;
        }

       // Source categories
        $source = [];
        foreach ($to_merge as $merge) {
            $source[$merge] = getAllDataFromTable('glpi_itilcategories', [$merge => $source_id]);
        }

       // Target categories
        $target = [];
        foreach ($to_merge as $merge) {
            $target[$merge] = getAllDataFromTable('glpi_itilcategories', [$merge => $target_id]);
        }

       // Merge
        $template = new static();
        foreach ($source as $merge => $data) {
            foreach ($data as $key => $val) {
                $template->getFromDB($target_id);
                if (
                    !array_key_exists($key, $target[$merge])
                    && in_array($val['entities_id'], $_SESSION['glpiactiveentities'])
                ) {
                    $DB->update(
                        'glpi_itilcategories',
                        [
                            $merge => $target_id
                        ],
                        [
                            'id' => $val['id']
                        ]
                    );
                }
            }
        }
    }


    /**
     * Format template fields to merge
     *
     * @since 0.90
     *
     * @param $data
     **/
    public function formatFieldsToMerge($data)
    {

        $output = [];
        foreach ($data as $val) {
            $output[$val['num']] = $val;
        }

        return $output;
    }


    /**
     * Import a dropdown - check if already exists
     *
     * @since 0.90
     *
     * @param array $input  array of value to import (name, ...)
     *
     * @return integer|boolean true in case of success, -1 otherwise
     **/
    public function import(array $input)
    {

        if (!isset($input['name'])) {
            return -1;
        }
       // Clean datas
        $input['name'] = trim($input['name']);

        if (empty($input['name'])) {
            return -1;
        }

       // Check twin
        $ID = $this->findID($input);
        if ($ID > 0) {
           // Merge data
            $this->mergeTemplateFields($ID, $input['id']);
            $this->mergeTemplateITILCategories($ID, $input['id']);

           // Delete source
            $this->delete($input, 1);

           // Update destination with source input
            $input['id'] = $ID;
        }

        $this->update($input);
        return true;
    }


    /**
     * Forbidden massive action
     *
     * @since 0.90
     *
     * @see CommonDBTM::getForbiddenStandardMassiveAction()
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'merge';

        return $forbidden;
    }


    public static function getIcon()
    {
        return "fas fa-layer-group";
    }
}
