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
use Glpi\ContentTemplates\TemplateManager;
use Glpi\Toolbox\Sanitizer;

/**
 * ITILSolution Class
 **/
class ITILSolution extends CommonDBChild
{
   // From CommonDBTM
    public $dohistory                   = true;
    private $item                       = null;

    public static $itemtype = 'itemtype'; // Class name or field name (start with itemtype) for link to Parent
    public static $items_id = 'items_id'; // Field name

    public static function getNameField()
    {
        return 'id';
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Solution', 'Solutions', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->isNewItem()) {
            return '';
        }
        if ($item->maySolve()) {
            $nb    = 0;
            $title = self::getTypeName(Session::getPluralNumber());
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countFor($item->getType(), $item->getID());
            }
            return self::createTabEntry($title, $nb);
        }
        return '';
    }

    public static function canView()
    {
        return Session::haveRight('ticket', READ)
             || Session::haveRight('change', READ)
             || Session::haveRight('problem', READ);
    }

    public static function canUpdate()
    {
       //always true, will rely on ITILSolution::canUpdateItem
        return true;
    }

    public function canUpdateItem()
    {
        return $this->item->maySolve();
    }

    public static function canCreate()
    {
       //always true, will rely on ITILSolution::canCreateItem
        return true;
    }

    public function canCreateItem()
    {
        $item = new $this->fields['itemtype']();
        $item->getFromDB($this->fields['items_id']);
        return $item->canSolve();
    }

    public function canEdit($ID)
    {
        return $this->item->maySolve();
    }

    public function post_getFromDB()
    {
        // Bandaid to avoid loading parent item if not needed
        // TODO: replace by proper lazy loading in GLPI 10.1
        if (
            $this->item == null // No item loaded
            || $this->item->getType() !== $this->fields['itemtype'] // Another item is loaded
            || $this->item->getID() !== $this->fields['items_id']   // Another item is loaded
        ) {
            $this->item = new $this->fields['itemtype']();
            $this->item->getFromDB($this->fields['items_id']);
        }
    }

    /**
     * Print the phone form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - item: CommonITILObject instance
     *     - kb_id_toload: load new item content from KB entry
     *
     * @return boolean item found
     **/
    public function showForm($ID, array $options = [])
    {
        if ($this->isNewItem()) {
            $this->getEmpty();
        }

        if (isset($options['kb_id_toload']) && $options['kb_id_toload'] > 0) {
            $kb = new KnowbaseItem();
            if ($kb->getFromDB($options['kb_id_toload'])) {
                $this->fields['content'] = $kb->getField('answer');
            }
        }

        TemplateRenderer::getInstance()->display('components/itilobject/timeline/form_solution.html.twig', [
            'item'    => $options['parent'] ?? null,
            'subitem' => $this,
            'params'  => $options,
        ]);

        return true;
    }

    /**
     * Count solutions for specific item
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * @return integer
     */
    public static function countFor($itemtype, $items_id)
    {
        return countElementsInTable(
            self::getTable(),
            [
                'WHERE' => [
                    'itemtype'  => $itemtype,
                    'items_id'  => $items_id
                ]
            ]
        );
    }

    public function prepareInputForAdd($input)
    {
        if (!isset($input['users_id']) && !(Session::isCron() || strpos($_SERVER['REQUEST_URI'] ?? '', 'crontask.form.php') !== false)) {
            $input['users_id'] = Session::getLoginUserID();
        }

        if (
            $this->item == null
            || (isset($input['itemtype']) && isset($input['items_id']))
        ) {
            $this->item = new $input['itemtype']();
            $this->item->getFromDB($input['items_id']);
        }

        // Handle template
        if (isset($input['_solutiontemplates_id'])) {
            $template = new SolutionTemplate();
            $parent_item = new $input['itemtype']();
            if (
                !$template->getFromDB($input['_solutiontemplates_id'])
                || !$parent_item->getFromDB($input['items_id'])
            ) {
                return false;
            }
            $input = array_replace(
                [
                    'content'           => Sanitizer::sanitize($template->getRenderedContent($parent_item)),
                    'solutiontypes_id'  => $template->fields['solutiontypes_id'],
                    'status'            => CommonITILValidation::WAITING,
                ],
                $input
            );
        }

        if (isset($input['_templates_id'])) {
            $template = new SolutionTemplate();
            $result = $template->getFromDB($input['_templates_id']);
            if (!$result) {
                return false;
            }
            $template_fields = $template->fields;
            unset($template_fields['id']);
            if (isset($template_fields['content'])) {
                $parent_item = new $input['itemtype']();
                $parent_item->getFromDB($input['items_id']);
                $template_fields['content'] = Sanitizer::sanitize($template->getRenderedContent($parent_item));
            }
            $input = array_replace($template_fields, $input);
        }

        if (!$this->item->isStatusComputationBlocked($input)) {
        // check itil object is not already solved
            if (in_array($this->item->fields["status"], $this->item->getSolvedStatusArray())) {
                Session::addMessageAfterRedirect(
                    __("The item is already solved, did anyone pushed a solution before you?"),
                    false,
                    ERROR
                );
                return false;
            }

            //default status for global solutions
            $status = CommonITILValidation::ACCEPTED;

            //handle autoclose, for tickets only
            if ($input['itemtype'] == Ticket::getType()) {
                $autoclosedelay =  Entity::getUsedConfig(
                    'autoclose_delay',
                    $this->item->getEntityID(),
                    '',
                    Entity::CONFIG_NEVER
                );

                // 0  or ticket status CLOSED = immediately
                if ($autoclosedelay != 0 && $this->item->fields["status"] != $this->item::CLOSED) {
                    $status = CommonITILValidation::WAITING;
                }
            }

            //Accepted; store user and date
            if ($status == CommonITILValidation::ACCEPTED) {
                $input['users_id_approval'] = Session::getLoginUserID();
                $input['date_approval'] = $_SESSION["glpi_currenttime"];
            }

            $input['status'] = $status;
        }

       // Render twig content, needed for massives action where we the content
       // can't be rendered directly in the form
        if (($input['_render_twig'] ?? false) && isset($input['content'])) {
            $html = TemplateManager::renderContentForCommonITIL(
                $this->item,
                $input['content']
            );

           // Invalid template
            if (!$html) {
                return false;
            }

            $input['content'] = Sanitizer::sanitize($html);
        }

        return $input;
    }

    public function post_addItem()
    {

       //adding a solution mean the ITIL object is now solved
       //and maybe closed (according to entitiy configuration)
        if ($this->item == null) {
            $this->item = new $this->fields['itemtype']();
            $this->item->getFromDB($this->fields['items_id']);
        }

        $item = $this->item;

        // Handle rich-text images and uploaded documents
        $this->input["_job"] = $this->item;
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        // Add solution to duplicates
        if ($this->item->getType() == 'Ticket' && !isset($this->input['_linked_ticket'])) {
            Ticket_Ticket::manageLinkedTicketsOnSolved($this->item->getID(), $this);
        }

        if (!isset($this->input['_linked_ticket'])) {
            $status = $item::SOLVED;

           //handle autoclose, for tickets only
            if ($item->getType() == Ticket::getType()) {
                $autoclosedelay =  Entity::getUsedConfig(
                    'autoclose_delay',
                    $this->item->getEntityID(),
                    '',
                    Entity::CONFIG_NEVER
                );

                // 0 = immediately or ticket status CLOSED force status
                if ($autoclosedelay == 0 || $this->item->fields["status"] == $this->item::CLOSED) {
                     $status = $item::CLOSED;
                }
            }

            $this->item->update([
                'id'     => $this->item->getID(),
                'status' => $status
            ]);
        }

        parent::post_addItem();
    }

    public function prepareInputForUpdate($input)
    {

        if (!isset($this->fields['itemtype'])) {
            return false;
        }
        $input["_job"] = new $this->fields['itemtype']();
        if (!$input["_job"]->getFromDB($this->fields["items_id"])) {
            return false;
        }

        if (isset($input['_update']) && ($uid = Session::getLoginUserID())) {
            $input["users_id_editor"] = $uid;
        }

        return $input;
    }

    public function post_updateItem($history = true)
    {
        // Handle rich-text images and uploaded documents
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        parent::post_updateItem($history);
    }


    /**
     * {@inheritDoc}
     * @see CommonDBTM::getSpecificValueToDisplay()
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'status':
                $value = $values[$field];
                $statuses = self::getStatuses();

                return (isset($statuses[$value]) ? $statuses[$value] : $value);
            break;
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * {@inheritDoc}
     * @see CommonDBTM::getSpecificValueToSelect()
     */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'status':
                $options['display'] = false;
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, self::getStatuses(), $options);
            break;
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Return list of statuses.
     * Key as status values, values as labels.
     *
     * @return string[]
     */
    public static function getStatuses()
    {
        return [
            CommonITILValidation::WAITING  => __('Waiting for approval'),
            CommonITILValidation::REFUSED  => _x('solution', 'Refused'),
            CommonITILValidation::ACCEPTED => __('Accepted'),
        ];
    }

    public static function getIcon()
    {
        return 'ti ti-check';
    }

    /**
     * Allow to set the parent item
     * Some subclasses will load their parent item in their `post_getFromDB` function
     * If the parent is already loaded, it might be useful to set it with this method
     * before loading the item, thus avoiding one useless DB query (or many more queries
     * when looping on children items)
     *
     * TODO 10.1 move method and `item` property into parent class
     *
     * @param CommonITILObject $parent Parent item
     *
     * @return void
     */
    final public function setParentItem(CommonITILObject $parent): void
    {
        if (static::$itemtype !== 'itemtype' && !is_a($parent, static::$itemtype)) {
            throw new LogicException("Invalid parent type");
        }

        $this->item = $parent;
    }
}
