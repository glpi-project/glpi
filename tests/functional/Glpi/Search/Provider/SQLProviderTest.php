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

namespace tests\units\Glpi\Search\Provider;

use Certificate;
use Domain;
use Glpi\Search\Provider\SQLProvider;
use Glpi\Tests\DbTestCase;
use Html;
use Search;

class SQLProviderTest extends DbTestCase
{
    public function testGetLeftJoinCriteria()
    {
        global $DB;

        $already_linked = [];
        $item_item_join = SQLProvider::getLeftJoinCriteria(
            'Ticket',
            'glpi_tickets',
            $already_linked,
            'glpi_tickets_tickets',
            'tickets_tickets_id',
            false,
            0,
            ['jointype' => 'item_item'],
            'tickets_id_1'
        );
        $it = new \DBmysqlIterator($DB);
        $this->assertEquals(
            ' LEFT JOIN `glpi_tickets_tickets` ON (`glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_1` OR `glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_2`)',
            $it->analyseJoins($item_item_join)
        );

        $item_item_revert_join = SQLProvider::getLeftJoinCriteria(
            'Ticket_Ticket',
            'glpi_tickets_tickets',
            $already_linked,
            'glpi_tickets',
            'tickets_id',
            false,
            0,
            ['jointype' => 'item_item_revert'],
            'tickets_id'
        );
        $this->assertEquals(
            ' LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_1` OR `glpi_tickets`.`id` = `glpi_tickets_tickets`.`tickets_id_2`)',
            $it->analyseJoins($item_item_revert_join)
        );
    }

    /**
     * Regression test to validate that the expiration date badge shown for
     * Certificate items in search results respects the user's configured
     * date format instead of always displaying the raw database value.
     *
     * @see https://github.com/glpi-project/glpi/pull/24866
     */
    public function testCertificateExpirationDateBadgeRespectsDateFormat()
    {
        $this->login();

        $expiration_date = date('Y-m-d', strtotime('-10 days'));

        $this->createItem(Certificate::class, [
            'name'            => __FUNCTION__,
            'entities_id'     => $this->getTestRootEntity(true),
            'date_expiration' => $expiration_date,
        ]);

        $_SESSION['glpidate_format'] = 1; // d-m-Y

        $result = Search::getDatas(
            Certificate::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => __FUNCTION__,
                    ],
                ],
            ],
            [10] // date_expiration
        );

        $this->assertTrue(isset($result['data']['rows'][0]['Certificate_10']['displayname']));
        $displayname = $result['data']['rows'][0]['Certificate_10']['displayname'];

        $this->assertStringContainsString(Html::convDate($expiration_date), $displayname);
        $this->assertStringNotContainsString($expiration_date, $displayname);
    }

    /**
     * Regression test to validate that the expiration date badge shown for
     * Domain items in search results respects the user's configured date
     * format instead of always displaying the raw database value.
     *
     * @see https://github.com/glpi-project/glpi/pull/24866
     */
    public function testDomainExpirationDateBadgeRespectsDateFormat()
    {
        $this->login();

        $expiration_date = date('Y-m-d H:i:s', strtotime('-10 days'));

        $this->createItem(Domain::class, [
            'name'            => __FUNCTION__,
            'entities_id'     => $this->getTestRootEntity(true),
            'date_expiration' => $expiration_date,
        ]);

        $_SESSION['glpidate_format'] = 1; // d-m-Y

        $result = Search::getDatas(
            Domain::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => __FUNCTION__,
                    ],
                ],
            ],
            [6] // date_expiration
        );

        $this->assertTrue(isset($result['data']['rows'][0]['Domain_6']['displayname']));
        $displayname = $result['data']['rows'][0]['Domain_6']['displayname'];

        $this->assertStringContainsString(Html::convDate($expiration_date), $displayname);
        $this->assertStringNotContainsString($expiration_date, $displayname);
    }
}
