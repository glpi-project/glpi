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

namespace Glpi\Console\Patch;

use Exception;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Build\CompileScssCommand;
use Glpi\Console\Cache\ClearCommand;
use Glpi\Patch\ApplyStatus;
use Glpi\Patch\DiffFetcher;
use Glpi\Patch\FileApplyResult;
use Glpi\Patch\PatchApplier;
use Glpi\Patch\PathRewriter;
use Override;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Safe\unlink;

/**
 * Applies a GLPI (or plugin) pull request patch directly to the filesystem.
 *
 * Usage examples
 * --------------
 *   # Apply a GLPI core fix from a GitHub PR URL
 *   bin/console patch:apply https://github.com/glpi-project/glpi/pull/1234
 *
 *   # Apply a local diff file
 *   bin/console patch:apply /tmp/my-fix.diff
 *
 *   # Apply a plugin patch
 *   bin/console patch:apply https://github.com/glpi-network/approvalbymail/pull/56 --plugin=approvalbymail
 *
 *   # Test without writing anything
 *   bin/console patch:apply https://github.com/glpi-project/glpi/pull/1234 --dry-run
 *
 * How it works
 * ------------
 * The command downloads (or reads) a unified diff, then applies it directly
 * using pure PHP.  No external tools (git, patch, filterdiff …) are needed.
 * GLPI-specific path remapping is done automatically:
 *   - js/* and lib/* → public/js/* and public/lib/*
 *   - tests/* is always skipped (not deployed in production)
 *   - .vue source files are skipped by default (require a front-end rebuild)
 *
 * After applying, the command tells you if you also need to:
 *   - Clear the server cache  (cache:clear)
 *   - Recompile SCSS          (build:compile_scss)
 *   - Clear your browser cache
 */
final class ApplyCommand extends AbstractCommand
{
    protected $requires_db           = false;
    protected $requires_db_up_to_date = false;

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this->setName('patch:apply');
        $this->setDescription(
            'Build and apply a patch to the core or a plugin using a github pull request url or a diff file.'
        );

        $this->setHelp(<<<'HELP'
            The <info>patch:apply</info> command downloads a pull request diff from GitHub (or reads
            a local .diff file) and applies it directly to the core or a plugin.

            Before applying the patch, it does all the following modifications to the raw diff content
            to adapt it to production GLPI installations:
            - Remaps paths for asset folders: js/, css/ and lib/ → public/js/, public/css and public/lib/
            - Skips any file under tests/, tools/, phpunit/, and CHANGELOG.md

            If the patch contains .vue files, the command is aborted. This is because .vue files require a front-end rebuild.
            If the patch contains .js files, the command will automatically delete the corresponding .min.js files.
            If the patch contains .css files, the command will automatically recompile the SCSS files after applying the patch.
            If the patch contains changes to templates/, the command will automatically clear the cache after applying the patch.

            Usage examples:

            <info>bin/console patch:apply <PR URL> </info>
            <info>bin/console patch:apply <DIFF URL> </info>

            Or use a local diff file:

            <info>bin/console patch:apply <file path> </info>

            You can also target a plugin:

            <info>bin/console patch:apply <PR URL> --plugin=<plugin name> </info>

            Use <comment>--dry-run</comment> to see what would happen without writing anything:

            <info>bin/console patch:apply <PR URL> --dry-run</info>

            Use <comment>--revert</comment> to reverse a previously applied patch:

            <info>bin/console patch:apply <PR URL> --revert</info>

            Both options can be combined to simulate a revert without writing anything:

            <info>bin/console patch:apply <PR URL> --revert --dry-run</info>
        HELP);

        $this->addArgument(
            'input',
            InputArgument::REQUIRED,
            implode("\n", [
                'Where to get the patch from. Accepted formats:',
                '  - GitHub PR URL  : https://github.com/glpi-project/glpi/pull/1234',
                '  - Diff file URL  : https://patch-diff.githubusercontent.com/glpi-project/glpi/pull/1234.diff',
                '  - Local file     : /path/to/my-fix.diff',
            ])
        );

