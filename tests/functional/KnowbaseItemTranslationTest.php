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
use KnowbaseItem;
use KnowbaseItemTranslation;

final class KnowbaseItemTranslationTest extends DbTestCase
{
    /** Injected raw in the DOM by `GetTranslationContentController`. */
    private const PAYLOAD = '<p>hi</p><img src=x onerror="alert(1)"><script>alert(2)</script>';

    public function testAnswerIsSanitizedOnAdd(): void
    {
        $this->login();

        $translation = new KnowbaseItemTranslation();
        $id = $translation->add([
            'knowbaseitems_id' => $this->getKbItemId(),
            'language'         => 'fr_FR',
            'name'             => 'Traduction',
            'answer'           => self::PAYLOAD,
        ]);
        $this->assertGreaterThan(0, $id);

        $this->assertSanitized($translation->fields['answer']);
    }

    public function testAnswerIsSanitizedOnUpdate(): void
    {
        $this->login();

        $translation = new KnowbaseItemTranslation();
        $id = $translation->add([
            'knowbaseitems_id' => $this->getKbItemId(),
            'language'         => 'fr_FR',
            'name'             => 'Traduction',
            'answer'           => '<p>safe</p>',
        ]);
        $this->assertGreaterThan(0, $id);

        $this->assertTrue($translation->update([
            'id'     => $id,
            'answer' => self::PAYLOAD,
        ]));

        $this->assertTrue($translation->getFromDB($id));
        $this->assertSanitized($translation->fields['answer']);
    }

    /** `revertTo()` writes a stored revision back, and revisions predate sanitization. */
    public function testAnswerIsSanitizedOnRevert(): void
    {
        global $DB;

        $this->login();

        $translation = new KnowbaseItemTranslation();
        $id = $translation->add([
            'knowbaseitems_id' => $this->getKbItemId(),
            'language'         => 'fr_FR',
            'name'             => 'Traduction',
            'answer'           => '<p>v1</p>',
        ]);
        $this->assertGreaterThan(0, $id);

        // Updating snapshots the current content as a revision.
        $this->assertTrue($translation->update([
            'id'     => $id,
            'answer' => '<p>v2</p>',
        ]));

        $revision = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_knowbaseitems_revisions',
            'WHERE'  => [
                'knowbaseitems_id' => $translation->fields['knowbaseitems_id'],
                'language'         => 'fr_FR',
            ],
        ])->current();
        $this->assertNotNull($revision);

        // Simulate a revision stored before the answer was sanitized.
        $DB->update(
            'glpi_knowbaseitems_revisions',
            ['answer' => self::PAYLOAD],
            ['id' => $revision['id']]
        );

        $this->assertTrue($translation->getFromDB($id));
        $this->assertTrue($translation->revertTo($revision['id']));

        $this->assertTrue($translation->getFromDB($id));
        $this->assertSanitized($translation->fields['answer']);
    }

    private function getKbItemId(): int
    {
        return getItemByTypeName(KnowbaseItem::class, '_knowbaseitem01', true);
    }

    private function assertSanitized(string $answer): void
    {
        $this->assertStringNotContainsString('onerror', $answer);
        $this->assertStringNotContainsString('<script', $answer);
        $this->assertStringContainsString('<p>hi</p>', $answer);
    }
}
