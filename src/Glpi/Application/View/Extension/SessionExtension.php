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

namespace Glpi\Application\View\Extension;

use CommonDBTM;
use CommonGLPI;
use Exception;
use Profile_User;
use Session;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use User;

/**
 * @since 10.0.0
 */
class SessionExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_view_all_entities', [Session::class, 'canViewAllEntities']),
            new TwigFunction('get_current_interface', [$this, 'getCurrentInterface']),
            new TwigFunction('get_current_user', [$this, 'getCurrentUser']),
            new TwigFunction('has_access_to_entity', [Session::class, 'haveAccessToEntity']),
            new TwigFunction('has_access_to_user_entities', [$this, 'hasAccessToUserEntities']),
            new TwigFunction('has_profile_right', [Session::class, 'haveRight']),
            new TwigFunction('has_itemtype_right', [$this, 'hasItemtypeRight']),
            new TwigFunction('is_multi_entities_mode', [Session::class, 'isMultiEntitiesMode']),
            new TwigFunction('pull_messages', [$this, 'pullMessages']),
            new TwigFunction('session', [$this, 'session']),
            new TwigFunction('user_pref', [$this, 'userPref']),
            new TwigFunction('get_saved_option', [Session::class, 'getSavedOption']),
            new TwigFunction('get_page_layout', [$this, 'getPageLayout']),
        ];
    }

    /**
     * Returns current interface.
     *
     */
    public function getCurrentInterface(): ?string
    {
        return $_SESSION['glpiactiveprofile']['interface'] ?? null;
    }

    /**
     * Returns current connected user.
     *
     */
    public function getCurrentUser(): ?User
    {
        if (($user = User::getById(Session::getLoginUserID())) instanceof User) {
            return $user;
        }
        return null;
    }

    /**
     * Check global right on itemtype.
     *
     *
     */
    public function hasItemtypeRight(string $itemtype, int $right): bool
    {
        if (!is_a($itemtype, CommonGLPI::class, true)) {
            throw new Exception(sprintf('Unable to check rights of itemtype "%s".', $itemtype));
        }

        /** @var CommonDBTM $item */
        $item = new $itemtype();
        return $item->canGlobal($right);
    }

    /**
     * Get user preference.
     *
     *
     * @return null|mixed
     */
    public function userPref(string $name, bool $decode = false)
    {
        global $CFG_GLPI;

        $data = $_SESSION['glpi' . $name] ?? $CFG_GLPI[$name] ?? null;
        if ($decode) {
            $data = importArrayFromDB($data);
        }

        return $data;
    }

    /**
     * Get session value.
     *
     *
     * @return mixed
     */
    public function session(string $name)
    {

        return $_SESSION[$name] ?? null;
    }

    /**
     * Check if a current user have access has access to given user entities.
     *
     *
     */
    public function hasAccessToUserEntities(int $users_id): bool
    {
        return Session::haveAccessToOneOfEntities(Profile_User::getUserEntities($users_id, false));
    }


    /**
     * Return MESSAGE_AFTER_REDIRECT session var and clear it.
     *
     * @return string[]
     */
    public function pullMessages(): array
    {
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'] ?? [];
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        return $messages;
    }

    /**
     * Get the page layout ('vertical' or 'horizontal').
     *
     * Helpdesk interface is always horizontal.
     * Central interface depends on the user preferences.
     *
     */
    public function getPageLayout(): string
    {
        // Helpdesk interface is always horizontal
        if ($this->getCurrentInterface() == 'helpdesk') {
            return 'horizontal';
        }

        return $this->userPref('page_layout');
    }
}
