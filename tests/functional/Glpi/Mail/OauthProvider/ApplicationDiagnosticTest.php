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

namespace tests\units\Glpi\Mail\OauthProvider;

use Glpi\Mail\OauthProvider\ApplicationDiagnostic;
use Glpi\Tests\DbTestCase;
use OAuthApplication;
use OAuthAuthorization;

class ApplicationDiagnosticTest extends DbTestCase
{
    private function createApplicationWithAuthorization(): OAuthApplication
    {
        /** @var OAuthApplication $application */
        $application = $this->createItem(OAuthApplication::class, [
            'name'          => 'Test app ' . $this->getUniqueString(),
            'is_active'     => 1,
            'provider'      => OAuthApplication::AZURE,
            'client_id'     => 'my-client-id',
            'client_secret' => 'my-secret',
            'tenant_id'     => 'my-tenant',
        ], ['client_secret']);

        $this->createItem(OAuthAuthorization::class, [
            'oauthapplications_id' => $application->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
        ]);

        return $application;
    }

    /**
     * @return list<string>
     */
    private function getAuthorizationStepKeys(OAuthApplication $application): array
    {
        $keys = array_column((new ApplicationDiagnostic($application))->getPlan(), 'key');

        return array_values(array_filter($keys, static fn(string $key) => str_starts_with($key, 'auth:')));
    }

    public function testPlanIncludesAuthorizationStepsWithUpdateRight(): void
    {
        $this->login();

        $application = $this->createApplicationWithAuthorization();

        $this->assertCount(2, $this->getAuthorizationStepKeys($application));
    }

    public function testPlanOmitsAuthorizationStepsWithoutUpdateRight(): void
    {
        $this->login();

        $application = $this->createApplicationWithAuthorization();

        $_SESSION['glpiactiveprofile'][OAuthApplication::$rightname] = READ;

        $this->assertSame([], $this->getAuthorizationStepKeys($application));
    }
}
