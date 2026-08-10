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

namespace Glpi\Tools\Command;

use FilesystemIterator;
use Override;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Icon fonts expose their glyph through a CSS `::before { content: "\eXXX" }` rule. The accessible
 * name computation includes pseudo-element content, so a decorative icon placed inside an element
 * that takes its name from its contents (button, link, heading, tab, option...) injects a private
 * use area character into that name. Screen readers announce it, and it makes accessible names
 * impossible to match reliably.
 *
 * Decorative icons must therefore carry `aria-hidden="true"`. This command reports the ones that
 * do not, and can add the attribute.
 */
final class CheckDecorativeIconsCommand extends AbstractCommand
{
    /**
     * Error code returned when some decorative icons are missing `aria-hidden`.
     */
    public const ERROR_MISSING_ARIA_HIDDEN = 1;

    /**
     * Directories parsed when no `--directory` option is given, relative to the project root.
     */
    private const DEFAULT_DIRECTORIES = ['ajax', 'front', 'js', 'src', 'templates'];

    private const PARSED_EXTENSIONS = ['js', 'php', 'twig', 'vue'];

    /**
     * The icon can be fitted with `aria-hidden` without losing anything.
     */
    private const VERDICT_FIXABLE = 'fixable';

    /**
     * The icon carries an accessible name, or is the interactive control itself. Hiding it would
     * remove a name instead of a glyph, so it needs a human decision.
     */
    private const VERDICT_REVIEW = 'review';

