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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AlertRenderingTest extends GLPITestCase
{
    private function render(string $template): string
    {
        return TemplateRenderer::getInstance()->renderFromStringTemplate($template);
    }

    public static function provideAlertTypeCssClass(): \Generator
    {
        yield 'info'    => ['info',    'alert-info'];
        yield 'warning' => ['warning', 'alert-warning'];
        yield 'danger'  => ['danger',  'alert-danger'];
        yield 'success' => ['success', 'alert-success'];
    }

    #[DataProvider('provideAlertTypeCssClass')]
    public function test_renders_correct_css_class_for_type(string $type, string $expected_class): void
    {
        $html = $this->render("{{ component('Alert', {type: '$type'}) }}");
        $this->assertStringContainsString($expected_class, $html);
    }

    public function test_renders_custom_icon_when_provided(): void
    {
        $html = $this->render("{{ component('Alert', {icon: 'ti ti-custom-star'}) }}");
        $this->assertStringContainsString('ti-custom-star', $html);
        $this->assertStringNotContainsString('ti-info-circle', $html);
    }

    // -------------------------------------------------------------------------
    // Variant components (Alert:Info, Alert:Warning, ...)
    // -------------------------------------------------------------------------

    public function test_Alert_Info_variant(): void
    {
        $html = $this->render("{{ component('Alert:Info') }}");
        $this->assertStringContainsString('alert-info', $html);
    }

    public function test_Alert_Warning_variant(): void
    {
        $html = $this->render("{{ component('Alert:Warning') }}");
        $this->assertStringContainsString('alert-warning', $html);
    }

    public function test_Alert_Danger_variant(): void
    {
        $html = $this->render("{{ component('Alert:Danger') }}");
        $this->assertStringContainsString('alert-danger', $html);
    }

    public function test_Alert_Success_variant(): void
    {
        $html = $this->render("{{ component('Alert:Success') }}");
        $this->assertStringContainsString('alert-success', $html);
    }

    public function test_twig_tag_syntax_renders_alert(): void
    {
        $html = $this->render('<twig:Alert title="My info alert" />');
        $this->assertStringContainsString('alert-info', $html);
        $this->assertStringContainsString('My info alert', $html);

        $html = $this->render('<twig:Alert:Warning type="warning"');
        $this->assertStringContainsString('alert-warning', $html);
    }

    public function test_twig_tag_syntax_renders_alert_with_overloaded_blocks(): void
    {
        $content = "
        <twig:Alert>
            <twig:block name='title'>
                <h3 class='alert-title bg-green'>
                    We can also be more like a vue/nuxt component
                </h3>
            </twig:block>

            <div>
                My content in more like a twig logic
            </div>
        </twig:Alert>
        ";

        $html = $this->render($content);
        $this->assertStringContainsString('alert-title bg-green', $html);
        $this->assertStringContainsString('My content in more like a twig logic', $html);

    }
}
