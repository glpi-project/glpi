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

namespace Glpi\Tests\Glpi;

use Tag;
use Tag_Itemtype;

trait TagTrait
{
    /**
     * Verify itemtypes for a given tag
     *
     * @param Tag $tag Tag to verify
     * @param array|string $itemtypes Expected itemtypes
     */
    private function verifyItemtypes(Tag $tag, array|string $itemtypes): void
    {
        global $CFG_GLPI;

        $tag_itemtype = new Tag_Itemtype();
        if (!is_array($itemtypes)) {
            $this->assertEmpty($tag_itemtype->find(['tags_id' => $tag->getID()]));
            return;
        }

        foreach ($itemtypes as $itemtype) {
            $is_add_for_tag = $tag_itemtype->getFromDBByCrit(['tags_id' => $tag->getID(), 'itemtype' => $itemtype]);
            if (!in_array($itemtype, $CFG_GLPI['taggable_types'])) {
                $this->assertFalse($is_add_for_tag);
            } else {
                $this->assertTrue($is_add_for_tag);
            }
        }
    }

    /**
     * Create a tag with given input and verify itemtypes
     *
     * @param array $input Input data for tag creation
     * @param array $skip_fields Fields to skip verification
     *
     * @return Tag Created tag
     */
    private function createTag(array $input, array $skip_fields = []): Tag
    {
        $tag = $this->createItem(Tag::class, $input, $skip_fields);
        $this->verifyItemtypes($tag, $input['_itemtypes'] ?? []);

        return $tag;
    }

    /**
     * Update a tag with given input and verify itemtypes
     *
     * @param Tag $tag Tag to update
     * @param array $input Input data
     * @param array $skip_fields Fields to skip verification
     *
     * @return Tag Updated tag
     */
    private function updateTag(Tag $tag, array $input, array $skip_fields = []): Tag
    {
        $tag = $this->updateItem(Tag::class, $tag->getID(), $input, $skip_fields);
        $this->verifyItemtypes($tag, $input['_itemtypes'] ?? []);
        return $tag;
    }

