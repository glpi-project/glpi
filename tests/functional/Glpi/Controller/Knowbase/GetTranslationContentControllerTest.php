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

namespace tests\units\Glpi\Controller\Knowbase;

use Glpi\Controller\Knowbase\GetTranslationContentController;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItemTranslation;
use Symfony\Component\HttpFoundation\Request;

class GetTranslationContentControllerTest extends DbTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function callController(int $id, string $language): array
    {
        $request = Request::create('/Knowbase/KnowbaseItem/' . $id . '/Translation/' . $language);
        $request->attributes->set('knowbaseitems_id', $id);
        $request->attributes->set('language', $language);

        return json_decode((new GetTranslationContentController())->__invoke($request)->getContent(), true);
    }

    /**
     * Rows written before answers were sanitized on write keep their payload,
     * and the client injects this response with `innerHTML`.
     */
    public function testLegacyUnsanitizedAnswerIsSanitizedOnRead(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $this->login();

        $kbitem = $this->createItem(KnowbaseItem::class, [
            'name'   => 'Translated article ' . $this->getUniqueString(),
            'answer' => '<p>original</p>',
        ]);
        $translation = $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kbitem->getID(),
            'language'         => 'fr_FR',
            'name'             => 'Article traduit',
            'answer'           => '<p>clean</p>',
        ]);

        // Straight to the DB: `prepareInputForUpdate()` would sanitize this away.
        $DB->update(
            KnowbaseItemTranslation::getTable(),
            ['answer' => '<div class="kb-html-block"><img src=x onerror="alert(1)"></div>'],
            ['id' => $translation->getID()],
        );

        $answer = $this->callController($kbitem->getID(), 'fr_FR')['answer'];

        $this->assertStringNotContainsString('onerror', $answer);
        $this->assertStringContainsString('kb-html-block', $answer);
    }
}
