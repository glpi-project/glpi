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

namespace Glpi\Patch;

use Exception;
use SebastianBergmann\Diff\Chunk;
use SebastianBergmann\Diff\Diff;
use SebastianBergmann\Diff\Parser;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\unlink;

final class PatchApplier
{
    /**
     * Number of lines to search above and below the expected hunk position
     * when the file has been slightly changed since the patch was generated.
     */
    private const FUZZ_LINES = 3;

    public function __construct(
        private PathRewriter $rewriter,
    ) {}

    /**
     * Applies a raw unified diff string to the filesystem.
     *
     * @param  string $diff_content  Raw unified diff (e.g. downloaded from GitHub).
     * @param  bool   $dry_run       When true nothing is written; results show
     *                               what WOULD happen.
     * @return FileApplyResult[]
     */
    public function apply(string $diff_content, bool $dry_run = false, bool $force_vue = false, bool $revert = false): array
    {
        $diffs = (new Parser())->parse($diff_content);

        //check if the patch contains .vue files, if yes, throw an exception if $force_vue is not set to true.
        if (!$force_vue) {
            foreach ($diffs as $diff) {
                if (str_ends_with($diff->to(), '.vue') || str_ends_with($diff->from(), '.vue')) {
                    throw new \RuntimeException('This patch contains changes to .vue files, which are not meant to be applied. Stopping the patch application.');
                }
            }
        }

        $results = [];
        foreach ($diffs as $diff) {
            $results[] = $this->applyFileDiff($diff, $dry_run, $revert);
        }

        return $results;
    }

    private function applyFileDiff(Diff $diff, bool $dry_run, bool $revert): FileApplyResult
    {
        $from = $diff->from();
        $to   = $diff->to();

        $is_new_file     = $this->rewriter->isDevNull($from);
        $is_deleted_file = $this->rewriter->isDevNull($to);

        if (!$is_new_file && !$is_deleted_file) {
            $resolved_from = $this->rewriter->resolve($from);
            $resolved_to   = $this->rewriter->resolve($to);
            if($resolved_from['path'] !== $resolved_to['path']) {
                return $this->applyRenamedFile($diff, $resolved_from, $resolved_to, $dry_run, $revert);
            }
        }

        // Resolve filesystem target
        $resolved_target = $this->rewriter->resolve($is_new_file ? $to : $from);
        $target = $resolved_target['path'];
        $skiped = $resolved_target['skiped'];

        if ($skiped) {
            return new FileApplyResult($target, ApplyStatus::Skipped, 'Skipped');
        }

        if ($revert) {
            if ($is_new_file) {
                // Patch created this file → revert by deleting it
                return $this->applyDeletedFile($target, $dry_run);
            }
            if ($is_deleted_file) {
                // Patch deleted this file → revert by recreating it with original content
                return $this->applyRevertDeletedFile($diff, $target, $dry_run);
            }
            return $this->applyModifiedFile($diff, $target, $dry_run, $revert);
        }

        if ($is_new_file) {
            return $this->applyNewFile($diff, $target, $dry_run);
        }

        if ($is_deleted_file) {
            return $this->applyDeletedFile($target, $dry_run);
        }

        return $this->applyModifiedFile($diff, $target, $dry_run, $revert);
    }

    private function applyNewFile(Diff $diff, string $target, bool $dry_run): FileApplyResult
    {
        $new_lines = [];
        $has_trailing_newline = true;
        foreach ($diff->chunks() as $chunk) {
            foreach ($chunk->lines() as $line) {
                if (str_starts_with($line->content(), '\\ ')) {
                    $has_trailing_newline = false;
                    continue;
                }
                if ($line->isAdded()) {
                    $new_lines[] = $line->content();
                }
            }
        }

        $new_content = implode("\n", $new_lines) . ($has_trailing_newline ? "\n" : "");

        if (file_exists($target)) {
            try {
                $existing = file_get_contents($target);
            } catch (Exception $e) {
                return new FileApplyResult(
                    $target,
                    ApplyStatus::Conflict,
                    "A file already exists at this location but it cannot be read: $target - check permissions.\n"
                    . $e->getMessage()
                );
            }

            if (
                $existing === $new_content
                || rtrim((string) $existing, "\r\n") === rtrim($new_content, "\r\n")
            ) {
                return new FileApplyResult($target, ApplyStatus::AlreadyApplied, 'File was already created with the same content');
            }

            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                'A file already exists at this location but its content differs from the patch. '
                . 'It may have been created by a different change. Please review it manually.'
            );
        }