        $this->addOption(
            'plugin',
            'p',
            InputOption::VALUE_REQUIRED,
            'Name of the plugin to patch (directory name under plugins/ or marketplace/).'
            . ' Leave empty to patch GLPI core.'
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Simulate the patch: show what would be changed without writing any file.'
        );

        $this->addOption(
            'revert',
            'r',
            InputOption::VALUE_NONE,
            'Revert the patch instead of applying it: removes added lines and restores removed lines.'
        );
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $raw_input */
        $raw_input = $input->getArgument('input');

        $plugin_name = $input->getOption('plugin')??'';
        $dry_run     = (bool) $input->getOption('dry-run');
        $revert      = (bool) $input->getOption('revert');

        // ------------------------------------------------------------------
        // Banner
        // ------------------------------------------------------------------
        if ($dry_run && $revert) {
            $io->note('DRY-RUN + REVERT mode is active - simulating patch revert without writing any files.');
        } elseif ($dry_run) {
            $io->note('DRY-RUN mode is active - no files will be changed.');
        } elseif ($revert) {
            $io->note('REVERT mode is active - the patch will be reversed.');
        }

        // ------------------------------------------------------------------
        // Find and Validate plugin directory when --plugin is used
        // ------------------------------------------------------------------
        $plugin_dir = '';
        if ($plugin_name !== '') {
            $marketplace_plugin_dir = GLPI_ROOT . '/marketplace/' . $plugin_name;
            $plugins_plugin_dir = GLPI_ROOT . '/plugins/' . $plugin_name;

            $is_installed_in_marketplace = is_dir($marketplace_plugin_dir);
            $is_installed_in_plugins = is_dir($plugins_plugin_dir);

            if (!$is_installed_in_marketplace && !$is_installed_in_plugins) {
                $io->error([
                    "Plugin directory not found in either plugins/ or marketplace/.",
                    'Please make sure the plugin folder exists in one of these directories.',
                ]);
                return Command::FAILURE;
            }

            if ($is_installed_in_marketplace && $is_installed_in_plugins) {
                $io->error([
                    "Plugin exists in both plugins/ and marketplace/.",
                    'Please make sure the plugin is installed in only one of these directories.',
                ]);
                return Command::FAILURE;
            }

            //set the plugin directory for path rewriting
            $plugin_dir = $is_installed_in_marketplace
                          ? $marketplace_plugin_dir
                          : $plugins_plugin_dir;
        }

        // ------------------------------------------------------------------
        // Fetch the diff
        // ------------------------------------------------------------------
        $io->text('Fetching patch…');

