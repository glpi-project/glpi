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

namespace tests\units;

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/contract.class.php */

class ContractTest extends DbTestCase
{
    public function testClone()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $contract = new \Contract();
        $input = [
            'name' => 'A test contract',
            'entities_id'  => 0,
        ];
        $cid = $contract->add($input);
        $this->assertGreaterThan(0, $cid);

        $cost = new \ContractCost();
        $cost_id = $cost->add([
            'contracts_id' => $cid,
            'name'         => 'Test cost',
        ]);
        $this->assertGreaterThan(0, $cost_id);

        $suppliers_id = getItemByTypeName('Supplier', '_suplier01_name', true);
        $this->assertGreaterThan(0, $suppliers_id);

        $link_supplier = new \Contract_Supplier();
        $link_id = $link_supplier->add([
            'suppliers_id' => $suppliers_id,
            'contracts_id' => $cid,
        ]);
        $this->assertGreaterThan(0, $link_id);

        $this->assertTrue($link_supplier->getFromDB($link_id));
        $relation_items = $link_supplier->getItemsAssociatedTo($contract->getType(), $cid);
        $this->assertCount(1, $relation_items, 'Original Contract_Supplier not found!');

        $citem = new \Contract_Item();
        $citems_id = $citem->add([
            'contracts_id' => $cid,
            'itemtype'     => 'Computer',
            'items_id'     => getItemByTypeName('Computer', '_test_pc01', true),
        ]);
        $this->assertGreaterThan(0, $citems_id);

        $this->assertTrue($citem->getFromDB($citems_id));
        $relation_items = $citem->getItemsAssociatedTo($contract->getType(), $cid);
        $this->assertCount(1, $relation_items, 'Original Contract_Item not found!');

        $cloned = $contract->clone();
        $this->assertGreaterThan($cid, $cloned);