    #[Override]
    protected function isPluginOptionAvailable(): bool
    {
        return true;
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this->setName('tools:check_decorative_icons');
        $this->setDescription('Check that decorative icons are hidden from assistive technologies.');

        $this->addOption(
            'directory',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Directory to parse (optional)',
        );

        $this->addOption(
            'fix',
            'f',
            InputOption::VALUE_NONE,
            'Add the missing `aria-hidden` attributes'
        );

        $this->addOption(
            'show-review',
            null,
            InputOption::VALUE_NONE,
            'Also list the icons that cannot be fixed automatically'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project_dir = dirname(__DIR__, 3); // Root of GLPI

        if ($this->isPluginCommand()) {
            $project_dir = $this->getPluginDirectory();
        }

        $directories = ($target_dir = $input->getOption('directory')) !== null
            ? [$target_dir]
            : array_map(
                static fn(string $dir): string => $project_dir . DIRECTORY_SEPARATOR . $dir,
                self::DEFAULT_DIRECTORIES
            );

        $fix = (bool) $input->getOption('fix');
        $fixable_count = 0;
        $review = [];
        $failed_files = [];

        foreach ($this->getFilesToParse($directories) as $filename) {
            if (($contents = file_get_contents($filename)) === false) {
                throw new \RuntimeException(sprintf('Unable to read file "%s".', $filename));
            }

            $icons = $this->findIconsToReport($contents);
            if ($icons === []) {
                continue;
            }

            $relative_path = str_replace($project_dir . DIRECTORY_SEPARATOR, '', $filename);

            foreach ($icons as $icon) {
                if ($icon['verdict'] === self::VERDICT_REVIEW) {
                    $review[] = sprintf(
                        '%s:%d %s (%s)',
                        $relative_path,
                        $icon['line'],
                        trim($icon['tag']),
                        $icon['reason']
                    );
                    continue;
                }

                $fixable_count++;
                $this->io->writeln(
                    sprintf(
                        '<fg=red>%s:%d</> %s',
                        $relative_path,
                        $icon['line'],
                        trim($icon['tag'])
                    ),
                    $fix ? OutputInterface::VERBOSITY_VERBOSE : OutputInterface::VERBOSITY_NORMAL
                );
            }

            if (!$fix) {
                continue;
            }

            $updated = $this->addAriaHidden($contents, $icons);
            if ($updated !== $contents && file_put_contents($filename, $updated) === false) {
                $failed_files[] = $relative_path;
            }
        }

        if ($input->getOption('show-review') && $review !== []) {
            $this->io->section(
                sprintf('%d icon(s) need a manual decision (name carried by the icon, or interactive icon)', count($review))
            );
            foreach ($review as $line) {
                $this->io->writeln('<fg=yellow>‣ ' . $line . '</>');
            }
        }

        if ($fixable_count === 0) {
            $this->io->success('All decorative icons are hidden from assistive technologies.');
            return 0; // Success
        }

        if (!$fix) {
            $this->io->error(
                sprintf(
                    'Found %d decorative icon(s) without `aria-hidden`. Use --fix option to fix them.',
                    $fixable_count
                )
            );
            return self::ERROR_MISSING_ARIA_HIDDEN;
        }

        if ($failed_files !== []) {
            $this->io->error(
                sprintf('%d file(s) cannot be updated: %s', count($failed_files), implode(', ', $failed_files))
            );
            return self::FAILURE;
        }

        $this->io->success(sprintf('Added `aria-hidden` on %d decorative icon(s).', $fixable_count));
        return 0; // Success
    }

    /**
     * Locate the icon elements that are not hidden yet, and qualify each of them.
     *
     * @return list<array{offset: int, length: int, tag: string, line: int, verdict: string, reason: string}>
     */
    private function findIconsToReport(string $contents): array
    {
        $icons = [];

        // `[^>]*` cannot handle an attribute value containing a `>` (a Twig ternary, typically);
        // such a tag is skipped rather than mis-parsed, and shows up in the review list instead.
        if (preg_match_all('/<(?<tag>i|span)\b(?<attrs>[^>]*?)(?<selfclose>\/)?>/i', $contents, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return [];
        }

        foreach ($matches as $match) {
            $tag = $match[0][0];
            $offset = $match[0][1];
            $attrs = $match['attrs'][0];
            $is_span = strtolower($match['tag'][0]) === 'span';

            $self_closed = ($match['selfclose'][0] ?? '') !== '';
            $tag_name = $is_span ? 'span' : 'i';

            $element_end = $this->getElementEnd($contents, $offset + strlen($tag), $self_closed, $tag_name);
            if ($element_end === null) {
                // Elements holding text are not icons, whatever their classes are.
                continue;
            }

            // An empty `<i>` is always an icon in GLPI. A `<span>` needs an explicit icon class,
            // as empty spans are also used as layout or Select2 placeholders.
            if ($is_span && !$this->hasIconClass($attrs)) {
                continue;
            }

            if (preg_match('/\baria-hidden\s*=/i', $attrs) === 1) {
                continue; // Already done.
            }
            if (preg_match('/\brole\s*=\s*.?presentation/i', $attrs) === 1) {
                continue; // Equivalent intent, already expressed.
            }

            $reason = $this->getReviewReason($attrs, $this->isSoleContentOfUnnamedControl($contents, $offset, $element_end));
            $icons[] = [
                'offset' => $offset,
                'length' => strlen($tag),
                'tag' => $tag,
                'line' => substr_count($contents, "\n", 0, $offset) + 1,
                'verdict' => $reason === null ? self::VERDICT_FIXABLE : self::VERDICT_REVIEW,
                'reason' => $reason ?? '',
            ];
        }

        return $icons;
    }

    /**
     * Return the position right after the element opened at $offset, or null when it holds text.
     */
    private function getElementEnd(string $contents, int $offset, bool $self_closed, string $tag_name): ?int
    {
        if ($self_closed) {
            return $offset;
        }

        $closing_position = stripos($contents, '</' . $tag_name, $offset);
        if ($closing_position === false) {
            return null; // Unbalanced markup, leave it alone.
        }

        if (trim(substr($contents, $offset, $closing_position - $offset)) !== '') {
            return null;
        }

        $closing_end = strpos($contents, '>', $closing_position);

        return $closing_end === false ? null : $closing_end + 1;
    }

    /**
     * Tell whether the icon is the whole content of an interactive element that has no other
     * naming source. Hiding such an icon would leave the control without any accessible name,
     * which is worse than the stray glyph: it needs a real label first.
     */
    private function isSoleContentOfUnnamedControl(string $contents, int $icon_start, int $icon_end): bool
    {
        foreach (['button', 'a', 'label'] as $parent_tag) {
            $parent = $this->findEnclosingOpeningTag($contents, $icon_start, $parent_tag);
            if ($parent === null) {
                continue;
            }

            if (trim(substr($contents, $parent['end'], $icon_start - $parent['end'])) !== '') {
                continue; // Some text precedes the icon, the control is named by its contents.
            }

            $closing_position = stripos($contents, '</' . $parent_tag, $icon_end);
            if ($closing_position === false) {
                continue;
            }

            if (trim(substr($contents, $icon_end, $closing_position - $icon_end)) !== '') {
                continue; // Some text follows the icon.
            }

            return preg_match('/\b(?:title|aria-label|aria-labelledby)\s*=/i', $parent['attrs']) !== 1;
        }

        return false;
    }

    /**
     * Find the opening tag of the closest still opened $tag_name element preceding $before.
     *
     * @return array{end: int, attrs: string}|null
     */
    private function findEnclosingOpeningTag(string $contents, int $before, string $tag_name): ?array
    {
        // Markup emitted from PHP or JS is often split across concatenated string literals, so the
        // search is bounded: beyond that, any candidate is more likely unrelated than enclosing.
        $window_start = max(0, $before - 2000);
        $window = substr($contents, $window_start, $before - $window_start);

        if (preg_match_all('/<' . $tag_name . '\b(?<attrs>[^>]*)>/i', $window, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return null;
        }

        $last = end($matches);
        $opening_end = $window_start + $last[0][1] + strlen($last[0][0]);

        $closing_position = stripos($contents, '</' . $tag_name, $opening_end);
        if ($closing_position !== false && $closing_position < $before) {
            return null; // That element is already closed, it does not enclose the icon.
        }

        return ['end' => $opening_end, 'attrs' => $last['attrs'][0]];
    }

    private function hasIconClass(string $attrs): bool
    {
        if (preg_match('/\bclass\s*=\s*(?<quote>["\'])(?<value>.*?)\g{quote}/is', $attrs, $matches) !== 1) {
            return false;
        }

        // `icon` also covers the class names built from a variable: `getIcon()`, `item.icon`,
        // `resolvedIcon`, `itemtype_icon`, as well as the `main-icon` / `alert-icon` families.
        return preg_match('/\bti-|\bfa-|\bfa[srlbdk]?\b|icon/i', $matches['value']) === 1;
    }

    /**
     * Return why the icon needs a human decision, or null when it can safely be hidden.
     */
    private function getReviewReason(string $attrs, bool $names_its_control): ?string
    {
        $carries_a_name = preg_match('/\b(?:title|aria-label|aria-labelledby|alt)\s*=/i', $attrs) === 1;

        // An icon that is the control itself is reported as such first: the name it carries is a
        // consequence of that, not the problem to solve.
        if (preg_match('/\b(?:onclick|href|tabindex|contenteditable|data-bs-toggle=(?![\'"]?tooltip)|v-on:click|@click)/i', $attrs) === 1) {
            return 'is the interactive control itself, it should become a real button';
        }

        if (
            preg_match('/\bclass\s*=\s*(?<quote>["\'])(?<value>.*?)\g{quote}/is', $attrs, $matches) === 1
            && preg_match('/\b(?:btn|pointer|cursor-pointer)\b/', $matches['value']) === 1
        ) {
            return 'is styled as an interactive control, it should become a real button';
        }

        if ($names_its_control) {
            return $carries_a_name
                // Hiding the icon would drop its title too: an aria-hidden subtree contributes
                // nothing to the accessible name computation.
                ? 'holds the only name of its control, move that name up to the control itself'
                : 'is the only content of a control that has no name at all, that control needs a label first';
        }

        if ($carries_a_name) {
            return 'carries a name of its own, replace it with a visually hidden text if it carries meaning';
        }

        return null;
    }

    /**
     * @param list<array{offset: int, length: int, tag: string, line: int, verdict: string, reason: string}> $icons
     */
    private function addAriaHidden(string $contents, array $icons): string
    {
        // Walk backwards so that the offsets computed above stay valid.
        foreach (array_reverse($icons) as $icon) {
            if ($icon['verdict'] !== self::VERDICT_FIXABLE) {
                continue;
            }

            $tag = $icon['tag'];
            // Reuse the quoting style already used in the tag: the markup often lives inside a PHP
            // or JS string literal, and the wrong quote would break it.
            $quote = preg_match('/=\s*(?<quote>["\'])/', $tag, $matches) === 1 ? $matches['quote'] : '"';

            // Insert right after the last attribute, i.e. before the closing `>` and the `/` of a
            // self closing tag.
            $body = rtrim(substr($tag, 0, -1));
            if (str_ends_with($body, '/')) {
                $body = rtrim(substr($body, 0, -1));
            }
            $insert_at = strlen($body);

            $updated_tag = substr($tag, 0, $insert_at)
                . sprintf(' aria-hidden=%1$strue%1$s', $quote)
                . substr($tag, $insert_at);

            $contents = substr_replace($contents, $updated_tag, $icon['offset'], $icon['length']);
        }

        return $contents;
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    private function getFilesToParse(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($directory, FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS),
                    static function (SplFileInfo $file): bool {
                        if ($file->isDir()) {
                            // Third party code, and build artifacts.
                            return !in_array($file->getFilename(), ['lib', 'node_modules', 'vendor'], true);
                        }
                        return in_array(strtolower($file->getExtension()), self::PARSED_EXTENSIONS, true);
                    }
                )
            );

            foreach ($iterator as $file) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
