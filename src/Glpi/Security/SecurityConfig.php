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

namespace Glpi\Security;

use CommonGLPI;
use Config;
use Glpi\Application\View\TemplateRenderer;
use Log;
use Session;

final class SecurityConfig extends Config
{
    public static string $log_itemtype = Config::class;

    public static function getTypeName($nb = 0)
    {
        return _x('setup', 'Security');
    }

    public static function getIcon()
    {
        return 'ti ti-shield-lock';
    }

    public static function getTable($classname = null)
    {
        return parent::getTable(Config::class);
    }

    public static function getMenuContent()
    {
        $menu = [];
        if (self::canView()) {
            $menu['title']   = _x('setup', 'Security');
            $menu['page']    = self::getFormURL(false);
            $menu['icon']    = self::getIcon();
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!get_class($item)) {
            return '';
        }
        if (Config::canUpdate()) {
            $tabs = [];
            $tabs[0] = self::createTabEntry(__('Password Policy'), 0, $item::class, 'ti ti-shield-lock');
            $tabs[1] = self::createTabEntry(__('Two-factor authentication (2FA)'), 0, $item::class, 'ti ti-shield-lock');
            return $tabs;
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self) {
            switch ($tabnum) {
                case 0:
                    $item->showFormPasswordPolicy();
                    break;
                case 1:
                    $item->showFormMFA();
                    break;
            }
        }
        return true;
    }

    /**
     * Password policy form
     *
     * @return void|false (display) Returns false if there is a rights error.
     */
    public function showFormPasswordPolicy()
    {
        global $CFG_GLPI;

        if (!self::canUpdate()) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/setup/security/password_policy.html.twig', [
            'canedit' => Session::haveRight(self::$rightname, UPDATE),
            'config'  => $CFG_GLPI,
            'form_path' => self::getFormURL(),
        ]);
    }

    /**
     * Password policy form
     *
     * @return void|false (display) Returns false if there is a rights error.
     */
    public function showFormMFA()
    {
        global $CFG_GLPI;

        if (!self::canUpdate()) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/setup/security/2fa.html.twig', [
            'canedit' => Session::haveRight(self::$rightname, UPDATE),
            'config'  => $CFG_GLPI,
            'form_path' => self::getFormURL(),
        ]);
    }
}
