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

namespace tests\units\Glpi\Altcha;

use Glpi\Altcha\AltchaManager;
use Glpi\Application\View\TemplateRenderer;
use GLPITestCase;

final class AltchaManagerTest extends GLPITestCase
{
    public function testIsEnabledByDefault(): void
    {
        $altcha_manager = AltchaManager::getInstance();
        $this->assertTrue($altcha_manager->isEnabled());
    }

    public function testDefaultWidgetParameters(): void
    {
        $renderer = TemplateRenderer::getInstance();
        $html = $renderer->render('components/altcha/widget.html.twig');
        $this->assertStringContainsString('<altcha-widget', $html);
        $this->assertStringNotContainsString('auto="onload"', $html);
        $this->assertStringNotContainsString('class="d-none"', $html);
    }

    public function testValidSolution(): void
    {
        // Prepare: create a challenge.
        $altcha_manager = AltchaManager::getInstance();
        $challenge = $altcha_manager->generateChallenge();

        // Act: compute the proof of work and submit a correct solution to the
        // challenge.
        // Then, mark the challenge as resolved and send the same solution again.
        $pow = null;
        for ($i = 0; $i <= $challenge->maxNumber; $i++) {
            $digest = hash("sha256", $challenge->salt . $i);
            if (hash_equals($digest, $challenge->challenge)) {
                $pow = $i;
                break;
            }
        }
        $payload = base64_encode(json_encode([
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'number'    => $pow,
            'salt'      => $challenge->salt,
            'signature' => $challenge->signature,
        ]));
        $is_valid_1 = $altcha_manager->verifySolution($payload);
        $altcha_manager->removeChallenge($payload);
        $is_valid_2 = $altcha_manager->verifySolution($payload);

        // Assert: the proof of work should be valid but only once.
        $this->assertTrue($is_valid_1);
        $this->assertFalse($is_valid_2);
    }

    public function testInvalidSolution(): void
    {
        // Prepare: create a challenge.
        $altcha_manager = AltchaManager::getInstance();
        $challenge = $altcha_manager->generateChallenge();

        // Act: submit an incorrect solution to the challenge.
        $payload = base64_encode(json_encode([
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'number'    => GLPI_ALTCHA_MAX_NUMBER + 10, // Impossible value
            'salt'      => $challenge->salt,
            'signature' => $challenge->signature,
        ]));
        $is_valid = $altcha_manager->verifySolution($payload);

        // Assert: challenge should not be validated.
        $this->assertFalse($is_valid);
    }
}
