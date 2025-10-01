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

use CronTask;
use DbTestCase;
use QueuedWebhook;
use Webhook;

class QueuedWebhookTest extends DbTestCase
{
    public function testQueuedWebhookClean()
    {
        $this->login();

        // Ajouter des entrées Webhook
        $webhook1 = new Webhook();
        $webhook1->add([
            'name' => 'webhook1',
            'sent_try' => 3,
        ]);

        $webhook2 = new Webhook();
        $webhook2->add([
            'name' => 'webhook2',
            'sent_try' => 1,
        ]);

        $this->assertGreaterThan(0, $webhook1->getID());
        $this->assertGreaterThan(0, $webhook2->getID());

        $queuedWebhook1 = new QueuedWebhook();
        $queuedWebhook1->add([
            'webhooks_id' => $webhook1->getID(),
            'send_time' => date("Y-m-d H:i:s", time() - (40 * DAY_TIMESTAMP)),
        ]);

        $queuedWebhook2 = new QueuedWebhook();
        $queuedWebhook2->add([
            'webhooks_id' => $webhook1->getID(),
            'send_time' => date("Y-m-d H:i:s", time() - (35 * DAY_TIMESTAMP)),
        ]);

        $queuedWebhook3 = new QueuedWebhook();
        $queuedWebhook3->add([
            'webhooks_id' => $webhook2->getID(),
            'send_time' => date("Y-m-d H:i:s", time() + (1 * DAY_TIMESTAMP)),
        ]);

        $queuedWebhook4 = new QueuedWebhook();
        $queuedWebhook4->add([
            'webhooks_id' => $webhook2->getID(),
            'send_time' => date("Y-m-d H:i:s", time() + (3 * DAY_TIMESTAMP)),
        ]);

        $this->assertGreaterThan(0, $queuedWebhook1->getID());
        $this->assertGreaterThan(0, $queuedWebhook2->getID());
        $this->assertGreaterThan(0, $queuedWebhook3->getID());
        $this->assertGreaterThan(0, $queuedWebhook4->getID());

        $queuedWebhook1->update(
            [
                'id' => $queuedWebhook1->getID(),
                'sent_try' => 4,
            ],
        );

        $this->assertEquals(4, $queuedWebhook1->fields['sent_try']);

        $queuedWebhook2->update(
            [
                'id' => $queuedWebhook2->getID(),
                'sent_try' => 1,
            ],
        );

        $this->assertEquals(1, $queuedWebhook2->fields['sent_try']);

        $cron = new CronTask();
        $cron->getFromDBByCrit([
            'itemtype' => QueuedWebhook::class,
            'name' => 'queuedwebhookclean',
        ]);

        QueuedWebhook::cronQueuedWebhookClean($cron);

        $this->assertFalse($queuedWebhook1->getFromDB($queuedWebhook1->getID()));
        $this->assertFalse($queuedWebhook2->getFromDB($queuedWebhook2->getID()));
        $this->assertTrue($queuedWebhook3->getFromDB($queuedWebhook3->getID()));
        $this->assertTrue($queuedWebhook4->getFromDB($queuedWebhook4->getID()));
    }
}
