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

namespace tests\units;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/software.class.php */

class DomainTest extends DbTestCase
{
    public function testTypeName()
    {
        $this->assertSame('Domain', \Domain::getTypeName(1));
        $this->assertSame('Domains', \Domain::getTypeName(0));
        $this->assertSame('Domains', \Domain::getTypeName(10));
    }

    public function testPrepareInput()
    {
        $domain = new \Domain();
        $expected = ['date_creation' => 'NULL', 'date_expiration' => null];
        $result   = $domain->prepareInputForUpdate(['date_creation' => '', 'date_expiration' => null]);
        $this->assertSame($expected, $result);
        $result   = $domain->prepareInputForAdd(['date_creation' => '', 'date_expiration' => null]);
        $this->assertSame($expected, $result);
    }

    public function testCleanDBonPurge()
    {
        $this->login();

        $domain = new \Domain();
        $domains_id = (int) $domain->add([
            'name'   => 'glpi-project.org',
        ]);
        $this->assertGreaterThan(0, $domains_id);

        $domain_item = new \Domain_Item();
        $this->assertGreaterThan(
            0,
            $domain_item->add([
                'domains_id'   => $domains_id,
                'itemtype'     => 'Computer',
                'items_id'     => getItemByTypeName('Computer', '_test_pc01', true),
            ])
        );

        $record = new \DomainRecord();
        foreach (['www', 'ftp', 'mail'] as $sub) {
            $this->assertGreaterThan(
                0,
                $record->add([
                    'name'         => $sub,
                    'data'         => 'glpi-project.org.',
                    'domains_id'   => $domains_id,
                ])
            );
        }

        $this->assertSame(1, countElementsInTable($domain_item->getTable(), ['domains_id' => $domains_id]));
        $this->assertSame(3, countElementsInTable($record->getTable(), ['domains_id' => $domains_id]));
        $this->assertTrue($domain->delete(['id' => $domains_id], true));
        $this->assertSame(0, countElementsInTable($domain_item->getTable(), ['domains_id' => $domains_id]));
        $this->assertSame(0, countElementsInTable($record->getTable(), ['domains_id' => $domains_id]));
    }

    public function testGetEntitiesToNotify()
    {
        global $DB;
        $this->login();

        $this->assertEmpty(\Entity::getEntitiesToNotify('use_domains_alert'));

        $entity = getItemByTypeName('Entity', '_test_root_entity');
        $this->assertTrue(
            $entity->update([
                'id'                                      => $entity->fields['id'],
                'use_domains_alert'                       => 1,
                'send_domains_alert_close_expiries_delay' => 7,
                'send_domains_alert_expired_delay'        => 1,
            ])
        );
        $this->assertTrue($entity->getFromDB($entity->fields['id']));

        $this->assertSame(
            [
                getItemByTypeName('Entity', '_test_root_entity', true)   => 1,
                getItemByTypeName('Entity', '_test_child_1', true)       => 1,
                getItemByTypeName('Entity', '_test_child_2', true)       => 1,
                getItemByTypeName('Entity', '_test_child_3', true)       => 1,
            ],
            \Entity::getEntitiesToNotify('use_domains_alert')
        );

        $iterator = $DB->request(\Domain::expiredDomainsCriteria($entity->fields['id']));
        $this->assertSame(
            "SELECT * FROM `glpi_domains` WHERE "
            . "NOT (`date_expiration` IS NULL) AND `entities_id` = '{$entity->fields['id']}' AND `is_deleted` = '0' "
            . "AND DATEDIFF(CURDATE(), `date_expiration`) > 1 AND DATEDIFF(CURDATE(), `date_expiration`) > 0",
            $iterator->getSql()
        );

        $iterator = $DB->request(\Domain::closeExpiriesDomainsCriteria($entity->fields['id']));
        $this->assertSame(
            "SELECT * FROM `glpi_domains` WHERE "
            . "NOT (`date_expiration` IS NULL) AND `entities_id` = '{$entity->fields['id']}' AND `is_deleted` = '0' "
            . "AND DATEDIFF(CURDATE(), `date_expiration`) > -7 AND DATEDIFF(CURDATE(), `date_expiration`) < 0",
            $iterator->getSql()
        );
    }