        if ($dry_run) {
            return new FileApplyResult($target, ApplyStatus::DryRun, 'Would create this new file');
        }

        $dir = dirname($target);
        if (!is_dir($dir)) {
            try {
                mkdir($dir, 0o755, true);
            } catch (Exception $e) {
                return new FileApplyResult(
                    $target,
                    ApplyStatus::Conflict,
                    "Could not create directory: $dir - check permissions."
                );
            }
        }

        try {
            file_put_contents($target, $new_content);
        } catch (Exception $e) {
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                "Could not write to: $target - check permissions."
            );
        }

        return new FileApplyResult($target, ApplyStatus::Created, 'New file created successfully');
    }

    private function applyDeletedFile(string $target, bool $dry_run): FileApplyResult
    {
        if (!file_exists($target)) {
            return new FileApplyResult($target, ApplyStatus::AlreadyApplied, 'File was already deleted');
        }

        if ($dry_run) {
            return new FileApplyResult($target, ApplyStatus::DryRun, 'Would delete this file');
        }

        try {
            unlink($target);
        } catch (Exception $e) {
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                "Could not delete: $target - check permissions."
            );
        }

        return new FileApplyResult($target, ApplyStatus::Deleted, 'File deleted successfully');
    }

    private function applyModifiedFile(Diff $diff, string $target, bool $dry_run, bool $revert = false): FileApplyResult
    {
        if (!file_exists($target)) {
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                'File not found on disk.'
            );
        }

        if (!is_readable($target)) {
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                "File is not readable: $target - check permissions."
            );
        }

        try {
            $original_content = file_get_contents($target);
        } catch (Exception $e) {
            return new FileApplyResult($target, ApplyStatus::Conflict, "Cannot read file: $target\n" . $e->getMessage());
        }

        // Detect line-ending style so we can preserve it after rewriting
        $eol = str_contains($original_content, "\r\n") ? "\r\n" : "\n";

        // Normalize to LF internally for uniform processing
        $normalized = str_replace("\r\n", "\n", $original_content);
        $lines = explode("\n", $normalized);
        if (end($lines) === '') {
            array_pop($lines); // Remove trailing empty element from final newline
        }

        // Track cumulative line-shift caused by applied hunks
        $offset              = 0;
        $applied_count       = 0;
        $already_applied_count = 0;

        foreach ($diff->chunks() as $chunk) {
            [$old_lines, $new_lines] = $revert ? $this->buildOldNewRevert($chunk) : $this->buildOldNew($chunk);

            // Expected start position in the current (shifted) $lines array (0-based).
            // In normal mode, start() is the old-file line number (1-based).
            // In revert mode, end() is the new-file line number (1-based).
            $expected_pos = max(0, ($revert ? $chunk->end() : $chunk->start()) - 1 + $offset);

            // Check if new lines are already present (patch already applied).
            if ($new_lines !== []) {
                $found_new = $this->findSequence($lines, $new_lines, $expected_pos);
                if ($found_new !== null) {
                    // Already applied — adjust offset so later hunks stay aligned
                    $offset += count($new_lines) - count($old_lines);
                    $already_applied_count++;
                    continue;
                }
            }

            // only new lines are added = pure insertion
            if ($old_lines === []) {
                array_splice($lines, $expected_pos, 0, $new_lines);
                $offset += count($new_lines);
                $applied_count++;
                continue;
            }

            // old lines are present and new lines aren't added yet : look for old lines to apply the change
            $found_old = $this->findSequence($lines, $old_lines, $expected_pos);

            if ($found_old !== null) {
                array_splice($lines, $found_old, count($old_lines), $new_lines);
                $offset += count($new_lines) - count($old_lines);
                $applied_count++;
                continue;
            }

            // Neither found: genuine conflict
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                sprintf(
                    'The patch does not match the file content near line %d. '
                    . 'This usually means the patch is based on a different version of the file, '
                    . 'or another change was applied in between. Manual intervention is required.',
                    $chunk->start()
                )
            );
        }

        // All hunks accounted for - decide overall result
        if ($applied_count === 0 && $already_applied_count > 0) {
            return new FileApplyResult(
                $target,
                ApplyStatus::AlreadyApplied,
                $revert ? 'All changes were already reverted in this file' : 'All changes were already present in this file'
            );
        }

        //check that the number of applied hunks is consistent with the diff content (sanity check)
        $total_hunks = count($diff->chunks());
        if ($applied_count + $already_applied_count !== $total_hunks) {
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                "Unexpected error: only $applied_count out of $total_hunks hunks were applied, and $already_applied_count were already applied. Please review the file manually."
            );
        }

        if ($dry_run) {
            return new FileApplyResult(
                $target,
                ApplyStatus::DryRun,
                $revert ? 'Would successfully revert changes in this file' : 'Would successfully apply changes to this file'
            );
        }

        // Reconstruct file content preserving original EOL and trailing newline
        $new_content = implode($eol, $lines);
        if (str_ends_with($original_content, "\n") || str_ends_with($original_content, "\r\n")) {
            $new_content .= $eol;
        }

        try {
            file_put_contents($target, $new_content);
        } catch (Exception $e) {
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                "Cannot write to file: $target - check permissions."
            );
        }

        return new FileApplyResult(
            $target,
            $revert ? ApplyStatus::Reverted : ApplyStatus::Applied,
            $revert ? 'Changes reverted successfully' : 'Changes applied successfully'
        );
    }

    /**
     * Recreates a file that was deleted by the patch (used when reverting).
     * The original content is reconstructed from the removed lines in the diff.
     */
    private function applyRevertDeletedFile(Diff $diff, string $target, bool $dry_run): FileApplyResult
    {
        $old_lines = [];
        $has_trailing_newline = true;
        foreach ($diff->chunks() as $chunk) {
            foreach ($chunk->lines() as $line) {
                $content = $line->content();
                if (str_starts_with($content, '\\ ')) {
                    $has_trailing_newline = false;
                    continue; // Skip the "\ No newline at end of file" indicator
                }
                if ($line->isRemoved() || $line->isUnchanged()) {
                    $old_lines[] = $content;
                }
            }
        }

        $old_content = implode("\n", $old_lines) . ($has_trailing_newline ? "\n" : "");

        if (file_exists($target)) {
            try {
                $existing = file_get_contents($target);
            } catch (Exception $e) {
                return new FileApplyResult(
                    $target,
                    ApplyStatus::Conflict,
                    "A file already exists at this location but it cannot be read: $target - check permissions.\n"
                    . $e->getMessage()
                );
            }

            if (
                $existing === $old_content
                || rtrim((string) $existing, "\r\n") === rtrim($old_content, "\r\n")
            ) {
                return new FileApplyResult($target, ApplyStatus::AlreadyApplied, 'File was already restored with its original content');
            }

            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                'A file already exists at this location but its content differs from the expected original. Manual intervention is required.'
            );
        }

        if ($dry_run) {
            return new FileApplyResult($target, ApplyStatus::DryRun, 'Would restore this deleted file');
        }

        $dir = dirname($target);
        if (!is_dir($dir)) {
            try {
                mkdir($dir, 0o755, true);
            } catch (Exception $e) {
                return new FileApplyResult(
                    $target,
                    ApplyStatus::Conflict,
                    "Could not create directory: $dir - check permissions."
                );
            }
        }

        try {
            file_put_contents($target, $old_content);
        } catch (Exception $e) {
            return new FileApplyResult(
                $target,
                ApplyStatus::Conflict,
                "Could not write to: $target - check permissions."
            );
        }

        return new FileApplyResult($target, ApplyStatus::Created, 'File restored successfully');
    }

    /**
     * Handles a rename or move diff entry.
     *
     * Four cases based on skip status:
     *   - both skipped          → skip entirely
     *   - destination skipped   → only delete the source
     *   - source skipped        → only create the destination (source kept intact)
     *   - neither skipped       → apply modifications, write destination, delete source
     */
    private function applyRenamedFile(Diff $diff, array $resolved_from, array $resolved_to, bool $dry_run, bool $revert): FileApplyResult
    {
        $source_path    = $revert ? $resolved_to['path']     : $resolved_from['path'];
        $dest_path      = $revert ? $resolved_from['path']   : $resolved_to['path'];
        $source_skipped = $revert ? $resolved_to['skiped']   : $resolved_from['skiped'];
        $dest_skipped   = $revert ? $resolved_from['skiped'] : $resolved_to['skiped'];

        if ($source_skipped && $dest_skipped) {
            return new FileApplyResult("from " . $source_path . "\nto " . $dest_path, ApplyStatus::Skipped, 'Skipped');
        }

        if ($dest_skipped) {
            // $to is skipped: only delete the source, don't create the destination
            return $this->applyDeletedFile($source_path, $dry_run);
        }

        // Destination is not skipped: we must create/write it.
        // Source may be skipped; if not, we also delete it after writing.

        if (!file_exists($source_path)) {
            return new FileApplyResult($source_path, ApplyStatus::Conflict, 'Source file not found on disk.');
        }

        if (!is_readable($source_path)) {
            return new FileApplyResult(
                $source_path,
                ApplyStatus::Conflict,
                "Source file is not readable: $source_path - check permissions."
            );
        }

        try {
            $original_content = file_get_contents($source_path);
        } catch (Exception $e) {
            return new FileApplyResult(
                $source_path,
                ApplyStatus::Conflict,
                "Cannot read source file: $source_path\n" . $e->getMessage()
            );
        }

        $target_file_already_exists = file_exists($dest_path);

        if ($dry_run) {
            //if the target file soesn't already exist,
            //simulate the dry run on the source file (same base)
            if (!$target_file_already_exists) {
                $result = $this->applyModifiedFile($diff, $source_path, $dry_run, $revert);
            }
            else{
                $result = $this->applyModifiedFile($diff, $dest_path, $dry_run, $revert);
            }
        } else {
            if (!$target_file_already_exists) {
                try{
                    $dir = dirname($dest_path);
                    if(!is_dir($dir)) {
                        mkdir(dirname($dest_path), 0o755, true);
                    }
                    file_put_contents($dest_path, $original_content);
                } catch (Exception $e) {
                    return new FileApplyResult(
                        $dest_path,
                        ApplyStatus::Conflict,
                        "Could not create destination file: $dest_path - check permissions."
                    );
                }
            }
            $result = $this->applyModifiedFile($diff, $dest_path, $dry_run, $revert);
        }

        if(
            !in_array(
                $result->status,
                [
                    ApplyStatus::Applied,
                    ApplyStatus::AlreadyApplied,
                    ApplyStatus::Reverted,
                    ApplyStatus::DryRun
                ],
                true
            )
        ) {
            $result->display_path = "From " . $source_path . "\nTo " . $dest_path;
            return $result; // If we failed to apply modifications to the destination, don't delete the source
        }

        if (!$source_skipped && !$dry_run && file_exists($source_path)) {
            try {
                unlink($source_path);
            } catch (Exception $e) {
                return new FileApplyResult(
                    "From " . $source_path . "\nTo " . $dest_path,
                    ApplyStatus::Conflict,
                    "Destination file was created successfully, but the source file could not be deleted - check permissions.\n"
                );
            }
        }

        if ($dry_run) {
            return new FileApplyResult(
                "From " . $source_path . "\nTo " . $dest_path,
                ApplyStatus::DryRun,
                $revert ? 'Would successfully revert this rename' : 'Would successfully apply this rename'
            );
        }

        return new FileApplyResult(
            "From " . $source_path . "\nTo " . $dest_path,
            $revert ? ApplyStatus::Reverted : ApplyStatus::Applied,
            $revert ? 'Rename reverted successfully' : 'File renamed successfully'
        );
    }

    /**
     * Separates a hunk's lines into the "old" sequence (what must be found in
     * the file) and the "new" sequence (what replaces it).
     *
     * Unified diff semantics:
     *   UNCHANGED (+context)  → appears in both old and new
     *   REMOVED               → appears only in old
     *   ADDED                 → appears only in new
     *
     * @return array{0: string[], 1: string[]}  [$old_lines, $new_lines]
     */
    private function buildOldNew(Chunk $chunk): array
    {
        $old = [];
        $new = [];

        // Use the @@ header counts as hard caps — sebastian/diff may absorb
        // metadata lines (e.g. "deleted file mode") from the next file in a
        // multi-file diff into the last chunk's line list.
        $max_old = $chunk->startRange();
        $max_new = $chunk->endRange();

        foreach ($chunk->lines() as $line) {
            $content = $line->content();

            // Skip the "\ No newline at end of file" indicator that git diff adds
            if (str_starts_with($content, '\\ ')) {
                continue;
            }

            $count_old = count($old);
            $count_new = count($new);

            if ($line->isUnchanged()) {
                if ($count_old < $max_old) {
                    $old[] = $content;
                }
                if ($count_new < $max_new) {
                    $new[] = $content;
                }
            } elseif ($line->isRemoved()) {
                if ($count_old < $max_old) {
                    $old[] = $content;
                }
            } elseif ($line->isAdded()) {
                if ($count_new < $max_new) {
                    $new[] = $content;
                }
            }
        }

        return [$old, $new];
    }

    /**
     * Returns the hunk's line sequences in reverse order for revert operations:
     * the "new" sequence becomes what to search for, and the "old" sequence
     * becomes what to replace with.
     *
     * @return array{0: string[], 1: string[]}  [$old_lines, $new_lines]
     */
    private function buildOldNewRevert(Chunk $chunk): array
    {
        [$old, $new] = $this->buildOldNew($chunk);
        return [$new, $old];
    }

    /**
     * Searches for $needle as a contiguous subsequence of $haystack,
     * starting within [$expected_pos - FUZZ, $expected_pos + FUZZ].
     *
     * Trailing whitespace is ignored when comparing individual lines,
     * which improves tolerance for minor editor differences.
     *
     * @param string[] $haystack
     * @param string[] $needle
     * @return int|null  0-based index of the match in $haystack, or null.
     */
    private function findSequence(array $haystack, array $needle, int $expected_pos): ?int
    {
        if ($needle === []) {
            return $expected_pos;
        }

        $needle_len   = count($needle);
        $haystack_len = count($haystack);

        if ($needle_len > $haystack_len) {
            return null;
        }

        $from = max(0, $expected_pos - self::FUZZ_LINES);
        $to   = min($haystack_len - $needle_len, $expected_pos + self::FUZZ_LINES);

        if ($to < $from) {
            return null;
        }

        for ($i = $from; $i <= $to; $i++) {
            if ($this->linesMatchAt($haystack, $needle, $i)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Returns true if every line of $needle matches $haystack starting at $pos.
     *
     * @param string[] $haystack
     * @param string[] $needle
     */
    private function linesMatchAt(array $haystack, array $needle, int $pos): bool
    {
        foreach ($needle as $j => $expected_line) {
            if (!array_key_exists($pos + $j, $haystack)) {
                return false;
            }

            if (rtrim($haystack[$pos + $j]) !== rtrim($expected_line)) {
                return false;
            }
        }

        return true;
    }
}
