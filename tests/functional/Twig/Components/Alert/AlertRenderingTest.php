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

    public static function provideAlertColorCssClass(): \Generator
    {
        yield 'info'    => ['info',    'alert-info'];
        yield 'warning' => ['warning', 'alert-warning'];
        yield 'danger'  => ['danger',  'alert-danger'];
        yield 'success' => ['success', 'alert-success'];
    }

    #[DataProvider('provideAlertColorCssClass')]
    public function test_renders_correct_css_class_for_color(string $color, string $expected_class): void
    {
        $html = $this->render("{{ component('Alert', {color: '$color'}) }}");
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
        $html = $this->render('<twig:Alert heading="My info alert" />');
        $this->assertStringContainsString('alert-info', $html);
        $this->assertStringContainsString('My info alert', $html);

        $html = $this->render('<twig:Alert:Warning />');
        $this->assertStringContainsString('alert-warning', $html);
    }

    public function test_twig_tag_syntax_renders_alert_with_overloaded_blocks(): void
    {
        $content = "
        <twig:Alert>
            <twig:block name='heading'>
                <h3 class='alert-heading bg-green'>
                    We can also be more like a vue/nuxt component
                </h3>
            </twig:block>

            <div>
                My content in more like a twig logic
            </div>
        </twig:Alert>
        ";

        $html = $this->render($content);
        $this->assertStringContainsString('alert-heading bg-green', $html);
        $this->assertStringContainsString('My content in more like a twig logic', $html);

    }

    public function test_attributes_are_forwarded(): void
    {
        $html = $this->render('<twig:Alert class="mb-0" id="my-alert" data-foo="bar" />');
        $this->assertStringContainsString('class="alert alert-info mb-0"', $html);
        $this->assertStringContainsString('id="my-alert"', $html);
        $this->assertStringContainsString('data-foo="bar"', $html);
    }

    public function test_dismissible_renders_close_button(): void
    {
        $html = $this->render('<twig:Alert :dismissible="true" />');
        $this->assertStringContainsString('alert-dismissible', $html);
        $this->assertStringContainsString('fade', $html);
        $this->assertStringContainsString('show', $html);
        $this->assertStringContainsString('btn-close', $html);
        $this->assertStringContainsString('data-bs-dismiss="alert"', $html);
    }

    public function test_close_block_can_be_overridden(): void
    {
        $content = "
        <twig:Alert :dismissible='true'>
            <twig:block name='close'>
                <button type='button' class='my-custom-close'></button>
            </twig:block>
        </twig:Alert>
        ";

        $html = $this->render($content);
        $this->assertStringContainsString('my-custom-close', $html);
        $this->assertStringNotContainsString('btn-close', $html);
    }

    public function test_icon_block_can_be_emptied(): void
    {
        $content = "
        <twig:Alert>
            <twig:block name='icon'></twig:block>
        </twig:Alert>
        ";

        $html = $this->render($content);
        $this->assertStringNotContainsString('alert-icon', $html);
    }

    public function test_heading_uses_alert_heading_class(): void
    {
        $html = $this->render('<twig:Alert heading="My heading" />');
        $this->assertStringContainsString('<h4 class="alert-heading">My heading</h4>', $html);
    }

    public function test_content_uses_alert_description_class(): void
    {
        $html = $this->render('<twig:Alert content="My content" />');
        $this->assertStringContainsString('<div class="alert-description">My content</div>', $html);
    }
}
