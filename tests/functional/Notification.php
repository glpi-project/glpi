<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
use Glpi\Search\Item_Filter;
use QueuedNotification;

/* Test for inc/notification.class.php */

class Notification extends DbTestCase
{
    public function testGetMailingSignature()
    {
        global $CFG_GLPI;

        $this->login();

        $root    = getItemByTypeName('Entity', 'Root entity', true);
        $parent  = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $CFG_GLPI['mailing_signature'] = 'global_signature';

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("global_signature");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("global_signature");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("global_signature");

        $entity = new \Entity();
        $this->boolean($entity->update([
            'id'                => $root,
            'mailing_signature' => "signature_root",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_root");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_root");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_root");

        $this->boolean($entity->update([
            'id'                => $parent,
            'mailing_signature' => "signature_parent",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_parent");

        $this->boolean($entity->update([
            'id'                => $child_1,
            'mailing_signature' => "signature_child_1",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_child_1");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_parent");

        $this->boolean($entity->update([
            'id'                => $child_2,
            'mailing_signature' => "signature_child_2",
        ]))->isTrue();

        $this->string(\Notification::getMailingSignature($parent))->isEqualTo("signature_parent");
        $this->string(\Notification::getMailingSignature($child_1))->isEqualTo("signature_child_1");
        $this->string(\Notification::getMailingSignature($child_2))->isEqualTo("signature_child_2");
    }

    /**
     * Functionnal test on filtering a notification's target
     *
     * @return void
     */
    public function testFilter(): void
    {
        global $CFG_GLPI, $DB;

        $target_notification = "New Ticket";
        $entity = getItemByTypeName("Entity", "_test_root_entity", true);

        $this->login();
        $this->integer(countElementsInTable(QueuedNotification::getTable()))->isEqualTo(0);

        // Enable notifications
        $CFG_GLPI['use_notifications'] = true;
        $CFG_GLPI['notifications_mailing'] = true;

        // Activate only new followup notification
        $success = $DB->update(\Notification::getTable(), ['is_active' => false], [
            'name' => ['<>', $target_notification]
        ]);
        $this->boolean($success)->isTrue();
        $this->integer(
            countElementsInTable(
                \Notification::getTable(),
                ['is_active' => true]
            )
        )->isEqualTo(1);

        // Create categories
        $cat_A = $this->createItem("ITILCategory", ["name" => "cat A"]);
        $cat_B = $this->createItem("ITILCategory", ["name" => "cat B"]);

        // Filter notification on category
        $notification = getItemByTypeName("Notification", $target_notification);
        $success = Item_Filter::saveFilter($notification, $notification->getItemtypeToFilter(), [
            [
                "link"       => "and",
                "field"      => 7,                      // Category
                "searchtype" => "equals",
                "value"      => $cat_B->fields['id'],
            ]
        ]);
        $this->boolean($success)->isTrue();

        // Create tickets
        $this->createItem("Ticket", [
            "name"              => "Test",
            "content"           => "Test",
            "itilcategories_id" => $cat_A->fields['id'],
            "entities_id"       => $entity,
        ]);
        $this->integer(countElementsInTable(QueuedNotification::getTable()))->isEqualTo(0);

        $this->createItem("Ticket", [
            "name"              => "Test",
            "content"           => "Test",
            "itilcategories_id" => $cat_B->fields['id'],
            "entities_id"       => $entity,
        ]);
        $this->integer(countElementsInTable(QueuedNotification::getTable()))->isEqualTo(1);
    }
}
