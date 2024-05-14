<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace tests\units\Glpi\Form\AccessControl;

use Glpi\Session\SessionInfo;
use GLPITestCase;

class FormAccessParameters extends GLPITestCase
{
    public function testGetSessionInfo(): void
    {
        $form_access_parameters = new \Glpi\Form\AccessControl\FormAccessParameters(
            session_info: $this->getDummySessionInfo(),
            url_parameters: []
        );

        $this->object(
            $form_access_parameters->getSessionInfo()
        )->isEqualTo($this->getDummySessionInfo());
    }

    public function testGetUrlParameters(): void
    {
        $form_access_parameters = new \Glpi\Form\AccessControl\FormAccessParameters(
            session_info: $this->getDummySessionInfo(),
            url_parameters: ['token' => 'my_token']
        );

        $this->array(
            $form_access_parameters->getUrlParameters()
        )->isEqualTo(['token' => 'my_token']);
    }

    public function isAuthenticatedProvider(): iterable
    {
        $parameters_without_session = new \Glpi\Form\AccessControl\FormAccessParameters(
            session_info: null,
            url_parameters: []
        );
        $parameters_with_session = new \Glpi\Form\AccessControl\FormAccessParameters(
            session_info: $this->getDummySessionInfo(),
            url_parameters: []
        );

        return [
            'Without session' => [$parameters_without_session, false],
            'With session'    => [$parameters_with_session, true],
        ];
    }

    /**
     * @dataProvider isAuthenticatedProvider
     */
    public function testIsAuthenticated(
        \Glpi\Form\AccessControl\FormAccessParameters $form_access_parameters,
        bool $expected
    ): void {
        $this->boolean(
            $form_access_parameters->isAuthenticated()
        )->isEqualTo($expected);
    }

    private function getDummySessionInfo(): SessionInfo
    {
        return new SessionInfo(
            user_id: 1,
            group_ids: [2, 3],
            profile_id: 4,
        );
    }
}
