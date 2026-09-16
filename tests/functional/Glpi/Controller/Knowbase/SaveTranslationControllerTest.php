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

use Glpi\Controller\Knowbase\SaveTranslationController;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItemTranslation;
use Symfony\Component\HttpFoundation\Request;

class SaveTranslationControllerTest extends DbTestCase
{
    /** Injected with `innerHTML` by `ArticleController#loadTranslationContent()`. */
    private const PAYLOAD = '<p>hi</p><img src=x onerror="alert(1)"><script>alert(2)</script>';

    private function save(int $id, string $language, string $answer): array
    {
        $request = Request::create(
            '/Knowbase/KnowbaseItem/' . $id . '/Translation',
            'POST',
            content: json_encode(['language' => $language, 'answer' => $answer, 'name' => 'Traduction']),
        );
        $request->attributes->set('knowbaseitems_id', $id);

        return json_decode((new SaveTranslationController())->__invoke($request)->getContent(), true);
    }

    private function createArticle(): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'   => 'Translated article ' . $this->getUniqueString(),
            'answer' => '<p>original</p>',
        ]);
    }

    private function getStoredAnswer(int $knowbaseitems_id, string $language): string
    {
        $translation = new KnowbaseItemTranslation();
        $this->assertTrue($translation->getFromDBByCrit([
            'knowbaseitems_id' => $knowbaseitems_id,
            'language'         => $language,
        ]));

        return $translation->fields['answer'];
    }

    public function testAnswerIsSanitizedWhenCreatingATranslation(): void
    {
        $this->login();
        $kbitem = $this->createArticle();

        $this->assertTrue($this->save($kbitem->getID(), 'fr_FR', self::PAYLOAD)['success']);

        $this->assertSanitized($this->getStoredAnswer($kbitem->getID(), 'fr_FR'));
    }

    public function testAnswerIsSanitizedWhenUpdatingATranslation(): void
    {
        $this->login();
        $kbitem = $this->createArticle();

        $this->assertTrue($this->save($kbitem->getID(), 'fr_FR', '<p>safe</p>')['success']);
        $this->assertTrue($this->save($kbitem->getID(), 'fr_FR', self::PAYLOAD)['success']);

        $this->assertSanitized($this->getStoredAnswer($kbitem->getID(), 'fr_FR'));
    }

    /**
     * The sanitizer must not be a render pipeline: plain text posted by a client
     * other than the editor has to be stored as-is, not wrapped in `<p>`.
     */
    public function testPlainTextAnswerIsStoredUnchanged(): void
    {
        $this->login();
        $kbitem = $this->createArticle();

        $this->assertTrue($this->save($kbitem->getID(), 'fr_FR', 'Contenu initial')['success']);

        $this->assertSame('Contenu initial', $this->getStoredAnswer($kbitem->getID(), 'fr_FR'));
    }

    private function assertSanitized(string $answer): void
    {
        $this->assertStringNotContainsString('onerror', $answer);
        $this->assertStringNotContainsString('<script', $answer);
        $this->assertStringContainsString('<p>hi</p>', $answer);
    }
}
