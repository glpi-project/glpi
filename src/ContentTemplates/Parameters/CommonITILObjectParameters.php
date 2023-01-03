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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use Entity;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ArrayParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ObjectParameter;
use Glpi\Toolbox\Sanitizer;
use Group;
use ITILCategory;
use Session;
use Supplier;
use User;

/**
 * Parameters for "CommonITILObject" items.
 *
 * @since 10.0.0
 */
abstract class CommonITILObjectParameters extends AbstractParameters
{
    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
            new AttributeParameter("ref", __("Reference (# + id)")),
            new AttributeParameter("link", _n('Link', 'Links', 1), "raw"),
            new AttributeParameter("name", __('Title')),
            new AttributeParameter("content", __('Description'), "raw"),
            new AttributeParameter("date", __('Opening date'), 'date("d/m/y H:i")'),
            new AttributeParameter("solvedate", __('Resolution date'), 'date("d/m/y H:i")'),
            new AttributeParameter("closedate", __('Closing date'), 'date("d/m/y H:i")'),
            new AttributeParameter("status", __('Status')),
            new AttributeParameter("urgency", __('Urgency')),
            new AttributeParameter("impact", __('Impact')),
            new AttributeParameter("priority", __('Priority')),
            new ObjectParameter(new EntityParameters()),
            new ObjectParameter(new ITILCategoryParameters()),
            new ArrayParameter("requesters.users", new UserParameters(), _n('Requester', 'Requesters', Session::getPluralNumber())),
            new ArrayParameter("observers.users", new UserParameters(), _n('Watcher', 'Watchers', Session::getPluralNumber())),
            new ArrayParameter("assignees.users", new UserParameters(), _n('Assignee', 'Assignees', Session::getPluralNumber())),
            new ArrayParameter("requesters.groups", new GroupParameters(), _n('Requester group', 'Requester groups', Session::getPluralNumber())),
            new ArrayParameter("observers.groups", new GroupParameters(), _n('Watcher group', 'Watcher groups', Session::getPluralNumber())),
            new ArrayParameter("assignees.groups", new GroupParameters(), _n('Assigned group', 'Assigned groups', Session::getPluralNumber())),
            new ArrayParameter("assignees.suppliers", new SupplierParameters(), _n('Assigned supplier', 'Assigned suppliers', Session::getPluralNumber())),
        ];
    }

    protected function defineValues(CommonDBTM $commonitil): array
    {
        /** @var CommonITILObject $commonitil  */

       // Output "unsanitized" values
        $fields = Sanitizer::unsanitize($commonitil->fields);

       // Base values from ticket property
        $values = [
            'id'        => $fields['id'],
            'ref'       => "#" . $fields['id'],
            'link'      => $commonitil->getLink(),
            'name'      => $fields['name'],
            'content'   => $fields['content'],
            'date'      => $fields['date'],
            'solvedate' => $fields['solvedate'],
            'closedate' => $fields['closedate'],
            'status'    => $commonitil::getStatus($fields['status']),
            'urgency'   => $commonitil::getUrgencyName($fields['urgency']),
            'impact'    => $commonitil::getImpactName($fields['impact']),
            'priority'  => $commonitil::getPriorityName($fields['priority']),
        ];

       // Add ticket's entity
        if ($entity = Entity::getById($fields['entities_id'])) {
            $entity_parameters = new EntityParameters();
            $values['entity'] = $entity_parameters->getValues($entity);
        }

       // Add ticket's category
        if ($itilcategory = ITILCategory::getById($fields['itilcategories_id'])) {
            $itilcategory_parameters = new ITILCategoryParameters();
            $values['itilcategory'] = $itilcategory_parameters->getValues($itilcategory);
        }

       // Add requesters / observers / assigned data
        $commonitil->loadActors();

        $values['requesters'] = [
            'users'  => [],
            'groups' => [],
        ];
        $values['observers'] = [
            'users'  => [],
            'groups' => [],
        ];
        $values['assignees'] = [
            'users'     => [],
            'groups'    => [],
            'suppliers' => [],
        ];

        $user_parameters = new UserParameters();
        $users_to_add = [
            'requesters' => CommonITILActor::REQUESTER,
            'observers'  => CommonITILActor::OBSERVER,
            'assignees'  => CommonITILActor::ASSIGN,
        ];
        foreach ($users_to_add as $key => $type) {
            foreach ($commonitil->getUsers($type) as $data) {
                if ($user = User::getById($data['users_id'])) {
                    $values[$key]['users'][] = $user_parameters->getValues($user);
                }
            }
        }

        $group_parameters = new GroupParameters();
        $groups_to_add = [
            'requesters' => CommonITILActor::REQUESTER,
            'observers'  => CommonITILActor::OBSERVER,
            'assignees'  => CommonITILActor::ASSIGN,
        ];
        foreach ($groups_to_add as $key => $type) {
            foreach ($commonitil->getGroups($type) as $data) {
                if ($group = Group::getById($data['groups_id'])) {
                    $values[$key]['groups'][] = $group_parameters->getValues($group);
                }
            }
        }

        $supplier_parameters = new SupplierParameters();
        foreach ($commonitil->getSuppliers(CommonITILActor::ASSIGN) as $data) {
            if ($supplier = Supplier::getById($data['suppliers_id'])) {
                $values['assignees']['suppliers'][] = $supplier_parameters->getValues($supplier);
            }
        }

        return $values;
    }
}
