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

namespace tests\units\Glpi\Dashboard;

use DbTestCase;
use Reminder;
use Reminder_User;
use User;

/* Test for inc/dashboard/provider.class.php */

class Provider extends DbTestCase
{
    public function itemProvider()
    {
        return [
            ['item' => new \Computer()],
            ['item' => new \Ticket()],
            ['item' => new \Item_DeviceSimcard()],
        ];
    }

    /**
     * @dataProvider itemProvider
     */
    public function testBigNumber(\CommonDBTM $item)
    {
        $this->login();

        $itemtype = $item->getType();
        $data = [
            \Glpi\Dashboard\Provider::bigNumberItem($item),
            call_user_func(['\\Glpi\\Dashboard\\Provider', "bigNumber$itemtype"])
        ];

        foreach ($data as $result) {
            $this->array($result)
            ->hasKeys([
                'number',
                'url',
                'label',
                'icon',
            ]);
            if ($item::getType() !== 'Item_DeviceSimcard') {
                // Ignore count for simcards. None are added in Bootstrap process and is here for regression testing only.
                $this->integer($result['number'])->isGreaterThan(0);
            }
            $this->string($result['url'])->contains($item::getSearchURL());
            //Verify URL doesn't have two query param joiners next to each other
            $this->string($result['url'])->notContains('&&');
            $this->string($result['url'])->notContains('?&');
            //Verify URL only has one ? joiner
            $this->integer(substr_count($result['url'], '?'))->isLessThanOrEqualTo(1);
            $this->string($result['label'])->isNotEmpty();
            $this->string($result['icon'])->isEqualTo($item::getIcon());
        }
    }


    public function ticketsCaseProvider()
    {
        return [
            ['case' => 'notold'],
            ['case' => 'late'],
            ['case' => 'waiting_validation'],
            ['case' => 'incoming'],
            ['case' => 'waiting'],
            ['case' => 'assigned'],
            ['case' => 'planned'],
            ['case' => 'solved'],
            ['case' => 'closed'],
            ['case' => 'status'],
        ];
    }


    /**
     * @dataProvider ticketsCaseProvider
     */
    public function testNbTicketsGeneric(string $case)
    {
        $result = \Glpi\Dashboard\Provider::nbTicketsGeneric($case);

        $this->array($result)
         ->hasKeys([
             'number',
             'url',
             'label',
             'icon',
             's_criteria',
             'itemtype',
         ]);
        $this->integer($result['number']);
        $this->string($result['url'])->contains(\Ticket::getSearchURL());
        $this->string($result['icon']);
        $this->string($result['label']);
        $this->array($result['s_criteria'])->size->isGreaterThan(0);
        $this->string($result['itemtype'])->isEqualTo('Ticket');
    }


    public function itemFKProvider()
    {
        return [
            ['item' => new \Computer(), 'fk_item' => new \Entity()],
            ['item' => new \Software(), 'fk_item' => new \Entity()],
        ];
    }


    /**
     * @dataProvider itemFKProvider
     */
    public function testNbItemByFk(\CommonDBTM $item, \CommonDBTM $fk_item)
    {
        $this->login();

        $result = \Glpi\Dashboard\Provider::nbItemByFk($item, $fk_item);
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        foreach ($result['data'] as $data) {
            $this->array($data)
            ->hasKeys([
                'number',
                'label',
                'url',
            ]);

            $this->integer($data['number'])->isGreaterThan(0);
            $this->string($data['label']);
            $this->string($data['url'])->contains($item::getSearchURL());
        }
    }


    public function testTicketsOpened()
    {
        $result = \Glpi\Dashboard\Provider::ticketsOpened();
        $this->array($result)
         ->hasKeys([
             'data',
             'distributed',
             'label',
             'icon',
         ]);

        $this->boolean($result['distributed'])->isFalse();
        $this->string($result['icon']);
        $this->string($result['label']);

        foreach ($result['data'] as $data) {
            $this->array($data)
            ->hasKeys([
                'number',
                'label',
                'url',
            ]);

            $this->integer($data['number'])->isGreaterThan(0);
            $this->string($data['label']);
            $this->string($data['url'])->contains(\Ticket::getSearchURL());
        }
    }


