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

use function Safe\preg_match;

abstract class RuleCommonITILObjectCollection extends RuleCollection
{
    // From RuleCollection
    public $use_output_rule_process_as_next_input   = true;

    /**
     * @param $entity (default 0)
     **/
    public function __construct($entity = 0)
    {
        parent::__construct();
        $this->entity = $entity;
    }

    /**
     * Get the ITIL Object itemtype that this rule collection is for
     * @return class-string<CommonITILObject> "Ticket", "Change" or "Problem"
     */
    public static function getItemtype(): string
    {
        // Return text between Rule and Collection is the current class name
        $matches = [];
        preg_match('/^Rule(.*)Collection$/', static::class, $matches);
        return $matches[1];
    }

    public static function canView(): bool
    {
        $rule_class = static::getRuleClassName();
        return Session::haveRightsOr(static::$rightname, [READ, $rule_class::PARENT]);
    }

    public function canList()
    {
        return static::canView();
    }

    public function preProcessPreviewResults($output)
    {
        $output = parent::preProcessPreviewResults($output);
        $itemtype = static::getItemtype();
        return $itemtype::showPreviewAssignAction($output);
    }

    public function showInheritedTab()
    {
        $rule_class = static::getRuleClassName();
        return (Session::haveRight(self::$rightname, $rule_class::PARENT) && ($this->entity));
    }

    public function showChildrensTab()
    {
        return (Session::haveRight(self::$rightname, READ)
            && (count($_SESSION['glpiactiveentities']) > 1));
    }

    public function prepareInputDataForProcess($input, $params)
    {
        $input['_groups_id_of_requester'] = [];
        // Get groups of users
        if (isset($input['_users_id_requester'])) {
            if (!is_array($input['_users_id_requester'])) {
                $requesters = [$input['_users_id_requester']];
            } else {
                $requesters = $input['_users_id_requester'];
            }
            foreach ($requesters as $uid) {
                foreach (Group_User::getUserGroups($uid) as $g) {
                    $input['_groups_id_of_requester'][$g['id']] = $g['id'];
                }
            }
        }

        // Required for rules on category code triggered by others rules
        if (isset($input['itilcategories_id']) && $input['itilcategories_id']) {
            $itilcategory = ITILCategory::getById($input['itilcategories_id']);
            if ($itilcategory) {
                $input['itilcategories_id_code'] = $itilcategory->fields['code'];
            }
        }

        return $input;
    }
}
