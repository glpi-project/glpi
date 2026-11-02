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

namespace tests\functional\Tools\Command;

use Glpi\Tests\GLPITestCase;
use Glpi\Tools\Command\CheckDecorativeIconsCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\CommandTester;

class CheckDecorativeIconsCommandTest extends GLPITestCase
{
    private string $test_dir;

    public function setUp(): void
    {
        parent::setUp();
        $this->test_dir = sys_get_temp_dir() . '/glpi_test_icons_' . uniqid();
        if (!mkdir($this->test_dir) && !is_dir($this->test_dir)) {
            $this->markTestSkipped('Could not create temp directory');
        }
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->test_dir);
        parent::tearDown();
    }

    public static function fixableProvider(): iterable
    {
        yield 'icon next to a text label' => [
            'template.html.twig',
            '<button type="button"><i class="ti ti-plus"></i><span>{{ __(\'Add\') }}</span></button>',
            '<button type="button"><i class="ti ti-plus" aria-hidden="true"></i><span>{{ __(\'Add\') }}</span></button>',
        ];

        yield 'icon of a named control' => [
            'template.html.twig',
            '<button type="button" aria-label="Add"><i class="ti ti-plus"></i></button>',
            '<button type="button" aria-label="Add"><i class="ti ti-plus" aria-hidden="true"></i></button>',
        ];

        yield 'standalone icon' => [
            'template.html.twig',
            '<span class="badge"><i class="ti ti-lock"></i> locked</span>',
            '<span class="badge"><i class="ti ti-lock" aria-hidden="true"></i> locked</span>',
        ];

        yield 'icon class built from a variable' => [
            'template.html.twig',
            '<a href="#"><i class="{{ item.icon }}"></i>{{ item.name }}</a>',
            '<a href="#"><i class="{{ item.icon }}" aria-hidden="true"></i>{{ item.name }}</a>',
        ];

        yield 'self closing icon' => [
            'component.vue',
            '<button :aria-label="__(\'Add\')"><i class="ti ti-plus" /></button>',
            '<button :aria-label="__(\'Add\')"><i class="ti ti-plus" aria-hidden="true" /></button>',
        ];

        yield 'multi line tag' => [
            'template.html.twig',
            "<button aria-label=\"Add\">\n    <i\n        class=\"ti ti-plus\"\n    ></i>\n</button>",
            "<button aria-label=\"Add\">\n    <i\n        class=\"ti ti-plus\" aria-hidden=\"true\"\n    ></i>\n</button>",
        ];
    }

    #[DataProvider('fixableProvider')]
    public function testFixableIconsAreUpdated(string $filename, string $contents, string $expected): void
    {
        file_put_contents($this->test_dir . '/' . $filename, $contents);

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir, '--fix' => true]);

        $this->assertStringContainsString('Added `aria-hidden` on 1 decorative icon(s).', $tester->getDisplay());
        $this->assertEquals($expected, file_get_contents($this->test_dir . '/' . $filename));
    }

    /**
     * The markup often lives inside a PHP or JS string literal: reusing the wrong quoting style
     * would break the file.
     */
    public function testQuotingStyleIsPreserved(): void
    {
        file_put_contents(
            $this->test_dir . '/legacy.php',
            "<?php \$out = \"<button title='x'><i class='ti ti-plus'></i></button>\";"
        );

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir, '--fix' => true]);

        $this->assertEquals(
            "<?php \$out = \"<button title='x'><i class='ti ti-plus' aria-hidden='true'></i></button>\";",
            file_get_contents($this->test_dir . '/legacy.php')
        );
    }

    public static function untouchedProvider(): iterable
    {
        yield 'already hidden' => [
            '<button aria-label="Add"><i class="ti ti-plus" aria-hidden="true"></i></button>',
        ];

        yield 'flagged as presentation' => [
            '<button aria-label="Add"><i class="ti ti-plus" role="presentation"></i></button>',
        ];

        yield 'icon holding the only name of its control' => [
            '<button type="button"><i class="ti ti-plus" title="Add"></i></button>',
        ];

        yield 'icon being the only content of an unnamed control' => [
            '<button type="button"><i class="ti ti-plus"></i></button>',
        ];

        yield 'interactive icon' => [
            '<i class="ti ti-plus" onclick="doSomething()"></i>',
        ];

        yield 'icon styled as a control' => [
            '<i class="ti ti-plus btn btn-sm"></i>',
        ];

        yield 'element holding text is not an icon' => [
            '<i>emphasis</i>',
        ];

        yield 'empty span without an icon class' => [
            '<span class="separator"></span>',
        ];
    }

    #[DataProvider('untouchedProvider')]
    public function testNonDecorativeMarkupIsLeftAlone(string $contents): void
    {
        file_put_contents($this->test_dir . '/template.html.twig', $contents);

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir, '--fix' => true]);

        $this->assertEquals($contents, file_get_contents($this->test_dir . '/template.html.twig'));
    }

    public function testCheckModeReportsWithoutWriting(): void
    {
        $contents = '<button aria-label="Add"><i class="ti ti-plus"></i></button>';
        file_put_contents($this->test_dir . '/template.html.twig', $contents);

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir]);

        $this->assertStringContainsString('template.html.twig:1', $tester->getDisplay());
        $this->assertEquals(CheckDecorativeIconsCommand::ERROR_MISSING_ARIA_HIDDEN, $tester->getStatusCode());
        $this->assertEquals($contents, file_get_contents($this->test_dir . '/template.html.twig'));
    }

    public function testReviewListIsOnlyShownOnDemand(): void
    {
        file_put_contents(
            $this->test_dir . '/template.html.twig',
            '<button type="button"><i class="ti ti-plus" title="Add"></i></button>'
        );

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir]);
        $this->assertStringNotContainsString('[names-its-control]', $tester->getDisplay());
        $this->assertEquals(0, $tester->getStatusCode());

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir, '--show-review' => null]);
        $this->assertStringContainsString('[names-its-control]', $tester->getDisplay());
        $this->assertEquals(0, $tester->getStatusCode());
    }

    public function testReviewListCanBeFilteredByCategory(): void
    {
        file_put_contents(
            $this->test_dir . '/template.html.twig',
            '<button type="button"><i class="ti ti-plus" title="Add"></i></button>'
            . '<i class="ti ti-x" onclick="close()"></i>'
        );

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir, '--show-review' => 'interactive']);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('[interactive]', $display);
        $this->assertStringNotContainsString('[names-its-control]', $display);
    }

    public function testReviewCountsAreSummarisedAfterTheListing(): void
    {
        file_put_contents(
            $this->test_dir . '/named.html.twig',
            '<button type="button"><i class="ti ti-plus" title="Add"></i></button>'
        );
        file_put_contents(
            $this->test_dir . '/clickable.html.twig',
            '<i class="ti ti-x" onclick="close()"></i>'
        );

        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir, '--show-review' => 'interactive']);

        $display = $tester->getDisplay();

        // The counts cover every category, even the ones the filter left out.
        $this->assertStringContainsString('2 icon(s) need a manual decision.', $display);
        $this->assertStringContainsString('interactive: 1, names-its-control: 1', $display);
        $this->assertGreaterThan(
            strpos($display, '[interactive]'),
            strpos($display, 'need a manual decision.')
        );
    }

    public function testUnknownReviewCategoryIsRejected(): void
    {
        $tester = new CommandTester(new CheckDecorativeIconsCommand());

        $this->expectException(InvalidOptionException::class);
        $tester->execute(['--directory' => $this->test_dir, '--show-review' => 'not-a-category']);
    }

    public function testSuccessOnCleanDirectory(): void
    {
        $tester = new CommandTester(new CheckDecorativeIconsCommand());
        $tester->execute(['--directory' => $this->test_dir]);

        $this->assertStringContainsString(
            '[OK] All decorative icons are hidden from assistive technologies.',
            $tester->getDisplay()
        );
        $this->assertEquals(0, $tester->getStatusCode());
    }
}