    public function testGetTicketsEvolution()
    {
        $result = \Glpi\Dashboard\Provider::getTicketsEvolution();
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        $this->string($result['icon']);
        $this->string($result['label']);
        $this->array($result['data'])->hasKeys(['labels', 'series']);
        $this->array($result['data']['labels'])->isNotEmpty();
        $this->array($result['data']['series'])->isNotEmpty();

        $nb_labels = count($result['data']['labels']);
        foreach ($result['data']['series'] as $serie) {
            $this->array($serie)->hasKey('data');
            $this->integer(count($serie['data']))->isEqualTo($nb_labels);

            foreach ($serie['data'] as $serie_data) {
                $this->integer($serie_data['value']);
                $this->string($serie_data['url'])->contains(\Ticket::getSearchURL());
            }
        }
    }


    public function testGetTicketsStatus()
    {
        $this->login();

        $result = \Glpi\Dashboard\Provider::getTicketsStatus();
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        $this->string($result['icon']);
        $this->string($result['label']);
        $this->array($result['data'])->hasKeys(['labels', 'series']);
        $this->array($result['data']['labels'])->isNotEmpty();
        $this->array($result['data']['series'])->isNotEmpty();

        $nb_labels = count($result['data']['labels']);
        foreach ($result['data']['series'] as $serie) {
            $this->array($serie)->hasKey('data');
            $this->integer(count($serie['data']))->isEqualTo($nb_labels);

            foreach ($serie['data'] as $serie_data) {
                $this->integer($serie_data['value']);
                $this->string($serie_data['url'])->contains(\Ticket::getSearchURL());
            }
        }
    }


    public function testTopTicketsCategories()
    {
        $this->login();

        $result = \Glpi\Dashboard\Provider::multipleNumberTicketByITILCategory();
        $this->array($result)
         ->hasKeys([
             'data',
             'label',
             'icon',
         ]);

        $this->string($result['icon']);
        $this->string($result['label']);

        foreach ($result['data'] as $data) {
            $this->array($data)
            ->hasKeys([
                'number',
                'label',
                'url',
            ]);

            $this->integer($data['number'])->isGreaterThan(0);
            $this->string($data['label']);
            $this->string($data['url'])->contains(\Ticket::getSearchURL());
        }
    }

    public function monthYearProvider()
    {
        return [
            [
                'monthyear' => '2019-01',
                'expected'  => [
                    '2019-01-01 00:00:00',
                    '2019-02-01 00:00:00'
                ]
            ], [
                'monthyear' => '2019-12',
                'expected'  => [
                    '2019-12-01 00:00:00',
                    '2020-01-01 00:00:00'
                ]
            ]
        ];
    }


    /**
     * @dataProvider monthYearProvider
     */
    public function testFormatMonthyearDates(string $monthyear, array $expected)
    {
        $this->array(\Glpi\Dashboard\Provider::formatMonthyearDates($monthyear))
         ->isEqualTo($expected);
    }

    protected function testGetArticleListReminderProvider(): iterable
    {
        $this->login();

        // Create one reminder that will be visible because we are its author
        $reminder = $this->createItem(Reminder::class, ['name' => 'test']);
        yield ['expected' => 1];

        // Change author to someone else
        $tech = getItemByTypeName(User::class, 'tech');
        $this->updateItem($reminder::getType(), $reminder->getID(), [
            'users_id'  => $tech->getID()
        ]);
        yield ['expected' => 0];

        // Allow our user through the visiblity criteria system
        $self = getItemByTypeName(User::class, TU_USER);
        $this->createItem(Reminder_User::class, [
            'reminders_id' => $reminder->getID(),
            'users_id' => $self->getID(),
        ]);
        yield ['expected' => 1];
    }

    /**
     * @dataprovider testGetArticleListReminderProvider
     */
    public function testGetArticleListReminder(int $expected): void
    {
        $results = \Glpi\Dashboard\Provider::getArticleListReminder();
        $this->integer($results['number'])->isEqualTo($expected);
    }
}
