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

use Glpi\Security\TOTPManager;

// class Preference for the current connected User
class Preference extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
       // Always plural
        return __('Settings');
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab('User', $ong, $options);
        $this->addStandardTab(__CLASS__, $ong, $options);
        if (Session::haveRightsOr('personalization', [READ, UPDATE])) {
            $this->addStandardTab('Config', $ong, $options);
        }
        $this->addStandardTab('ValidatorSubstitute', $ong, $options);
        $this->addStandardTab('DisplayPreference', $ong, $options);

        $ong['no_all_tab'] = true;

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return __('Two-factor authentication (2FA)');
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $totp = new TOTPManager();
        $totp->showTOTPConfigForm($_SESSION['glpiID'], isset($_REQUEST['reset_2fa']));
    }

    public function showTabsContent($options = [])
    {
        if (isset($_REQUEST['reset_2fa'])) {
            $options['reset_2fa'] = $_REQUEST['reset_2fa'];
        }
        parent::showTabsContent($options);
    }
}