        foreach ([\ContractCost::class, \Contract_Supplier::class, \Contract_Item::class] as $rel_class) {
            $this->assertSame(
                1,
                countElementsInTable(
                    $rel_class::getTable(),
                    ['contracts_id' => $cloned]
                ),
                'Missing relation with ' . $rel_class
            );
        }
    }

    public static function getSpecificValueToDisplayProvider()
    {
        return [
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2020-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_NEVER,
                    'periodicity' => 0,
                ],
                'expected' => "<span class='red'>2020-06-30</span>",
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2020-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 0,
                ],
                'expected' => "2026-06-30",
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2020-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_EXPRESS,
                    'periodicity' => 0,
                ],
                'expected' => "<span class='red'>2020-06-30</span>",
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2025-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_NEVER,
                    'periodicity' => 0,
                ],
                'expected' => "<span class='red'>2025-06-30</span>",
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2025-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 0,
                ],
                'expected' => '2026-06-30',
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2025-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_EXPRESS,
                    'periodicity' => 3,
                ],
                'expected' => "<span class='red'>2025-06-30</span>",
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2019-01-01',
                    'duration' => 60,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 12,
                ],
                'expected' => '2026-12-31',
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2029-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 3,
                ],
                'expected' => "2029-06-30",
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2022-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 12,
                ],
                'expected' => '2026-06-30',
            ],
            [
                'field' => '_virtual_expiration',
                'values' => [
                    'begin_date' => '2026-01-01',
                    'duration' => 6,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 12,
                ],
                'expected' => '2026-06-30',
            ],
            [
                'field' => '_virtual_expire_notice',
                'values' => [
                    'begin_date' => '2020-01-01',
                    'duration' => 6,
                    'notice' => 2,
                    'renewal' => \Contract::RENEWAL_NEVER,
                    'periodicity' => 0,
                ],
                'expected' => "<span class='red'>2020-04-30</span>",
            ],
            [
                'field' => '_virtual_expire_notice',
                'values' => [
                    'begin_date' => '2020-01-01',
                    'duration' => 6,
                    'notice' => 3,
                    'renewal' => \Contract::RENEWAL_NEVER,
                    'periodicity' => 12,
                ],
                'expected' => "<span class='red'>2020-03-31</span>",
            ],
            [
                'field' => '_virtual_expire_notice',
                'values' => [
                    'begin_date' => '2020-01-01',
                    'duration' => 6,
                    'notice' => 2,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 0,
                ],
                'expected' => "2026-04-30",
            ],
            [
                'field' => '_virtual_expire_notice',
                'values' => [
                    'begin_date' => '2020-01-01',
                    'duration' => 6,
                    'notice' => 3,
                    'renewal' => \Contract::RENEWAL_EXPRESS,
                    'periodicity' => 0,
                ],
                'expected' => "<span class='red'>2020-03-31</span>",
            ],
            [
                'field' => '_virtual_expire_notice',
                'values' => [
                    'begin_date' => '2026-01-01',
                    'duration' => 6,
                    'notice' => 4,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 12,
                ],
                'expected' => "<span class='red'>2026-02-28</span>",
            ],
            [
                'field' => '_virtual_expire_notice',
                'values' => [
                    'begin_date' => '2029-01-01',
                    'duration' => 6,
                    'notice' => 3,
                    'renewal' => \Contract::RENEWAL_TACIT,
                    'periodicity' => 6,
                ],
                'expected' => "2029-03-31",
            ],
        ];
    }

    #[DataProvider('getSpecificValueToDisplayProvider')]
    public function testGetSpecificValueToDisplay($field, $values, $expected)
    {
        $this->login();
        $_SESSION['glpi_currenttime'] = '2026-03-17 10:00:00';
        $this->setEntity('_test_root_entity', true);
        $contract = new \Contract();
        $this->assertEquals($expected, $contract->getSpecificValueToDisplay($field, $values));
    }

    public function testLinkUser()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $contract = new \Contract();
        $input = [
            'name' => 'A test contract',
            'entities_id'  => 0,
        ];
        $cid = $contract->add($input);
        $this->assertGreaterThan(0, $cid);

        $user = new \User();
        $uid = $user->add([
            'name' => 'Test User',
            'firstname' => 'Test',
            'realname' => 'User',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $uid);

        $link_user = new \Contract_User();
        $link_id = $link_user->add([
            'users_id' => $uid,
            'contracts_id' => $cid,
        ]);
        $this->assertGreaterThan(0, $link_id);

        $this->assertTrue($link_user->getFromDB($link_id));
        $relation_items = $link_user->getItemsAssociatedTo($contract->getType(), $cid);
        $this->assertCount(1, $relation_items, 'Original Contract_User not found!');
    }

    public static function warrantyExpirProvider(): array
    {
        return [
            // Standard expiration: correct end-of-month when day overflows (31 Jan + 12 months = 30 Jan).
            [
                'current_time' => '2027-01-01',
                'from'         => '2025-01-31',
                'addwarranty'  => 12,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => '2026-01-30',
            ],
            // Standard expiration: end-of-month in February (28 days, non-leap year).
            [
                'current_time' => '2027-01-01',
                'from'         => '2025-03-01',
                'addwarranty'  => 12,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => '2026-02-28',
            ],
            // Standard expiration: end-of-month in February of a leap year (29 days).
            [
                'current_time' => '2027-01-01',
                'from'         => '2031-03-01',
                'addwarranty'  => 12,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => '2032-02-29',
            ],
            // Lifelong warranty without notice: returns "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => __('Never'),
            ],
            // Zero duration without notice: returns the start date.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => '2020-01-01',
            ],
            // Standard expiration without notice or renewal (24 months → last day included).
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => '2021-12-31',
            ],
            // Lifelong warranty with notice: returns "Never" because duration is infinite.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => __('Never'),
            ],
            // Zero duration with 1-month notice: notice is clamped to the start date.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => '2020-01-01',
            ],
            // 1-month notice on a 24-month contract without renewal.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 0,
                'expected'     => '2021-12-01',
            ],
            // Lifelong warranty with tacit renewal and no notice: returns "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 0,
                'expected'     => __('Never'),
            ],
            // Zero duration with tacit renewal and no notice: returns the start date.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 0,
                'expected'     => '2020-01-01',
            ],
            // Tacit renewal without notice: next future expiration (2020 + n×24 months).
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 0,
                'expected'     => '2028-01-01',
            ],
            // Lifelong warranty with tacit renewal and notice: returns "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 0,
                'expected'     => __('Never'),
            ],
            // Zero duration, tacit renewal, 1-month notice: notice is clamped to the start date.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 0,
                'expected'     => '2020-01-01',
            ],
            // Tacit renewal with notice: next future notice (1 month before the next due date).
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 0,
                'expected'     => '2027-12-01',
            ],
            // Lifelong warranty, periodicity different from duration, no notice: returns "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 12,
                'expected'     => __('Never'),
            ],
            // Zero duration, 12-month periodicity, no notice or renewal: returns the start date.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 12,
                'expected'     => '2020-01-01',
            ],
            // 24-month contract with 12-month periodicity, no notice or renewal: standard expiration.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 12,
                'expected'     => '2021-12-31',
            ],
            // Lifelong warranty, 12-month periodicity, 1-month notice, no renewal: returns "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 12,
                'expected'     => __('Never'),
            ],
            // Zero duration, 12-month periodicity, 1-month notice: clamped to the start date.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 12,
                'expected'     => '2020-01-01',
            ],
            // 24-month contract, 12-month periodicity, 1-month notice, no renewal: notice of the last period.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => false,
                'periodicity'  => 12,
                'expected'     => '2021-12-01',
            ],
            // Lifelong warranty, tacit renewal, 12-month periodicity, no notice: returns "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 12,
                'expected'     => __('Never'),
            ],
            // Zero duration, tacit renewal, 12-month periodicity: zero renewal period → "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 12,
                'expected'     => __('Never'),
            ],
            // Tacit renewal, 12-month periodicity different from duration (24 months): next future expiration.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 0,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 12,
                'expected'     => '2028-01-01',
            ],
            // Lifelong warranty, tacit renewal, 12-month periodicity, 1-month notice: returns "Never".
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => -1,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 12,
                'expected'     => __('Never'),
            ],
            // Zero duration, tacit renewal, 1-month notice, 12-month periodicity: notice active from contract start.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 0,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 12,
                'expected'     => '2020-01-01',
            ],
            // Tacit renewal, 12-month periodicity, 1-month notice: next future notice.
            [
                'current_time' => '2027-01-01',
                'from'         => '2020-01-01',
                'addwarranty'  => 24,
                'deletenotice' => 1,
                'color'        => false,
                'auto_renew'   => true,
                'periodicity'  => 12,
                'expected'     => '2027-12-01',
            ],
        ];
    }

    #[DataProvider('warrantyExpirProvider')]
    public function testContractExpirationWithEdgeDates(
        string $current_time,
        string $from,
        int $addwarranty,
        int $deletenotice,
        bool $color,
        bool $auto_renew,
        int $periodicity,
        string $expected
    ): void {
        $this->login();
        $_SESSION['glpi_currenttime'] = $current_time;

        $result = \Infocom::getWarrantyExpir($from, $addwarranty, $deletenotice, $color, $auto_renew, $periodicity);

        $formatted_expected = in_array($expected, [__('Never'), ''], true) ? $expected : \Html::convDate($expected);

        $this->assertEquals($formatted_expected, $result);
    }
}
