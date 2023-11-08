<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\System\Diagnostic;

use FilesystemIterator;
use Glpi\Toolbox\VersionParser;
use GLPIKey;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;

/**
 * @since 10.1.0
 */
class SourceCodeIntegrityChecker
{
    public const STATUS_OK = 0;
    public const STATUS_ALTERED = 1;
    public const STATUS_MISSING = 2;
    public const STATUS_ADDED = 3;

    /**
     * @note Only exists to be able to mock it in tests
     */
    public function getCheckRootDir(): string
    {
        return GLPI_ROOT;
    }

    /**
     * Get list of files/directories to check.
     *
     * @return array
     */
    private function getPathsToCheck(): array
    {
        return [
            'ajax', 'css', 'front', 'inc', 'install', 'js', 'lib', 'locales', 'pics',
            'public', 'resources', 'sound', 'src', 'templates', 'vendor',
            'api.php', 'apirest.md', 'apirest.php', 'caldav.php', 'index.php', 'status.php'
        ];
    }

    /**
     * Returns the source code manifest contents.
     *
     * @return array|null
     * @phpstan-return array{algorithm: string, files: array<string, string>}|null
     * @throws \JsonException
     */
    private function getBaselineManifest(): ?array
    {
        $version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        $manifest = file_get_contents($this->getCheckRootDir() . '/version/' . $version);
        try {
            $content = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('The source code file manifest is invalid.', previous: $e);
        }
        if (!isset($content['algorithm'], $content['files']) || !is_string($content['algorithm']) || !is_array($content['files'])) {
            throw new \RuntimeException('The source code file manifest is invalid.');
        }
        return $content;
    }

