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

namespace tests\units\Glpi\Altcha;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\SolveChallengeOptions;
use Glpi\Altcha\AltchaManager;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Tests\GLPITestCase;

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
        // Using (new Altcha()) work because we don't actually need the HMAC key
        // from the internal altcha instance of the manager here, indeed
        // solveChallenge only does the proof of work computation that will
        // be done on the client side in production which does not require the
        // key.
        $solution = (new Altcha())->solveChallenge(new SolveChallengeOptions(
            challenge: $challenge,
            algorithm: new Pbkdf2(),
        ));

        // Fake payload to simulate what a valid client would submit
        $payload = base64_encode(json_encode([
            'challenge' => $challenge->toArray(),
            'solution'  => $solution,
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
            'challenge' => $challenge->toArray(),
            'solution' => [
                'counter'    => -1,
                'derivedKey' => 'not_a_real_key',
                'time'       => 82,
            ],
        ]));
        $is_valid = $altcha_manager->verifySolution($payload);

        // Assert: challenge should not be validated.
        $this->assertFalse($is_valid);
    }
}