    /**
     * Build and save a real instance of the given itemtype, so a tag can be attached to it.
     */
    private function createTaggableTestItemtype(string $itemtype, array $input = []): \CommonDBTM
    {
        $entities_id = $this->getTestRootEntity(true);

        switch ($itemtype) {
            case \Database::class:
                $database_instance = $this->createItem(\DatabaseInstance::class, [
                    'name' => 'Test Database Instance for Tag',
                    'entities_id' => $entities_id,
                ]);
                return $this->createItem(\Database::class, array_merge([
                    'name' => 'Test Database for Tag',
                    'databaseinstances_id' => $database_instance->getID(),
                ], $input));

            case \DomainRecord::class:
                $domain = $this->createItem(\Domain::class, [
                    'name' => 'Test Domain for Tag',
                    'entities_id' => $entities_id,
                ]);
                return $this->createItem(\DomainRecord::class, array_merge([
                    'name' => 'Test Domain Record for Tag',
                    'domains_id' => $domain->getID(),
                    'entities_id' => $entities_id,
                ], $input));

            case \NetworkName::class:
                $computer = $this->createItem(\Computer::class, [
                    'name' => 'Test Computer for Tag (NetworkName)',
                    'entities_id' => $entities_id,
                ]);
                return $this->createItem(\NetworkName::class, array_merge([
                    'itemtype' => \Computer::class,
                    'items_id' => $computer->getID(),
                ], $input));

            case \Item_DeviceSimcard::class:
                $computer = $this->createItem(\Computer::class, [
                    'name' => 'Test Computer for Tag (Item_DeviceSimcard)',
                    'entities_id' => $entities_id,
                ]);
                $device_simcard = $this->createItem(\DeviceSimcard::class, [
                    'designation' => 'Test DeviceSimcard for Tag',
                    'entities_id' => $entities_id,
                ]);
                return $this->createItem(\Item_DeviceSimcard::class, array_merge([
                    'items_id' => $computer->getID(),
                    'itemtype' => \Computer::class,
                    'devicesimcards_id' => $device_simcard->getID(),
                    'entities_id' => $entities_id,
                ], $input));

            case \RSSFeed::class:
                return $this->createItem(\RSSFeed::class, array_merge([
                    'name' => 'Test RSSFeed for Tag',
                    'url' => 'https://example.org/rss.xml',
                ], $input));

            case \ValidationStep::class:
                return $this->createItem(\ValidationStep::class, array_merge([
                    'name' => 'Test ValidationStep for Tag',
                    'minimal_required_validation_percent' => 100,
                ], $input));

            case \NetworkPortType::class:
                return $this->createItem(\NetworkPortType::class, array_merge([
                    'name' => 'Test NetworkPortType for Tag',
                    'entities_id' => $entities_id,
                    'value_decimal' => 0,
                ], $input));

            case \Holiday::class:
                return $this->createItem(\Holiday::class, array_merge([
                    'name' => 'Test Holiday for Tag',
                    'entities_id' => $entities_id,
                    'begin_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d'),
                ], $input));

            case \IPNetwork::class:
                return $this->createItem(\IPNetwork::class, array_merge([
                    'name' => 'Test IPNetwork for Tag',
                    'entities_id' => $entities_id,
                    'network' => '198.51.100.0/24',
                ], $input), ['network']);

            case \FQDN::class:
                return $this->createItem(\FQDN::class, array_merge([
                    'name' => 'Test FQDN for Tag',
                    'entities_id' => $entities_id,
                    'fqdn' => 'test-fqdn-for-tag.example.org',
                ], $input));

            case \USBVendor::class:
                return $this->createItem(\USBVendor::class, array_merge([
                    'name' => 'Test USBVendor for Tag',
                    'entities_id' => $entities_id,
                    'vendorid' => 'ffff',
                ], $input));

            case \PCIVendor::class:
                return $this->createItem(\PCIVendor::class, array_merge([
                    'name' => 'Test PCIVendor for Tag',
                    'entities_id' => $entities_id,
                    'vendorid' => 'ffff',
                ], $input));

            case \RuleRightParameter::class:
                return $this->createItem(\RuleRightParameter::class, array_merge([
                    'name' => 'Test RuleRightParameter for Tag',
                    'value' => 'test-value-for-tag',
                ], $input));

            case \DCRoom::class:
                return $this->createItem(\DCRoom::class, array_merge([
                    'name' => 'Test DCRoom for Tag',
                    'entities_id' => $entities_id,
                    'vis_cols' => 1,
                    'vis_rows' => 1,
                ], $input));

            case \DocumentType::class:
                // `ext` must be a real, non-empty value: DocumentType::getUploadableFilePattern()
                // builds a global regex from every uploadable type's `ext` and caches it statically,
                // so a NULL/empty `ext` here would corrupt file-upload rendering for every item
                // shown afterwards in the same request.
                return $this->createItem(\DocumentType::class, array_merge([
                    'name' => 'Test DocumentType for Tag',
                    'ext' => 'testtagext',
                    'is_uploadable' => 0,
                ], $input));

            case \AuthMail::class:
                return $this->createItem(\AuthMail::class, array_merge([
                    'name' => 'Test AuthMail for Tag',
                    'connect_string' => '{imap.example.org:993/imap/ssl}',
                ], $input));

            case \MailCollector::class:
                return $this->createItem(\MailCollector::class, array_merge([
                    'name' => 'Test MailCollector for Tag',
                    'host' => '{imap.example.org:993/imap/ssl}',
                    'login' => 'test-login',
                ], $input), ['host']);

            case \NotificationTemplate::class:
                return $this->createItem(\NotificationTemplate::class, array_merge([
                    'name' => 'Test NotificationTemplate for Tag',
                    'itemtype' => \Computer::class,
                ], $input));

            case \Notification::class:
                return $this->createItem(\Notification::class, array_merge([
                    'name' => 'Test Notification for Tag',
                    'entities_id' => $entities_id,
                    'itemtype' => \Computer::class,
                    'event' => 'test_event_for_tag',
                ], $input));

            case \Unmanaged::class:
                return $this->createItem(\Unmanaged::class, array_merge([
                    'name' => 'Test Unmanaged for Tag',
                    'entities_id' => $entities_id,
                    'itemtype' => \NetworkEquipment::class,
                ], $input));

            case \Agent::class:
                $computer = $this->createItem(\Computer::class, [
                    'name' => 'Test Computer for Tag (Agent)',
                    'entities_id' => $entities_id,
                ]);
                $agenttype = $this->createItem(\AgentType::class, ['name' => 'Test Agent Type for Tag']);
                return $this->createItem(\Agent::class, array_merge([
                    'deviceid' => 'test-device-id-for-tag',
                    'entities_id' => $entities_id,
                    'agenttypes_id' => $agenttype->getID(),
                    'itemtype' => \Computer::class,
                    'items_id' => $computer->getID(),
                ], $input));

            default:
                $item = new $itemtype();
                $item_input = [$itemtype::getNameField() => 'Test ' . $itemtype . ' for Tag'];
                if ($item->isField('entities_id')) {
                    $item_input['entities_id'] = $entities_id;
                }
                return $this->createItem($itemtype, array_merge($item_input, $input));
        }
    }
}
