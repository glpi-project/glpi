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

namespace Glpi\Config;

use CommonGLPI;
use Config;
use CronTask;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryFunction;
use Glpi\Event;
use Log;
use PurgeLogs;
use Session;

final class DataAndPrivacyConfig extends Config
{
    public static function getTypeName($nb = 0)
    {
        return _x('setup', 'Data and Privacy');
    }

    public static function getIcon()
    {
        return 'ti ti-file-text-shield';
    }

    public static function getTable($classname = null)
    {
        return parent::getTable(Config::class);
    }

    public static function getMenuContent()
    {
        $menu = [];
        if (self::canView()) {
            $menu['title'] = self::getTypeName();
            $menu['page'] = self::getFormURL();
            $menu['icon'] = self::getIcon();
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
            $tabs[0] = self::createTabEntry(__('Historical logs'), 0, $item::class, Event::getIcon());
            $tabs[1] = self::createTabEntry(_n('Login session', 'Login sessions', Session::getPluralNumber()), 0, $item::class, 'ti ti-user-shield');
            return $tabs;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self) {
            switch ($tabnum) {
                case 0:
                    $item->showFormLogs();
                    break;
                case 1:
                    $item->showFormSessions();
                    break;
            }
        }
        return true;
    }

    /**
     * Logs purge form
     *
     * @return void|bool (display) Returns false if there is a rights error.
     */
    private function showFormLogs()
    {
        global $CFG_GLPI;

        if (!self::canUpdate()) {
            return false;
        }

        $logspurge_crontask = new CronTask();
        $logspurge_crontask->getFromDBbyName(PurgeLogs::class, 'PurgeLogs');
        TemplateRenderer::getInstance()->display('pages/setup/general/logs_setup.html.twig', [
            'form_path' => self::getFormURL(),
            'config' => $CFG_GLPI,
            'canedit' => self::canUpdate(),
            'logspurge_crontask' => $logspurge_crontask,
        ]);
    }

    /**
     * @return false|void (display) Returns false if there is a rights error.
     */
    private function showFormSessions()
    {
        global $CFG_GLPI;

        if (!self::canUpdate()) {
            return false;
        }

        $crontask = new CronTask();
        $crontask->getFromDBbyName(self::class, 'purgesessionhistory');
        TemplateRenderer::getInstance()->display('pages/setup/data_privacy/session_retention.html.twig', [
            'form_path' => self::getFormURL(),
            'config' => $CFG_GLPI,
            'canedit' => self::canUpdate(),
            'crontask' => $crontask,
        ]);
    }

    /**
     * @param CronTask $task
     * @return int
     */
    public static function cronPurgeSessionHistory(CronTask $task): int
    {
        global $DB, $CFG_GLPI;

        $retention_days = $CFG_GLPI['login_history_retention_days'] ?? null;

        if ($retention_days === null) {
            // Safe break
            return 0;
        }

        $DB->delete('glpi_users_sessionhistories', [
            'NOT' => ['logged_out_at' => null],
            ['logged_out_at' => ['<=', QueryFunction::dateSub(QueryFunction::now(), (int) $retention_days, 'DAY')]],
        ]);
        $rows_deleted = $DB->getAffectedRows();
        $task->addVolume($rows_deleted);
        return 1;
    }
}
