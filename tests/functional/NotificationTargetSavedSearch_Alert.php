<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;

/* Test for inc/notificationtargetuser.class.php */

class NotificationTargetSavedSearch_Alert extends DbTestCase
{
    public function testAddDataForTemplate()
    {
        $this->login();
        // Create a saved search
        $saved_search = new \SavedSearch();
        $saved_searches_id = $saved_search->add([
            'name' => __FUNCTION__,
            'type' => \SavedSearch::SEARCH,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'users_id' => \Session::getLoginUserID(),
            'itemtype' => 'Computer',
            'url' => 'http://glpi.localhost/front/computer.php?is_deleted=0&as_map=0&browse=0&criteria%5B0%5D%5Blink%5D=AND&criteria%5B0%5D%5Bfield%5D=view&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=test&itemtype=Computer&start=0&_glpi_csrf_token=735e344f1f47545e5bea56aa4e75c15ca45d3628307937c3bf185e0a3bca39db&sort%5B%5D=1&order%5B%5D=ASC'
        ]);
        $this->integer($saved_searches_id)->isGreaterThan(0);

        // Create saved search alert
        $saved_search_alert = new \SavedSearch_Alert();
        $saved_search_alerts_id = $saved_search_alert->add([
            'savedsearches_id' => $saved_searches_id,
            'name' => __FUNCTION__,
            'is_active' => 1,
            'operator' => \SavedSearch_Alert::OP_GREATEQ,
            'value' => '5',
            'frequency' => DAY_TIMESTAMP,
        ]);
        $this->integer($saved_search_alerts_id)->isGreaterThan(0);

        // Create a notification target
        $target = new \NotificationTargetSavedSearch_Alert(
            getItemByTypeName('Entity', '_test_root_entity', true),
            'alert',
            $saved_search_alert
        );
        $target->addDataForTemplate('alert', [
            'item' => $saved_search_alert,
            'savedsearch' => $saved_search,
            'msg' => 'test',
            'data' => [
                'totalcount' => 10,
            ],
            'additionnaloption' => [
                'usertype' => \NotificationTarget::GLPI_USER
            ]
        ]);

        // Host may change so only check the end of the URL
        global $CFG_GLPI;
        $expected_redirect = '%2Ffront%2Fsavedsearch.php%3Faction%3Dload%26id%3D' . $saved_searches_id;
        $this->string($target->data['##savedsearch.url##'])
            ->isEqualTo($CFG_GLPI['url_base'] . '/index.php?redirect=' . $expected_redirect . '&noAUTO=1');
    }
}
