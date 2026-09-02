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
    public function test_alert_colors(): void
    {
        $this->assertSame('info', (new Alert())->color);
        $this->assertSame('info', (new Info())->color);
        $this->assertSame('success', (new Success())->color);
        $this->assertSame('warning', (new Warning())->color);
        $this->assertSame('danger', (new Danger())->color);
    }

    public function test_resolvedIcon_uses_custom_icon(): void
    {
        $alert = new Alert();
        $alert->color = 'info';
        $alert->icon = 'ti ti-custom-star';

        $this->assertSame('ti ti-custom-star', $alert->getResolvedIcon());
    }

    public static function provideResolvedIconDefaults(): \Generator
    {
        yield 'success' => ['success', 'ti ti-check'];
        yield 'warning' => ['warning', 'ti ti-alert-triangle'];
        yield 'danger'  => ['danger', 'ti ti-exclamation-circle'];
        yield 'info'    => ['info', 'ti ti-info-circle'];
        yield 'default' => ['secondary', 'ti ti-info-circle'];
    }

    /**
     * @param 'primary'|'secondary'|'success'|'danger'|'warning'|'info'|'light'|'dark' $color
     */
    #[DataProvider('provideResolvedIconDefaults')]
    public function test_resolvedIcon_defaults_per_color(string $color, string $expected_icon): void
    {
        $alert = new Alert();
        $alert->color = $color;

        $this->assertSame($expected_icon, $alert->getResolvedIcon());
    }

    public function test_getClasses(): void
    {
        $alert = new Alert();
        $alert->color = 'info';
        $this->assertSame('alert alert-info', $alert->getClasses());

        $alert->dismissible = true;
        $this->assertSame('alert alert-info alert-dismissible fade show', $alert->getClasses());

        $alert->dismissible = false;
        $alert->important = true;
        $this->assertSame('alert alert-info alert-important', $alert->getClasses());

        $alert->dismissible = true;
        $this->assertSame('alert alert-info alert-dismissible fade show alert-important', $alert->getClasses());
    }
}
