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
use RuntimeException;

use function Safe\file_get_contents;
use function Safe\parse_url;
use function Safe\preg_match;

final class DiffFetcher
{
    /**
     * URL template for GitHub's raw diff endpoint.
     * Arguments: owner, repo, pull-request number.
     */
    private const GITHUB_DIFF_URL = 'https://patch-diff.githubusercontent.com/raw/%s/%s/pull/%d.diff';

    /**
     * Fetches the diff content from a GitHub PR URL or a local diff/patch file.
     *
     * Accepted input formats:
     *   - https://github.com/owner/repo/pull/123   (GitHub PR page URL)
     *   - https://patch-diff.githubusercontent.com/raw/owner/repo/pull/123.diff  (Direct diff URL)
     *   - /absolute/path/to/file.diff              (local file)
     *   - relative/path/to/file.patch              (local file)
     *
     * @throws RuntimeException When the source cannot be read.
     */
    public function fetch(string $input): string
    {
        if ($this->isUrl($input)) {
            return $this->fetchFromUrl($input);
        }

        return $this->fetchFromFile($input);
    }

    /**
     * Tries to extract the GitHub owner, repository, and PR number from a URL.
     *
     * @return array{0: string, 1: string, 2: int}|null  [owner, repo, pr_num] or null.
     */
    public function parseGitHubPrUrl(string $url): ?array
    {
        $m = [];
        if (preg_match('#github\.com/([^/]+)/([^/]+)/pull/(\d+)#', $url, $m) === 1) {
            return count($m) === 4 ? [$m[1], $m[2], (int) $m[3]] : null;
        }

        return null;
    }

    private function isUrl(string $input): bool
    {
        return in_array(parse_url($input, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function fetchFromUrl(string $url): string
    {
        // Transform a GitHub PR page URL to the raw diff endpoint
        $parsed = $this->parseGitHubPrUrl($url);
        if ($parsed !== null) {
            [$owner, $repo, $pr_num] = $parsed;
            $diff_url = sprintf(self::GITHUB_DIFF_URL, $owner, $repo, $pr_num);
            return $this->download($diff_url);
        }

        // Treat as a direct URL to a .diff file
        return $this->download($url);
    }

    private function fetchFromFile(string $path): string
    {
        if (!file_exists($path)) {
            throw new RuntimeException(
                "Diff file not found: $path\n"
                . "Make sure the path is correct and the file exists."
            );
        }

        if (!is_readable($path)) {
            throw new RuntimeException(
                "Cannot read diff file: $path\n"
                . "Check that the file permissions allow reading."
            );
        }

        try {
            $content = file_get_contents($path);
            return $content;
        } catch (Exception $e) {
            throw new RuntimeException("The diff file could not be read: $path\n" . $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function download(string $url): string
    {
        try {
            $content = file_get_contents($url);
            return $content;
        } catch (Exception $e) {
            throw new RuntimeException("Failed to download the diff from: $url\n"
            . "Please check that the PR number is correct.", $e->getCode(), $e);
        }
    }
}
