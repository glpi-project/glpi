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

namespace tests\units\Glpi\Controller;

use Glpi\Controller\ShareTokenController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Security\ShareTokenManager;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Log;
use User;

final class ShareTokenControllerTest extends DbTestCase
{
    private function createKnowbaseItem(): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'users_id'    => getItemByTypeName(User::class, TU_USER, true),
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => $this->getUniqueString(),
            'answer'      => '<p>Test content</p>',
        ]);
    }

    public function testCreateReusesExistingTokenForASingleLinkItemtype(): void
    {
        $this->login();
        $kb       = $this->createKnowbaseItem();
        $existing = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);

        // KnowbaseItem is single-link: a racing publish must not mint a second token.
        $response = (new ShareTokenController())->create(KnowbaseItem::class, $kb->getID());

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame($existing->getID(), (int) $payload['token']['id']);

        $tokens = (new ShareTokenManager())->getTokensForItem(KnowbaseItem::class, $kb->getID());
        $this->assertCount(1, $tokens);
    }

    public function testCreateReactivatesADisabledTokenForASingleLinkItemtype(): void
    {
        $this->login();
        $kb       = $this->createKnowbaseItem();
        $existing = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);
        $this->updateItem(ShareToken::class, $existing->getID(), ['is_active' => 0]);

        $response = (new ShareTokenController())->create(KnowbaseItem::class, $kb->getID());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame($existing->getID(), (int) $payload['token']['id']);
        $this->assertSame(1, (int) $payload['token']['is_active']);

        $tokens = (new ShareTokenManager())->getTokensForItem(KnowbaseItem::class, $kb->getID());
        $this->assertCount(1, $tokens);
        $this->assertSame(1, (int) $tokens[0]['is_active']);
    }

    public function testRegenerateReplacesTokenWithNewActiveOne(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $old = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);
        $old_id    = $old->getID();
        $manager   = new ShareTokenManager();
        $old_plain = $manager->decryptToken((string) $old->fields['token']);

        $controller = new ShareTokenController();
        $response   = $controller->regenerate($old_id);

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        // Old token row is purged.
        $this->assertFalse((new ShareToken())->getFromDB($old_id));

        // Exactly one active token remains, with a different token value.
        $tokens = $manager->getTokensForItem(KnowbaseItem::class, $kb->getID());
        $this->assertCount(1, $tokens);
        $new_plain = $manager->decryptToken((string) $tokens[0]['token']);
        $this->assertNotSame($old_plain, $new_plain);
        $this->assertSame($new_plain, $payload['token']['token']);
    }

    public function testRegenerateLogsSingleRegeneratedHistoryEntry(): void
    {
        global $DB;

        $this->login();
        $kb = $this->createKnowbaseItem();
        $old = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);

        $sharing_log_criteria = [
            'itemtype'      => KnowbaseItem::class,
            'items_id'      => $kb->getID(),
            'itemtype_link' => ShareToken::class,
        ];

        $logs_before = iterator_to_array($DB->request([
            'FROM'  => Log::getTable(),
            'WHERE' => $sharing_log_criteria,
        ]));
        $ids_before = array_column($logs_before, 'id');

        $controller = new ShareTokenController();
        $response   = $controller->regenerate($old->getID());
        $payload    = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $logs_after = iterator_to_array($DB->request([
            'FROM'  => Log::getTable(),
            'WHERE' => $sharing_log_criteria,
        ]));

        $new_logs = array_values(array_filter(
            $logs_after,
            static fn(array $row) => !in_array($row['id'], $ids_before, true)
        ));

        // Exactly one new sharing log row, encoding the regeneration.
        $this->assertCount(1, $new_logs);
        $this->assertSame(Log::HISTORY_UPDATE_RELATION, (int) $new_logs[0]['linked_action']);

        // The automatic "disabled"/"enabled" pair must not have been written by the regenerate.
        $spurious = array_filter(
            $new_logs,
            static fn(array $row) => in_array(
                (int) $row['linked_action'],
                [Log::HISTORY_ADD_RELATION, Log::HISTORY_DEL_RELATION],
                true
            )
        );
        $this->assertCount(0, $spurious);
    }

    public function testRegenerateDeniedWithoutUpdateRight(): void
    {
        $this->login();
        $kb    = $this->createKnowbaseItem();
        $token = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);

        // post-only is a self-service profile with no knowledge base management right.
        $this->login('post-only', 'postonly');

        $this->expectException(AccessDeniedHttpException::class);

        (new ShareTokenController())->regenerate($token->getID());
    }
}