    public function testTransfer()
    {
        $this->login();
        $domain = new \Domain();
        $domains_id = (int) $domain->add([
            'name'   => 'glpi-project.org',
        ]);
        $this->assertGreaterThan(0, $domains_id);

        $record = new \DomainRecord();
        foreach (['www', 'ftp', 'mail'] as $sub) {
            $this->assertGreaterThan(
                0,
                $record->add([
                    'name'         => $sub,
                    'data'         => 'glpi-project.org.',
                    'domains_id'   => $domains_id,
                ])
            );
        }

        $entities_id = getItemByTypeName('Entity', '_test_child_2', true);

        //transfer to another entity
        $transfer = new \Transfer();

        $ma = $this->getMockBuilder(\MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();

        \MassiveAction::processMassiveActionsForOneItemtype(
            $ma,
            $domain,
            [$domains_id]
        );
        $transfer->moveItems(['Domain' => [$domains_id]], $entities_id, [$domains_id]);
        unset($_SESSION['glpitransfer_list']);

        $this->assertTrue($domain->getFromDB($domains_id));
        $this->assertSame($entities_id, (int) $domain->fields['entities_id']);

        global $DB;
        $records = $DB->request([
            'FROM'   => \DomainRecord::getTable(),
            'WHERE'  => ['domains_id' => $domains_id],
        ]);
        foreach ($records as $row) {
            $this->assertSame($entities_id, (int) $row['entities_id']);
        }
    }

    public function testCronDomainsAlert()
    {
        $this->login();

        // Force usage of notifications
        global $CFG_GLPI;
        $CFG_GLPI['use_notifications'] = true;

        $alert    = new \Alert();
        $domain   = new \Domain();
        $crontask = new \CronTask();
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // Add domains
        $domain_1_id = $domain->add([
            'name'            => $this->getUniqueString(),
            'entities_id'     => $entities_id,
            'date_expiration' => date('Y-m-d', time() - MONTH_TIMESTAMP), // expired for a long time (> 7 days)
        ]);
        $this->assertGreaterThan(0, $domain_1_id);

        $domain_2_id = $domain->add([
            'name'            => $this->getUniqueString(),
            'entities_id'     => $entities_id,
            'date_expiration' => date('Y-m-d', time() - DAY_TIMESTAMP), // expired recently (< 7 days)
        ]);
        $this->assertGreaterThan(0, $domain_2_id);

        $domain_3_id = $domain->add([
            'name'            => $this->getUniqueString(),
            'entities_id'     => $entities_id,
            'date_expiration' => date('Y-m-d', time() + DAY_TIMESTAMP), // will expire soon (< 7 days)
        ]);
        $this->assertGreaterThan(0, $domain_3_id);

        $domain_4_id = $domain->add([
            'name'            => $this->getUniqueString(),
            'entities_id'     => $entities_id,
            'date_expiration' => date('Y-m-d', time() + MONTH_TIMESTAMP), // will expire in a long time (> 7 days)
        ]);
        $this->assertGreaterThan(0, $domain_4_id);

        $domain_5_id = $domain->add([
            'name'            => $this->getUniqueString(),
            'entities_id'     => $entities_id,
            'date_expiration' => null, // does not expire
        ]);
        $this->assertGreaterThan(0, $domain_5_id);

        // Set root entity config domains alerts
        $entity = new \Entity();
        $updated = $entity->update([
            'id'                                      => $entities_id,
            'use_domains_alert'                       => 1,
            'send_domains_alert_close_expiries_delay' => 7, // alert on domains that will expire in less than 7 days
            'send_domains_alert_expired_delay'        => 7, // alert on domains expired since, at least, 7 days
        ]);
        $this->assertTrue($updated);

        $result = $domain->cronDomainsAlert($crontask);
        $this->assertEquals(1, $result); // 1 = fully processed

        $this->assertEquals(2, countElementsInTable(\Alert::getTable()));

        $expired_alerts = $alert->find(
            [
                'itemtype' => 'Domain',
                'type'     => \Alert::END,
            ]
        );
        $expired_alert = reset($expired_alerts);
        unset($expired_alert['id']);
        $this->assertEquals(
            [
                'date'     => $_SESSION["glpi_currenttime"],
                'itemtype' => 'Domain',
                'items_id' => $domain_1_id,
                'type'     => \Alert::END,
            ],
            $expired_alert
        );

        $expiring_alerts = $alert->find(
            [
                'itemtype' => 'Domain',
                'type'     => \Alert::NOTICE,
            ]
        );
        $expiring_alert = reset($expiring_alerts);

        unset($expiring_alert['id']);
        $this->assertEquals(
            [
                'date'     => $_SESSION["glpi_currenttime"],
                'itemtype' => 'Domain',
                'items_id' => $domain_3_id,
                'type'     => \Alert::NOTICE,
            ],
            $expiring_alert
        );

        $result = $domain->cronDomainsAlert($crontask);
        $this->assertEquals(0, $result); // 0 = nothing to do (alerts were already sent)
    }

    public static function linkContentProvider(): iterable
    {
        // Empty link
        yield [
            'link'     => '',
            'safe_url' => false,
            'expected' => [''],
        ];
        yield [
            'link'     => '',
            'safe_url' => true,
            'expected' => ['#'],
        ];

        foreach ([true, false] as $safe_url) {
            yield [
                'link'     => 'https://{{ LOGIN }}@{{ DOMAIN }}/',
                'safe_url' => $safe_url,
                'expected' => ['https://_test_user@domain.tld/'],
            ];
        }

        // Javascript link
        yield [
            'link'     => 'javascript:alert(1);" title="{{ DOMAIN }}"',
            'safe_url' => false,
            'expected' => ['javascript:alert(1);" title="domain.tld"'],
        ];
        yield [
            'link'     => 'javascript:alert(1);" title="{{ DOMAIN }}"',
            'safe_url' => true,
            'expected' => ['#'],
        ];
    }

    #[DataProvider('linkContentProvider')]
    public function testGenerateLinkContents(
        string $link,
        bool $safe_url,
        array $expected
    ): void {
        $this->login();

        // Create computer
        $item = $this->createItem(
            \Domain::class,
            [
                'name'         => 'domain.tld',
                'entities_id'  => $this->getTestRootEntity(true),
            ]
        );

        $instance = new \Domain();
        $this->assertEquals(
            $expected,
            $instance->generateLinkContents($link, $item, $safe_url)
        );

        // Validates that default is to generate safe URLs
        if ($safe_url) {
            $this->assertEquals(
                $expected,
                $instance->generateLinkContents($link, $item)
            );
        }
    }
}
