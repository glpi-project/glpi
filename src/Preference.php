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
        $this->addStandardTab(User::class, $ong, $options);
        $this->addStandardTab(self::class, $ong, $options);
        if (Session::haveRightsOr('personalization', [READ, UPDATE])) {
            $this->addStandardTab(Config::class, $ong, $options);
        }
        $this->addStandardTab(ValidatorSubstitute::class, $ong, $options);
        $this->addStandardTab(DisplayPreference::class, $ong, $options);

        $ong['no_all_tab'] = true;

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(text: __('Two-factor authentication (2FA)'), icon: 'ti ti-shield-lock');
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $totp = new TOTPManager();
        $regenerate_backup_codes = isset($_REQUEST['regenerate_backup_codes']) ? filter_var($_REQUEST['regenerate_backup_codes'], FILTER_VALIDATE_BOOLEAN) : false;
        // Don't allow regenerating the codes from the URL if the user already has some to prevent malicious or accidental regenerations
        $regenerate_backup_codes = $regenerate_backup_codes && $totp->is2FAEnabled($_SESSION['glpiID']) && !$totp->isBackupCodesAvailable($_SESSION['glpiID']);
        $totp->showTOTPConfigForm($_SESSION['glpiID'], isset($_REQUEST['reset_2fa']), $regenerate_backup_codes);
        return true;
    }

    /**
     * @FIXME Override the options inside the front controller.
     * @phpstan-ignore method.parentMethodFinalByPhpDoc (temporary solution to add the final tag)
     */
    public function showTabsContent($options = [])
    {
        if (isset($_REQUEST['reset_2fa'])) {
            $options['reset_2fa'] = $_REQUEST['reset_2fa'];
        }
        if (isset($_REQUEST['regenerate_backup_codes'])) {
            $options['regenerate_backup_codes'] = $_REQUEST['regenerate_backup_codes'];
        }
        parent::showTabsContent($options);
    }
}
