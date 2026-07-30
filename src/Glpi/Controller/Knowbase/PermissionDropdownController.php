<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Controller\Knowbase;

use CommonDBTM;
use Entity;
use Entity_KnowbaseItem;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Group;
use KnowbaseItem;
use KnowbaseItem_User;
use Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use User;

final class PermissionDropdownController extends AbstractController
{
    #[Route(
        "/Knowbase/PermissionDropdown",
        name: "knowbase_permission_dropdown",
        methods: 'POST',
    )]
    public function __invoke(Request $request): Response
    {
        // Get mandatory params
        $type  = $request->request->getString('type');
        $right = $request->request->getString('right');
        if (empty($type) || empty($right)) {
            throw new BadRequestHttpException();
        }

        // Validate submitted type
        $valid_types = [User::class, Group::class, Entity::class, Profile::class];
        if (!in_array($type, $valid_types, true)) {
            throw new BadRequestHttpException();
        }

        // If the user can't update this specific KB item, he should problably
        // not be able to see this dropdown at all.
        $knowbaseitems_id = $request->request->getInt('knowbaseitems_id');
        $kb = new KnowbaseItem();
        if (!$kb->can($knowbaseitems_id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        // Compute dropdown parameters
        $dropdown_options = match ($type) {
            User::class => [
                'right'      => 'all',
                'name'       => 'users_id',
                'width'      => '100%',
                'aria_label' => User::getTypeName(1),
            ],
            Group::class => [
                'name'       => 'groups_id',
                'width'      => '100%',
                'aria_label' => Group::getTypeName(1),
            ],
            Entity::class => [
                'value'       => $_SESSION['glpiactive_entity'],
                'name'        => 'entities_id',
                'entity'      => $request->request->get('entity', -1),
                'entity_sons' => $request->request->getBoolean('is_recursive'),
                'width'       => '100%',
                'aria_label'  => Entity::getTypeName(1),
            ],
            Profile::class => [
                'name'      => 'profiles_id',
                'width'     => '100%',
                'condition' => [
                    'glpi_profilerights.name' => 'knowbase',
                    'glpi_profilerights.rights' => [
                        '&',
                        $right === 'faq'
                            ? KnowbaseItem::READFAQ
                            : (READ | CREATE | UPDATE | PURGE),
                    ],
                ],
                'aria_label' => Profile::getTypeName(1),
            ],
        };

        // Exclude targets already assigned to this article so the user cannot
        // select a duplicate (which would otherwise be rejected on submit).
        // Only done for User and Entity: their uniqueness is single-dimension
        // (one entry per user/entity). Group and Profile targets are unique on
        // (id, entities_id), so the same group/profile may legitimately be
        // added again for a different entity and cannot be excluded here.
        if ($type === User::class) {
            $dropdown_options['used'] = $this->getUsedIds(
                KnowbaseItem_User::class,
                'users_id',
                $knowbaseitems_id
            );
        } elseif ($type === Entity::class) {
            $used = $this->getUsedIds(
                Entity_KnowbaseItem::class,
                'entities_id',
                $knowbaseitems_id
            );
            $dropdown_options['used'] = $used;

            // Dropdown::show keeps the preselected value selectable even when it
            // is listed in `used`. Clear the default (active entity) when it is
            // already a target so it gets excluded like the others.
            if (in_array((int) $dropdown_options['value'], $used, true)) {
                $dropdown_options['value'] = '';
            }
        }

        return $this->render('pages/tools/kb/permission_dropdown.html.twig', [
            'type'             => $type,
            'dropdown_options' => $dropdown_options,
        ]);
    }

    /**
     * Get the ids already assigned as visibility targets for the given article.
     *
     * @param class-string<CommonDBTM> $relation_class Visibility relation class
     * @param string                   $field          Target foreign key field
     * @param int                      $knowbaseitems_id
     * @return list<int>
     */
    private function getUsedIds(
        string $relation_class,
        string $field,
        int $knowbaseitems_id
    ): array {
        /** @var \DBmysql $DB */
        global $DB;

        $used = [];
        $iterator = $DB->request([
            'SELECT' => $field,
            'FROM'   => $relation_class::getTable(),
            'WHERE'  => ['knowbaseitems_id' => $knowbaseitems_id],
        ]);
        foreach ($iterator as $row) {
            if ($row[$field] !== null) {
                $used[] = (int) $row[$field];
            }
        }

        return $used;
    }
}
