<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Helpdesk;

use CommonGLPI;
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Grid;
use Override;
use Reminder;
use RSSFeed;
use Search;
use Session;
use Ticket;

final class HomePageTabs extends CommonGLPI
{
    private const ONGOING_TICKETS_TAB = 1;
    private const SOLVED_TICKETS_TAB = 2;
    private const PUBLIC_REMINDER_TAB = 3;
    private const RSS_FEED_PUBLIC = 4;
    private const DASHBOARD_TAB = 5;

    #[Override]
    public function defineTabs($options = []): array
    {
        $tabs = [];
        $this->addStandardTab(self::class, $tabs, $options);
        $tabs['no_all_tab'] = true;

        return $tabs;
    }

    #[Override]
    public function getTabNameForItem(
        CommonGLPI $item,
        $withtemplate = 0
    ): array|string {
        // Only available on self
        if (!($item instanceof self)) {
            return '';
        }

        $tabs = [
            self::ONGOING_TICKETS_TAB => self::createTabEntry(
                text: __('Ongoing tickets'),
                icon: Ticket::getIcon()
            ),
            self::SOLVED_TICKETS_TAB  => self::createTabEntry(
                text: __('Solved tickets'),
                icon: 'ti ti-check'
            ),
        ];

        if (
            Session::haveRight("reminder_public", READ)
            && Reminder::countPublicReminders() > 0
        ) {
            $tabs[self::PUBLIC_REMINDER_TAB] = self::createTabEntry(
                text: Reminder::getTypeName(),
                icon: Reminder::getIcon()
            );
        }

        if (
            Session::haveRight("rssfeed_public", READ)
            && RSSFeed::countPublicRssFedds() > 0
        ) {
            $tabs[self::RSS_FEED_PUBLIC] = self::createTabEntry(
                text: RSSFeed::getTypeName(),
                icon: RSSFeed::getIcon()
            );
        }

        if (Grid::canViewOneDashboard()) {
            $tabs[self::DASHBOARD_TAB] = self::createTabEntry(
                text: __("Dashboard"),
                icon: Dashboard::getIcon()
            );
        }

        return $tabs;
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ): bool {
        if (!($item instanceof self)) {
            return false;
        }

        $tabs = new self();

        if ($tabnum == self::ONGOING_TICKETS_TAB) {
            $tabs->displayOngoingTicketsTabs();
            return true;
        }

        if ($tabnum == self::SOLVED_TICKETS_TAB) {
            $tabs->displaySolvedTicketsTabs();
            return true;
        }

        if ($tabnum == self::PUBLIC_REMINDER_TAB) {
            if (!Session::haveRight("reminder_public", READ)) {
                return false;
            }

            // TODO: improve display
            echo Reminder::showListForCentral(false, false);
            return true;
        }

        if ($tabnum == self::RSS_FEED_PUBLIC) {
            if (!Session::haveRight("rssfeed_public", READ)) {
                return false;
            }

            // TODO: improve display
            echo RSSFeed::showListForCentral(false, false);
            return true;
        }

        if ($tabnum == self::DASHBOARD_TAB) {
            if (!Grid::canViewOneDashboard()) {
                return false;
            }

            $default   = Grid::getDefaultDashboardForMenu('central');
            $dashboard = new Grid($default);
            $dashboard->show();
            return true;
        }

        return false;
    }

    private function displayOngoingTicketsTabs(): void
    {
        $this->showTicketList([
            [
                'link'       => 'AND',
                'field'      => 12,
                'searchtype' => 'equals',
                'value'      => 'notold',
            ],
        ]);
    }

    private function displaySolvedTicketsTabs(): void
    {
        $this->showTicketList([
            [
                'link'       => 'AND',
                'field'      => 12,
                'searchtype' => 'equals',
                'value'      => 'old',
            ],
        ]);
    }

    private function showTicketList(array $criteria): void
    {
        echo '<div class="home-ticket-list">';
        Search::showList(Ticket::class, [
            'criteria'           => $criteria,
            'showmassiveactions' => false,
            'hide_controls'      => true,
            'as_map'             => false,
            'push_history'       => false,
            'sort'               => [19],
            'order'              => ['DESC'],
        ]);
        echo '</div>';
    }
}
