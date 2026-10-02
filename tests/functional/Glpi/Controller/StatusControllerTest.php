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

use AuthLDAP;
use Glpi\Controller\StatusController;
use Glpi\System\Status\StatusChecker;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class StatusControllerTest extends GLPITestCase
{
    public function setUp(): void
    {
        parent::setUp();
        StatusChecker::resetInstance();
    }

    public static function allServicesQueryProvider(): iterable
    {
        yield 'default service' => [
            'query_parameters' => [],
        ];

        yield 'explicit all services, case insensitive' => [
            'query_parameters' => ['service' => 'ALL'],
        ];
    }

    #[DataProvider('allServicesQueryProvider')]
    public function testAllServicesResponse(array $query_parameters): void
    {
        $status = $this->getStatusResponseContent($query_parameters);

        $this->assertSame(
            [
                'glpi'            => StatusChecker::STATUS_OK,
                'db'              => StatusChecker::STATUS_OK,
                'cas'             => StatusChecker::STATUS_NO_DATA,
                'ldap'            => StatusChecker::STATUS_NO_DATA,
                'imap'            => StatusChecker::STATUS_NO_DATA,
                'mail_collectors' => StatusChecker::STATUS_NO_DATA,
                'crontasks'       => StatusChecker::STATUS_OK,
                'filesystem'      => StatusChecker::STATUS_OK,
                'plugins'         => StatusChecker::STATUS_NO_DATA,
            ],
            array_map(
                static fn(array $service_status): string => $service_status['status'],
                $status
            )
        );
        $this->assertSame(
            [
                'status' => StatusChecker::STATUS_OK,
                'main' => [
                    'status' => StatusChecker::STATUS_OK,
                ],
                'replicas' => [
                    'status' => StatusChecker::STATUS_NO_DATA,
                    'servers' => [],
                ],
            ],
            $status['db']
        );
        $this->assertSame(['status' => StatusChecker::STATUS_NO_DATA], $status['cas']);
        $this->assertSame(
            ['status' => StatusChecker::STATUS_NO_DATA, 'servers' => []],
            $status['ldap']
        );
        $this->assertSame(
            ['status' => StatusChecker::STATUS_NO_DATA, 'servers' => []],
            $status['imap']
        );
        $this->assertSame(
            ['status' => StatusChecker::STATUS_NO_DATA, 'servers' => []],
            $status['mail_collectors']
        );
        $this->assertSame([], $status['crontasks']['stuck']);
        $this->assertIsString($status['crontasks']['status_msg']);
        $this->assertNotSame('', $status['crontasks']['status_msg']);
        $this->assertSame(
            ['status' => StatusChecker::STATUS_OK],
            $status['filesystem']['session_dir']
        );
        $this->assertSame(['status' => StatusChecker::STATUS_NO_DATA], $status['plugins']);
    }

    public function testSelectedServiceResponseIsCaseInsensitive(): void
    {
        $status = $this->getStatusResponseContent(['service' => 'DB']);

        $this->assertSame(
            [
                'status' => StatusChecker::STATUS_OK,
                'main' => [
                    'status' => StatusChecker::STATUS_OK,
                ],
                'replicas' => [
                    'status' => StatusChecker::STATUS_NO_DATA,
                    'servers' => [],
                ],
            ],
            $status
        );
    }

    public function testUnknownServiceResponseIsEmpty(): void
    {
        $this->assertSame(
            [],
            $this->getStatusResponseContent(['service' => 'unknown'])
        );
    }

    public function testResponseContainsOnlyPublicStatusInformation(): void
    {
        global $DB;

        StatusChecker::getDBStatus();
        $DB->beginTransaction();

        try {
            $private_name = 'Private LDAP server';
            $auth_ldap = new AuthLDAP();
            $auth_ldap_id = $auth_ldap->add([
                'name'            => $private_name,
                'host'            => '127.0.0.1',
                'port'            => 1,
                'timeout'         => 1,
                'is_active'       => 1,
                'rootdn'          => 'cn=Manager,dc=glpi,dc=org',
                'rootdn_passwd'   => 'secret',
            ]);
            $this->assertGreaterThan(0, $auth_ldap_id);

            $status = $this->getStatusResponseContent(['service' => 'LDAP']);
            $public_name = 'GLPI_LDAP_' . $auth_ldap_id;

            $this->assertSame(StatusChecker::STATUS_PROBLEM, $status['status']);
            $this->assertSame([$public_name], array_keys($status['servers']));
            $this->assertSame(StatusChecker::STATUS_PROBLEM, $status['servers'][$public_name]['status']);
            $this->assertArrayNotHasKey($private_name, $status['servers']);
        } finally {
            $DB->rollBack();
        }
    }

    private function getStatusResponseContent(array $query_parameters = []): array
    {
        $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;

        $controller = new StatusController();
        $response = $controller(Request::create('/status.php', 'GET', $query_parameters));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(Session::NORMAL_MODE, $_SESSION['glpi_use_mode']);

        $response_content = $response->getContent();
        $this->assertNotFalse($response_content);
        $this->assertJson($response_content);

        return json_decode($response_content, associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