        try {
            $diff_content = (new DiffFetcher())->fetch($raw_input);
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if (trim($diff_content) === '') {
            $io->error('The patch is empty. Nothing to apply.');
            return Command::FAILURE;
        }

        // ------------------------------------------------------------------
        // Determine the source description for user messages
        // ------------------------------------------------------------------
        $source_label = $this->buildSourceLabel($raw_input);
        if ($plugin_name !== '') {
            $source_label .= " (plugin: $plugin_name)";
        }
        $io->text("Source: $source_label");

        // ------------------------------------------------------------------
        // Apply
        // ------------------------------------------------------------------
        $rewriter = new PathRewriter($plugin_dir !== '' ? $plugin_dir : GLPI_ROOT);
        $applier  = new PatchApplier($rewriter);

        try {
            $results = $applier->apply($diff_content, $dry_run, false, $revert);
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($results === []) {
            $io->warning('No file changes were found in this patch.');
            return Command::SUCCESS;
        }

        // ------------------------------------------------------------------
        // Display results table
        // ------------------------------------------------------------------
        $this->renderResultsTable($output, $results);

        // ------------------------------------------------------------------
        // Display Summary counts
        // ------------------------------------------------------------------
        $this->renderSummaryCounts($output, $results);

        // ------------------------------------------------------------------
        // Conflicts: show details and fail if any
        // ------------------------------------------------------------------
        $conflicts = array_filter($results, fn(FileApplyResult $r) => $r->status === ApplyStatus::Conflict);
        if ($conflicts !== []) {
            $io->section('Conflict details');
            foreach ($conflicts as $result) {
                $io->error([
                    $result->display_path,
                    $result->message,
                ]);
            }
            return Command::FAILURE;
        }

        // ------------------------------------------------------------------
        // Performe Post-apply actions
        // ------------------------------------------------------------------
        if (!$dry_run) {
            $post_actions_result = $this->performePostApplyActions($results, $io, $input, $output);
            if ($post_actions_result === true) {
                $io->text('Please clear your browser cache.');
                $io->success($revert ? 'Patch reverted successfully!' : 'Patch applied successfully!');
            } else {
                $io->warning(
                    ($revert ? 'Patch reverted' : 'Patch applied')
                    . ', but some post-apply actions could not be performed automatically. Please check the messages above.'
                );
            }
        } else {
            $io->success(
                $revert
                    ? 'Dry-run complete. Re-run without --dry-run to revert the changes.'
                    : 'Dry-run complete. Re-run without --dry-run to apply the changes.'
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Determines and performs necessary actions after applying the patch.
     * Actions include:
     *  - Deleting .min.js files corresponding to changed .js files in public/
     *  - Recompiling SCSS if any .css or .scss file in public/ was changed
     *  - Clearing cache if any file under templates/ was changed
     *
     * @param FileApplyResult[] $results The results of applying the patch, used to determine necessary post-actions.
     * @return bool True if all post-actions were performed successfully, false if any action failed
     */
    private function performePostApplyActions(
        array $results,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output
    ): bool {
        $changed_files = array_filter(
            $results,
            fn(FileApplyResult $r) => in_array($r->status, [ApplyStatus::Applied, ApplyStatus::Reverted, ApplyStatus::Created, ApplyStatus::Deleted], true)
        );

        if (count($changed_files) === 0) {
            return true;
        }

        $paths = array_column($changed_files, 'display_path');
        $recompile_css = false;
        $clear_cache = false;
        $error_count = 0;
        foreach ($paths as $path) {
            if (str_contains($path, 'public/')) {

                // .js files in public/ → delete the corresponding .min.js file
                if (
                    str_ends_with($path, '.js')
                    && !str_ends_with($path, '.min.js')
                ) {
                    $min = str_replace('.js', '.min.js', $path);

                    //delete the minified file if it exists
                    if (file_exists($min)) {
                        try {
                            unlink($min);
                        } catch (Exception $e) {
                            $io->warning("Could not delete minified file: $min. Please check permissions.");
                            $error_count++;
                        }
                    }
                }
                // if it's a .css file in public/, we need to recompile the SCSS files
                elseif (str_ends_with($path, '.css') || str_ends_with($path, '.scss')) {
                    $recompile_css = true;
                }
            } elseif (str_contains($path, 'templates/')) {
                $clear_cache = true;
            }
        }

        if ($clear_cache) {
            try {
                (new ClearCommand())->execute($input, $output);
            } catch (Exception $e) {
                $io->warning("Patch applied, but failed to clear cache automatically: " . $e->getMessage());
                $error_count++;
            }
        }
        if ($recompile_css) {
            try {
                (new CompileScssCommand())->execute($input, $output);
            } catch (Exception $e) {
                $io->warning("Patch applied, but failed to recompile SCSS automatically: " . $e->getMessage());
                $error_count++;
            }
        }

        return $error_count === 0;
    }

    // -------------------------------------------------------------------------
    // Output helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a short human-readable label describing where the patch came from.
     */
    private function buildSourceLabel(string $input): string
    {
        $parsed = (new DiffFetcher())->parseGitHubPrUrl($input);
        if ($parsed !== null) {
            [$owner, $repo, $pr_num] = $parsed;
            return "GitHub PR #$pr_num ($owner/$repo)";
        }

        if (file_exists($input)) {
            return 'Local file: ' . basename($input);
        }

        return $input;
    }

    /**
     * Renders a table with one row per changed file.
     *
     * @param FileApplyResult[] $results
     */
    private function renderResultsTable(OutputInterface $output, array $results): void
    {
        $table = new Table($output);
        $table->setHeaders(['File', 'Status', 'Details']);
        $table->setColumnMaxWidth(0, 60);
        $table->setColumnMaxWidth(2, 55);

        // Sort results so conflicts appear first, then changes, then skipped
        $display_order = [
            ApplyStatus::Conflict->name       => 0,
            ApplyStatus::Applied->name        => 1,
            ApplyStatus::Reverted->name       => 1,
            ApplyStatus::Created->name        => 1,
            ApplyStatus::Deleted->name        => 1,
            ApplyStatus::DryRun->name         => 2,
            ApplyStatus::AlreadyApplied->name => 3,
            ApplyStatus::Skipped->name        => 4,
        ];
        usort($results, static fn(FileApplyResult $a, FileApplyResult $b): int => ($display_order[$a->status->name] ?? 9) <=> ($display_order[$b->status->name] ?? 9));

        $rows = [];
        foreach ($results as $result) {
            $rows[] = [
                $result->display_path,
                $this->formatStatus($result->status),
                $result->message,
            ];
        }

        $table->setRows($rows);
        $output->writeln('');
        $table->render();
    }

    private function formatStatus(ApplyStatus $status): string
    {
        return match ($status) {
            ApplyStatus::Applied        => '<info>APPLIED</info>',
            ApplyStatus::Reverted       => '<info>REVERTED</info>',
            ApplyStatus::Created        => '<info>CREATED</info>',
            ApplyStatus::Deleted        => '<info>DELETED</info>',
            ApplyStatus::AlreadyApplied => '<comment>ALREADY APPLIED</comment>',
            ApplyStatus::DryRun         => '<comment>DRY-RUN</comment>',
            ApplyStatus::Skipped        => 'SKIPPED',
            ApplyStatus::Conflict       => '<error>CONFLICT</error>',
        };
    }

    /**
     * Renders a summary of how many files were applied, reverted, created, deleted, skipped, etc.
     *
     * @param FileApplyResult[] $results The results of applying the patch, used to count the outcomes.
     */
    private function renderSummaryCounts(OutputInterface $output, array $results): void
    {
        $counts = [];
        foreach ($results as $result) {
            $key = $result->status->name;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $summary_parts = [];
        if (($counts[ApplyStatus::Applied->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('<info>%d applied</info>', $counts[ApplyStatus::Applied->name]);
        }
        if (($counts[ApplyStatus::Reverted->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('<info>%d reverted</info>', $counts[ApplyStatus::Reverted->name]);
        }
        if (($counts[ApplyStatus::Created->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('<info>%d created</info>', $counts[ApplyStatus::Created->name]);
        }
        if (($counts[ApplyStatus::Deleted->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('<info>%d deleted</info>', $counts[ApplyStatus::Deleted->name]);
        }
        if (($counts[ApplyStatus::DryRun->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('<comment>%d would be changed (dry-run)</comment>', $counts[ApplyStatus::DryRun->name]);
        }
        if (($counts[ApplyStatus::AlreadyApplied->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('<comment>%d already applied</comment>', $counts[ApplyStatus::AlreadyApplied->name]);
        }
        if (($counts[ApplyStatus::Skipped->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('%d skipped', $counts[ApplyStatus::Skipped->name]);
        }
        if (($counts[ApplyStatus::Conflict->name] ?? 0) > 0) {
            $summary_parts[] = sprintf('<error>%d conflict(s)</error>', $counts[ApplyStatus::Conflict->name]);
        }

        $output->writeln('');
        $output->writeln('Summary: ' . implode(', ', $summary_parts));
    }
}
