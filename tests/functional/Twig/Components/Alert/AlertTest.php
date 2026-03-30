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

namespace tests\units\Twig\Components\Alert;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Components\Alert\Alert;
use Twig\Components\Alert\Danger;
use Twig\Components\Alert\Info;
use Twig\Components\Alert\Success;
use Twig\Components\Alert\Warning;

class AlertTest extends TestCase
{
    public function test_alert_types(): void
    {
        $this->assertSame('info', (new Alert())->type);
        $this->assertSame('info', (new Info())->type);
        $this->assertSame('success', (new Success())->type);
        $this->assertSame('warning', (new Warning())->type);
        $this->assertSame('danger', (new Danger())->type);
    }

    public function test_resolvedIcon_uses_custom_icon(): void
    {
        $alert = new Alert();
        $alert->type = 'info';
        $alert->icon = 'ti ti-custom-star';

        $this->assertSame('ti ti-custom-star', $alert->getResolvedIcon());
    }
}
