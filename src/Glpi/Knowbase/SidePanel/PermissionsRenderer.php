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

namespace Glpi\Knowbase\SidePanel;

use Dropdown;
use Entity;
use Entity_KnowbaseItem;
use Group;
use Group_KnowbaseItem;
use Html;
use KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
use Override;
use Profile;
use Session;
use User;

final class PermissionsRenderer implements RendererInterface
{
    #[Override]
    public function canView(KnowbaseItem $item): bool
    {
        return $item->can($item->getID(), READ);
    }

    #[Override]
    public function getTemplate(): string
    {
        return "pages/tools/kb/modal/permissions.html.twig";
    }

    #[Override]
    public function getParams(KnowbaseItem $item): array
    {
        $id = $item->getID();
        $can_edit = $item->canEdit($id);
        $rand = mt_rand();

        $entries = $this->buildEntries($item);

        $visiblity_dropdown_params = [
            'type'  => '__VALUE__',
            'right' => ($item->getField('is_faq') ? 'faq' : 'knowbase'),
            'allusers' => 1,
        ];
        if (isset($item->fields['entities_id'])) {
            $visiblity_dropdown_params['entity'] = $item->fields['entities_id'];
        }
        if (isset($item->fields['is_recursive'])) {
            $visiblity_dropdown_params['is_recursive'] = $item->fields['is_recursive'];
        }

        $massive_action_params = [
            'num_displayed' => count($entries),
            'container' => 'mass' . KnowbaseItem::class . $rand,
            'specific_actions' => ['delete' => _x('button', 'Delete permanently')],
        ];
        if ($item->fields['users_id'] !== Session::getLoginUserID()) {
            $massive_action_params['confirm'] = __('Caution! You are not the author of this item. Deleting targets can result in loss of access.');
        }

        return [
            'id' => $id,
            'rand' => $rand,
            'can_edit' => $can_edit,
            'entries' => $entries,
            'visiblity_dropdown_params' => $visiblity_dropdown_params,
            'massive_action_params' => $massive_action_params,
        ];
    }

    /**
     * Build entries array for the permissions datatable
     *
     * @param KnowbaseItem $item
     * @return array<int, array{itemtype: string, id: int, type: string, recipient: string}>
     */
    private function buildEntries(KnowbaseItem $item): array
    {
        $id = $item->getID();
        $entries = [];

        $users = KnowbaseItem_User::getUsers($id);
        foreach ($users as $val) {
            foreach ($val as $data) {
                $entries[] = [
                    'itemtype' => 'KnowbaseItem_User',
                    'id' => $data['id'],
                    'type' => User::getTypeName(1),
                    'recipient' => htmlescape(getUserName($data['users_id'])),
                ];
            }
        }

        $groups = Group_KnowbaseItem::getGroups($id);
        foreach ($groups as $val) {
            foreach ($val as $data) {
                $name = Dropdown::getDropdownName('glpi_groups', $data['groups_id']);
                $tooltip = Dropdown::getDropdownComments('glpi_groups', (int) $data['groups_id']);
                $recipient = sprintf(
                    __s('%1$s %2$s'),
                    htmlescape($name),
                    Html::showToolTip($tooltip, ['display' => false])
                );
                if ($data['entities_id'] !== null) {
                    $recipient = sprintf(
                        __s('%1$s / %2$s'),
                        $recipient,
                        htmlescape(
                            Dropdown::getDropdownName(
                                'glpi_entities',
                                $data['entities_id']
                            )
                        )
                    );
                    if ($data['is_recursive']) {
                        $recipient = sprintf(
                            __s('%1$s %2$s'),
                            $recipient,
                            "<span class='fw-bold'>(" . __s('R') . ")</span>"
                        );
                    }
                }
                $entries[] = [
                    'itemtype' => 'Group_KnowbaseItem',
                    'id' => $data['id'],
                    'type' => Group::getTypeName(1),
                    'recipient' => $recipient,
                ];
            }
        }

        $entities = Entity_KnowbaseItem::getEntities($id);
        foreach ($entities as $val) {
            foreach ($val as $data) {
                $name = Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
                $tooltip = Dropdown::getDropdownComments('glpi_entities', (int) $data['entities_id']);
                $recipient = sprintf(
                    __s('%1$s %2$s'),
                    htmlescape($name),
                    Html::showToolTip($tooltip, ['display' => false])
                );
                if ($data['is_recursive']) {
                    $recipient = sprintf(
                        __s('%1$s %2$s'),
                        $recipient,
                        "<span class='fw-bold'>(" . __s('R') . ")</span>"
                    );
                }
                $entries[] = [
                    'itemtype' => 'Entity_KnowbaseItem',
                    'id' => $data['id'],
                    'type' => Entity::getTypeName(1),
                    'recipient' => $recipient,
                ];
            }
        }

        $profiles = KnowbaseItem_Profile::getProfiles($id);
        foreach ($profiles as $val) {
            foreach ($val as $data) {
                $name = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id']);
                $tooltip = Dropdown::getDropdownComments('glpi_profiles', (int) $data['profiles_id']);
                $recipient = sprintf(
                    __s('%1$s %2$s'),
                    htmlescape($name),
                    Html::showToolTip($tooltip, ['display' => false])
                );
                if ($data['entities_id'] !== null) {
                    $recipient = sprintf(
                        __s('%1$s / %2$s'),
                        $recipient,
                        htmlescape(
                            Dropdown::getDropdownName(
                                'glpi_entities',
                                $data['entities_id']
                            )
                        )
                    );
                    if ($data['is_recursive']) {
                        $recipient = sprintf(
                            __s('%1$s %2$s'),
                            $recipient,
                            "<span class='fw-bold'>(" . __s('R') . ")</span>"
                        );
                    }
                }
                $entries[] = [
                    'itemtype' => 'KnowbaseItem_Profile',
                    'id' => $data['id'],
                    'type' => Profile::getTypeName(1),
                    'recipient' => $recipient,
                ];
            }
        }

        return $entries;
    }
}
