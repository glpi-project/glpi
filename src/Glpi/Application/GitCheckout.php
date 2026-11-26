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

namespace Glpi\Application;

/**
 * Detects a GLPI instance that runs directly from a git checkout and reads its current revision.
 *
 * On such an instance, the source code can change (`git pull`, `git checkout`) without `GLPI_VERSION`
 * being changed. The revision is used to make the cache keys follow the source code.
 *
 * This class must not have any dependency: it is used before the autoloader is loaded
 * (see `ResourcesChecker`) and while the constants are defined.
 */
final class GitCheckout
{
    /**
     * Indicates whether the given directory is the root of a git checkout.
     *
     * `.git` is a directory in a regular clone, and a file in a linked worktree or a submodule.
     */
    public static function isGitCheckout(string $root_dir): bool
    {
        return \file_exists($root_dir . '/.git');
    }

    /**
     * Returns the commit hash currently checked out, or `null` if it cannot be determined
     * (not a git checkout, unborn branch, unsupported ref storage, ...).
     */
    public static function getRevision(string $root_dir): ?string
    {
        $git_dir = self::getGitDir($root_dir);
        if ($git_dir === null) {
            return null;
        }

        $head = self::readFile($git_dir . '/HEAD');
        if ($head === null) {
            return null;
        }

        if (!\str_starts_with($head, 'ref: ')) {
            // Detached HEAD, the file contains the commit hash.
            return self::isCommitHash($head) ? $head : null;
        }

        $ref = \substr($head, 5);
        if (!\str_starts_with($ref, 'refs/') || \str_contains($ref, '..')) {
            return null;
        }

        // In a linked worktree, the branches are stored in the main repository directory.
        $common_dir = $git_dir;
        $common_dir_pointer = self::readFile($git_dir . '/commondir');
        if ($common_dir_pointer !== null) {
            $common_dir = self::resolvePath($common_dir_pointer, $git_dir);
        }

        foreach (\array_unique([$git_dir, $common_dir]) as $dir) {
            $hash = self::readFile($dir . '/' . $ref);
            if ($hash !== null && self::isCommitHash($hash)) {
                return $hash;
            }
        }

        // The ref may only exist in the packed refs file.
        $packed_refs = self::readFile($common_dir . '/packed-refs');
        if ($packed_refs !== null) {
            foreach (\explode("\n", $packed_refs) as $line) {
                $parts = \explode(' ', \trim($line), 2);
                if (\count($parts) === 2 && $parts[1] === $ref && self::isCommitHash($parts[0])) {
                    return $parts[0];
                }
            }
        }

        return null;
    }

    /**
     * Returns the path of the git directory of the checkout, if any.
     */
    private static function getGitDir(string $root_dir): ?string
    {
        $git_path = $root_dir . '/.git';

        if (\is_dir($git_path)) {
            return $git_path;
        }

        // Linked worktree or submodule: `.git` is a file containing `gitdir: <path>`.
        $pointer = self::readFile($git_path);
        if ($pointer === null || !\str_starts_with($pointer, 'gitdir: ')) {
            return null;
        }

        $git_dir = self::resolvePath(\substr($pointer, 8), $root_dir);

        return \is_dir($git_dir) ? $git_dir : null;
    }

    private static function resolvePath(string $path, string $base_dir): string
    {
        return \str_starts_with($path, '/') || \preg_match('/^[a-z]:[\\\\\/]/i', $path) === 1 // @phpstan-ignore theCodingMachineSafe.function (Safe not loaded at this point)
            ? $path
            : $base_dir . '/' . $path;
    }

    private static function isCommitHash(string $value): bool
    {
        // SHA-1 (40 chars) or SHA-256 (64 chars) object names.
        return \preg_match('/^([0-9a-f]{40}|[0-9a-f]{64})$/', $value) === 1; // @phpstan-ignore theCodingMachineSafe.function (Safe not loaded at this point)
    }

    private static function readFile(string $path): ?string
    {
        if (!\is_file($path) || !\is_readable($path)) {
            return null;
        }

        $contents = \file_get_contents($path); // @phpstan-ignore theCodingMachineSafe.function (Safe not loaded at this point)

        return $contents !== false ? \trim($contents) : null;
    }
}
