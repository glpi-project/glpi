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
    public function testAnswerIsSanitized(): void
    {
        $this->login();
        $kbitem = $this->createItem(KnowbaseItem::class, [
            'name'   => 'Translated ' . $this->getUniqueString(),
            'answer' => '<p>x</p>',
        ]);
        $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kbitem->getID(),
            'language'         => 'fr_FR',
            'name'             => 'Traduit',
            'answer'           => '<p>Texte</p><img src="x" onerror="alert(1)">',
        ]);

        $request = Request::create('/Knowbase/KnowbaseItem/' . $kbitem->getID() . '/Translation/fr_FR');
        $request->attributes->set('knowbaseitems_id', $kbitem->getID());
        $request->attributes->set('language', 'fr_FR');
        $response = (new GetTranslationContentController())->__invoke($request);

        $answer = json_decode($response->getContent(), true)['answer'];
        $this->assertStringContainsString('<p>Texte</p>', $answer);
        $this->assertStringNotContainsString('onerror', $answer);
    }
}