    /**
     * Generates the source code manifest contents.
     *
     * @param string $algorithm
     * @return array {algorithm: string, files: array<string, string>}
     */
    public function generateManifest(string $algorithm): array
    {
        $to_scan = $this->getPathsToCheck();

        $files_to_check = [];
        $hashes = [];
        foreach ($to_scan as $item) {
            $path = $this->getCheckRootDir() . '/' . $item;
            if (is_dir($path)) {
                $iterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
                // flatten the iterator
                $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $files_to_check[] = $file->getPathname();
                    }
                }
            } else {
                $files_to_check[] = $path;
            }
        }
        sort($files_to_check);
        foreach ($files_to_check as $file) {
            $key = preg_replace('/^' . preg_quote($this->getCheckRootDir() . '/', '/') . '/', '', $file);
            $hashes[$key] = hash_file($algorithm, $file);
        }
        return [
            'algorithm' => $algorithm,
            'files' => $hashes
        ];
    }

    /**
     * Get a summary of differences between current source code and expected source code.
     *
     * @return array
     */
    public function getSummary(): array
    {
        $baseline = $this->getBaselineManifest();
        $current = $this->generateManifest($baseline['algorithm']);

        // Get files missing from the current manifest
        $missing = array_diff_key($baseline['files'], $current['files']);
        // Get files added to the current manifest
        $added = array_diff_key($current['files'], $baseline['files']);
        // Get files altered
        $altered = [];
        foreach ($baseline['files'] as $file => $hash) {
            if (isset($current['files'][$file]) && $current['files'][$file] !== $hash) {
                $altered[$file] = $current['files'][$file];
            }
        }
        // Summary where the key is the file and the value is the status. Ignore OK files
        $summary = [];
        foreach ($altered as $file => $hash) {
            $summary[$file] = self::STATUS_ALTERED;
        }
        foreach ($added as $file => $hash) {
            $summary[$file] = self::STATUS_ADDED;
        }
        foreach ($missing as $file => $hash) {
            $summary[$file] = self::STATUS_MISSING;
        }

        return $summary;
    }

    /**
     * Download the GLPI release.
     *
     * @param array $errors
     * @return string|null Release file path.
     */
    private function getGLPIRelease(array &$errors = []): string|null
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $version_to_get = VersionParser::getNormalizedVersion(GLPI_VERSION);
        $gh_releases_endpoint = 'https://api.github.com/repos/glpi-project/glpi/releases/tags/' . $version_to_get;

        $options = [
            'connect_timeout' => 10, // 10 seconds timeout
        ];
        // add proxy string if configured in glpi
        if (!empty($CFG_GLPI['proxy_name'])) {
            $options['proxy'] = sprintf(
                'http://%s%s:%d',
                !empty($CFG_GLPI['proxy_user'])
                    ? sprintf('%s:%s@', $CFG_GLPI['proxy_user'], (new GLPIKey())->decrypt($CFG_GLPI['proxy_passwd']))
                    : '',
                $CFG_GLPI['proxy_name'],
                $CFG_GLPI['proxy_port']
            );
        }
        $client = new Client($options);

        $dest = null;
        try {
            $response = $client->request('GET', $gh_releases_endpoint);
            $release = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            foreach ($release['assets'] as $asset) {
                if (str_starts_with($asset['name'], 'glpi-' . $version_to_get)) {
                    $dest = GLPI_TMP_DIR . '/' . $asset['name'];
                    // Check if the file already exists in the tmp dir
                    if (file_exists($dest)) {
                        break;
                    }
                    $url = $asset['browser_download_url'];
                    $client->request('GET', $url, ['sink' => $dest]);
                    break;
                }
            }
        } catch (GuzzleException $e) {
            $errors[] = $e->getMessage();
        }
        return $dest;
    }

    /**
     * Get the diff (unified diff format) between current source code and expected source code.
     *
     * @param bool $allow_download  Whether the release download is allowed.
     * @param array $errors         Array on which any errors will be added.
     * @return string|null
     */
    public function getDiff(bool $allow_download = false, array &$errors = []): ?string
    {
        $summary = $this->getSummary();
        // ignore OK files in case they are present
        $summary = array_filter($summary, static fn ($status) => $status !== self::STATUS_OK);
        // Ensure the release is downloaded
        $release_path = GLPI_TMP_DIR . '/glpi-' . VersionParser::getNormalizedVersion(GLPI_VERSION) . '.tgz';
        if (!file_exists($release_path)) {
            if ($allow_download) {
                $release_path = $this->getGLPIRelease($errors);
                if ($release_path === null) {
                    $errors[] = 'An error occurred while downloading the release.';
                    return null;
                }
            } else {
                $errors[] = 'The release is not downloaded and downloading it was not allowed.';
                return null;
            }
        }

        $diff = '';

        foreach ($summary as $file => $status) {
            $original_file = 'a/' . $file;
            $current_file = 'b/' . $file;
            $original_content = '';
            $current_content = '';
            $extra_header = '';
            if ($status === self::STATUS_ADDED || !file_exists('phar://' . $release_path . '/glpi/' . $file)) {
                $original_file = '/dev/null';
                $file_perms   = @substr(sprintf('%o', fileperms($this->getCheckRootDir() . '/' . $file)), -4) ?: '0644';
                $extra_header = 'new file mode 10' . $file_perms;
            } else {
                $original_content = file_get_contents('phar://' . $release_path . '/glpi/' . $file);
                if ($original_content === false) {
                    $errors[] = sprintf('Failed to get original contents of file `%s`.', $file);
                    continue;
                }
            }
            if ($status === self::STATUS_MISSING || !file_exists($this->getCheckRootDir() . '/' . $file)) {
                $current_file = '/dev/null';
                $file_perms   = @substr(sprintf('%o', fileperms('phar://' . $release_path . '/glpi/' . $file)), -4) ?: '0644';
                $extra_header = 'deleted file mode 10' . $file_perms;
            } else {
                $current_content = file_get_contents($this->getCheckRootDir() . '/' . $file);
                if ($current_content === false) {
                    $errors[] = sprintf('Fails to get current contents of file `%s`.', $file);
                    continue;
                }
            }
            try {
                $differ = new Differ(
                    new StrictUnifiedDiffOutputBuilder(
                        [
                            'fromFile' => $original_file,
                            'toFile'   => $current_file,
                        ]
                    )
                );
                $file_diff = $differ->diff(
                    $original_content,
                    $current_content
                );
                $diff .= 'diff --git a/' . $file . ' b/' . $file . "\n";
                if ($extra_header !== '') {
                    $diff .= "$extra_header\n";
                }
                $diff .= "$file_diff\n";
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        return $diff;
    }
}
