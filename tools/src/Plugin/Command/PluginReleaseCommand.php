<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Tools\Plugin\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

final class PluginReleaseCommand extends AbstractPluginCommand
{
    private const SCRIPT_VERSION = '2.0.0';

    private string $dist_dir;
    private string $gh_orga = 'pluginsGLPI';
    private string $plugin_name = '';
    private string $commit = '';

    private array $banned = [
        '.git*',
        '.gh_token',
        '.tx/',
        'tools/',
        'tests/',
        '.atoum.php',
        '.travis.yml',
        '.circleci/',
        '.ignore-release',
        '.stylelintrc.js',
        '.twig_cs.dist.php',
        'rector.php',
        'phpstan.neon',
        '.phpcs.xml',
        'phpunit.xml',
        'phpunit.xml.dist',
    ];

    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this->setName('tools:plugin:release');
        $this->setDescription(sprintf('Build and release a plugin (v%s).', self::SCRIPT_VERSION));
        $this->setHelp(sprintf(
            'GLPI plugins release script v%s' . PHP_EOL . PHP_EOL
            . 'This command builds and releases a GLPI plugin archive.',
            self::SCRIPT_VERSION
        ));

        $this->addOption('release', 'r', InputOption::VALUE_REQUIRED, 'Version to release');
        $this->addOption('nogithub', 'g', InputOption::VALUE_NONE, 'DO NOT Create github draft release');
        $this->addOption('check-only', 'C', InputOption::VALUE_NONE, 'Only do check, does not release anything');
        $this->addOption('dont-check', 'd', InputOption::VALUE_NONE, 'DO NOT check version');
        $this->addOption('propose', null, InputOption::VALUE_NONE, 'Calculate and propose next possible versions');
        $this->addOption('commit', 'c', InputOption::VALUE_REQUIRED, 'Specify commit to archive (-r required)');
        $this->addOption('extra', 'e', InputOption::VALUE_REQUIRED, 'Extra version informations (-c required)');
        $this->addOption('compile-mo', 'm', InputOption::VALUE_NONE, 'Compile MO files from PO files');
        $this->addOption('nosign', 'S', InputOption::VALUE_NONE, 'Do not sign release tarball');
        $this->addOption('assume-yes', 'Y', InputOption::VALUE_NONE, 'Assume YES to all questions');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force rebuild even if release exists');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin_dir = $this->getPluginDirectory();

        if (!file_exists($plugin_dir . '/setup.php')) {
            $this->io->error('Current directory is not a valid GLPI plugin.');
            return Command::FAILURE;
        }

        $this->dist_dir = $plugin_dir . '/dist';
        if (!is_dir($this->dist_dir)) {
            mkdir($this->dist_dir, 0o777, true);
        }

        $this->plugin_name = $this->guessPluginName();

        if ($input->getOption('compile-mo')) {
            $this->compileMo($plugin_dir);
            return Command::SUCCESS;
        }

        // Propose version
        if ($input->getOption('propose')) {
            $this->proposeVersion();
            return Command::SUCCESS;
        }

        // Release or Check-Only
        $release_version = $input->getOption('release');
        $input_commit = $input->getOption('commit');
        $extra = $input->getOption('extra');

        if (($extra || $input_commit) && (!$extra || !$input_commit || !$release_version)) {
            $this->io->error('You have to specify --release, --commit and --extra all together');
            return Command::FAILURE;
        }

        if ($release_version) {
            if (!$input->getOption('dont-check')) {
                if (!$this->validVersion($release_version)) {
                    $this->io->error(sprintf('%s is not a valid version number!', $release_version));
                    return Command::FAILURE;
                }

                if (!$this->checkVersion($release_version)) {
                    return Command::FAILURE;
                }
            }

            if ($input_commit) {
                if (!$this->validCommit($input_commit)) {
                    $this->io->error(sprintf('Invalid commit ref %s', $input_commit));
                    return Command::FAILURE;
                }
            } elseif (!$this->isExistingVersion($release_version)) {
                $this->io->error(sprintf('Tag %s does not exist!', $release_version));
                return Command::FAILURE;
            }
        } else {
            // If no release version specified, use latest version
            $release_version = $this->getLatestVersion();
            if (!$release_version) {
                $this->io->error('No tags found in the repository.');
                return Command::FAILURE;
            }

            if ($input->getOption('force')) {
                // Force mode, proceed directly
            } else {
                if (!$this->io->confirm(sprintf('Do you want to build version %s?', $release_version), true)) {
                    return Command::SUCCESS;
                }
            }
        }

        $this->io->title("Releasing plugin {$this->plugin_name}");

        if ($input->getOption('check-only')) {
            $this->io->note('*** Entering *check-only* mode ***');
            $this->io->success('Check-only mode finished.');
            return Command::SUCCESS;
        }

        // Prepare Archive Name
        $archive_name = "glpi-{$this->plugin_name}-{$release_version}";
        if ($input_commit && $extra) {
            $date = date('Ymd');
            $archive_name = "glpi-{$this->plugin_name}-{$release_version}-{$extra}-{$date}-{$this->commit}";
        }

        $tarball = $this->dist_dir . DIRECTORY_SEPARATOR . $archive_name . '.tar.bz2';

        if (!$input->getOption('force') && !$input->getOption('assume-yes') && file_exists($tarball)) {
            $this->io->warning("Archive $tarball already exists.");
            if (!$this->io->confirm('Do you want to rebuild it?', false)) {
                return Command::SUCCESS;
            }
        }

        // Build
        $ref = $this->commit ?: $release_version;
        $this->build($release_version, $ref, $tarball);

        // Sign
        if (!$input->getOption('nosign')) {
            $this->sign($tarball);
        }

        // GitHub Release
        if (!$input->getOption('nogithub')) {
            $this->createGithubRelease($release_version, $input_commit, $tarball);
        }

        return Command::SUCCESS;
    }

    private function guessPluginName(): string
    {
        $plugin_dir = $this->getPluginDirectory();
        $name = null;
        $setup_file = $plugin_dir . '/setup.php';
        if (file_exists($setup_file)) {
            $content = file_get_contents($setup_file);
            if (preg_match("/PLUGIN_(.+)_VERSION/", $content, $matches)) {
                $name = strtolower($matches[1]);
            }
        }
        if (!$name) {
            $name = basename($plugin_dir);
        }
        return $name;
    }

    private function compileMo(string $dir): void
    {
        $locales_dir = $dir . '/locales';
        $this->io->section("Compiling MO files");
        if ($this->output->isVerbose()) {
            $this->output->writeln(" <question>Locales dir: $locales_dir</question>");
        }

        if (is_dir($locales_dir)) {
            $files = glob($locales_dir . '/*.po');
            /** @var ConsoleSectionOutput $section */
            $section = $this->output->section();

            // Check msgfmt
            $process = new Process(['msgfmt', '--version']);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \RuntimeException('msgfmt not found!');
            }

            $section_messages = [];
            foreach ($files as $k => $file) {
                $mo = str_replace('.po', '.mo', $file);
                $section->writeln(" Compiling " . basename($file) . "...");
                $proc = new Process(['msgfmt', $file, '-o', $mo]);
                $proc->run();
                if (!$proc->isSuccessful()) {
                    $section_messages[$k] = " <error>Failed to compile $file</error>";
                } else {
                    $section_messages[$k] = " <info>Compiled " . basename($file) . "</info>";
                }
                $section->overwrite(implode("\n", $section_messages)); // Use for dynamic message display
            }
            $this->io->newLine();
        }
    }

    private function getNumericVersion(string $ver): array
    {
        preg_match_all('/\d+/', $ver, $matches);
        return $matches[0];
    }

    private function validVersion(string $ver): bool
    {
        $parts = $this->getNumericVersion($ver);
        return implode('.', $parts) === $ver;
    }

    private function checkVersion(string $build_ver): bool
    {
        $plugin_dir = $this->getPluginDirectory();
        $setup_file = $plugin_dir . '/setup.php';

        if ($this->output->isVerbose()) {
            $this->io->text("Checking for version $build_ver");
        }

        // Find version constant in setup.php
        $found = null;
        if (file_exists($setup_file)) {
            $content = file_get_contents($setup_file);
            $pattern = "/PLUGIN_" . strtoupper($this->plugin_name) . "_VERSION['\"], ['\"](.+)['\"]/";
            if (preg_match($pattern, $content, $matches)) {
                $found = $matches[1];
            }
        }

        if ($found !== $build_ver) {
            $this->io->error(sprintf('Plugin version check has failed (%s but %s found)!', $build_ver, $found ?? 'null'));
            return false;
        }

        // Check plugins website XML file
        $xml_file = $plugin_dir . '/' . $this->plugin_name . '.xml';
        if (!file_exists($xml_file)) {
            $xml_file = $plugin_dir . '/plugin.xml';
        }

        if (file_exists($xml_file)) {
            if ($this->output->isVerbose()) {
                $this->io->text("XML file found in $xml_file");
            }

            try {
                $xmldoc = new \SimpleXMLElement(file_get_contents($xml_file));
                $found = false;
                foreach ($xmldoc->versions->version as $version) {
                    if ((string) $version->num === $build_ver) {
                        if ($this->output->isVerbose()) {
                            $this->io->text("$build_ver found in the XML file!");
                        }
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $this->io->error("$build_ver *NOT* found in the XML file $xml_file");
                    return false;
                }
            } catch (\Exception $e) {
                $this->io->error("$xml_file is *NOT* XML valid: " . $e->getMessage());
                return false;
            }
        } else {
            $this->io->error('Plugins website configuration file has not been found!');
            return false;
        }

        return true;
    }

    protected function validCommit(string $commit_ref): bool
    {
        $plugin_dir = $this->getPluginDirectory();

        $process = new Process(['git', 'rev-parse', '--verify', $commit_ref], $plugin_dir);
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        $this->commit = trim(substr($process->getOutput(), 0, 10));

        // Display commit info if verbose
        if ($this->output->isVerbose()) {
            $show_process = new Process(['git', 'log', '-1', '--format=%H%n%an%n%ai%n%cn%n%ci%n%s', $commit_ref], $plugin_dir);
            $show_process->run();
            if ($show_process->isSuccessful()) {
                $lines = explode("\n", trim($show_process->getOutput()));
                $this->io->section("Commit informations:");
                $this->io->text([
                    "Hash:          " . ($lines[0] ?? ''),
                    "Author:        " . ($lines[1] ?? ''),
                    "Authored date: " . ($lines[2] ?? ''),
                    "Committer:     " . ($lines[3] ?? ''),
                    "Commit date:   " . ($lines[4] ?? ''),
                    "Message:       " . ($lines[5] ?? ''),
                ]);
            }
        }

        return true;
    }

    private function isExistingVersion(string $ver): bool
    {
        $tags = $this->getGitTags();
        return in_array($ver, $tags);
    }

    protected function getGitTags(): array
    {
        $plugin_dir = $this->getPluginDirectory();
        $process = new Process(['git', 'tag'], $plugin_dir);
        $process->run();
        if (!$process->isSuccessful()) {
            return [];
        }
        $tags = explode("\n", trim($process->getOutput()));
        return array_filter($tags);
    }

    private function getLatestVersion(): ?string
    {
        $tags = $this->getGitTags();
        if ($tags === []) {
            return null;
        }

        // Sort versions
        usort($tags, function ($a, $b) {
            if (!$this->validVersion($a)) {
                return -1;
            }
            if (!$this->validVersion($b)) {
                return 1;
            }
            return version_compare($a, $b);
        });

        return end($tags) ?: null;
    }

    private function proposeVersion(): void
    {
        $tags = $this->getGitTags();
        // Sort versions
        usort($tags, function ($a, $b) {
            if (!$this->validVersion($a)) {
                return -1;
            }
            if (!$this->validVersion($b)) {
                return 1;
            }
            return version_compare($a, $b);
        });

        $last = end($tags) ?: '0.0.0';

        // Logic from python script
        $last_minor = $last;
        $last_major = '0';

        // Find last major (where last digit is 0)
        foreach ($tags as $t) {
            if ($this->validVersion($t)) {
                $parts = explode('.', $t);
                if (end($parts) == '0') {
                    $last_major = str_replace('.0', '', $t);
                }
            }
        }

        if ($this->output->isVerbose()) {
            $this->io->text("Last minor: $last_minor | Last major: $last_major");
        }

        // New Minor
        $minor_parts = explode('.', $last_minor);
        if (end($minor_parts) == '0') {
            $new_minor = $last_minor . '.1';
        } else {
            $minor_parts[count($minor_parts) - 1]++;
            $new_minor = implode('.', $minor_parts);
        }

        // New Major
        $major_parts = $this->getNumericVersion($last_major);
        if ($major_parts === []) {
            $major_parts = [0];
        }
        $major_parts[count($major_parts) - 1]++;
        $new_major = implode('.', $major_parts) . '.0';

        $this->io->section("Proposed versions:");
        $this->io->text("Minor: $new_minor");
        $this->io->text("Major: $new_major");
    }

    private function build(string $ver, string $ref, string $dest): void
    {
        $plugin_dir = $this->getPluginDirectory();
        $this->io->section("Building glpi-{$this->plugin_name}-{$ver}");

        $type_str = ($ref !== $ver) ? 'Commit' : 'Tag';
        if ($this->output->isVerbose()) {
            $this->io->text("Release name: glpi-{$this->plugin_name}-{$ver}");
            $this->io->text("{$type_str}: {$ref}");
            $this->io->text("Dest: {$dest}");
        }

        // git ls-tree
        $process = new Process(['git', 'ls-tree', '-r', $ref, '--name-only'], $plugin_dir);
        $process->mustRun();
        $files = explode("\n", trim($process->getOutput()));

        // Filter banned
        $banned = $this->banned;
        $ignore_release_file = $plugin_dir . '/.ignore-release';
        if (file_exists($ignore_release_file)) {
            $lines = file($ignore_release_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $banned = array_merge($banned, $lines);
        }

        $valid_files = [];
        foreach ($files as $file) {
            if (empty($file)) {
                continue;
            }

            $excluded = false;
            foreach ($banned as $ban) {
                if (fnmatch($ban, $file) || fnmatch($ban, basename($file)) || preg_match('#^' . preg_quote($ban, '#') . '#', $file)) {
                    $excluded = true;
                    break;
                }
            }
            if (!$excluded) {
                $valid_files[] = $file;
            }
        }

        // Git archive
        $temp_tar = $this->dist_dir . '/temp.tar';
        $cmd = ['git', 'archive', '--prefix=' . $this->plugin_name . '/', '--output=' . $temp_tar, $ref];
        foreach ($valid_files as $f) {
            $cmd[] = $f;
        }

        $type_str = ($ref !== $ver) ? 'commit' : 'tag';
        $this->io->text("Archiving GIT {$type_str} {$ref}");

        $process = new Process($cmd, $plugin_dir);
        $process->setTimeout(600);
        $process->mustRun();

        // Now we need to prepare (extract, add vendors, re-compress)
        $src_dir = $this->dist_dir . '/src';
        $fs = new Filesystem();
        if (is_dir($src_dir)) {
            $fs->remove($src_dir);
        }
        mkdir($src_dir);

        $untar = new Process(['tar', '-xf', $temp_tar, '-C', $src_dir]);
        $untar->mustRun();
        unlink($temp_tar);

        $build_dir = $src_dir . '/' . $this->plugin_name;

        // Composer
        if (file_exists($build_dir . '/composer.json')) {
            /** @var ConsoleSectionOutput $section */
            $section = $this->output->section();

            $section->writeln(" Installing composer dependencies...");
            $c_cmd = ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'];
            if (!$this->output->isVerbose()) {
                $c_cmd[] = '--quiet';
            }

            $proc = new Process($c_cmd, $build_dir);
            $proc->setTimeout(300);
            $proc->mustRun();

            // Cleanup vendors
            $this->cleanupVendor($build_dir . '/vendor');

            // Dump autoload
            $proc = new Process(['composer', 'dump-autoload', '-o', '--no-dev'], $build_dir);
            $proc->mustRun();

            // Remove composer.lock
            if (file_exists($build_dir . '/composer.lock')) {
                unlink($build_dir . '/composer.lock');
            }
            $section->overwrite("<info> Composer dependencies installed.</info>");
        }

        // NPM
        if (file_exists($build_dir . '/package.json')) {
            /** @var ConsoleSectionOutput $section */
            $section = $this->output->section();

            $section->writeln(" Installing npm dependencies...");
            $n_cmd = ['npm', 'install'];
            $proc = new Process($n_cmd, $build_dir);
            $proc->setTimeout(600);
            $proc->mustRun();

            // Remove node_modules (assume npm install triggers postinstall build)
            $fs->remove($build_dir . '/node_modules');

            // Remove package-lock.json
            if (file_exists($build_dir . '/package-lock.json')) {
                unlink($build_dir . '/package-lock.json');
            }
            $section->overwrite("<info> npm dependencies installed.</info>");
        }

        // Compile MO
        $this->compileMo($build_dir);

        // Compress to bz2
        $this->io->section("Generating the archive");
        if ($this->output->isVerbose()) {
            $this->output->writeln(" <question>Target: $dest</question>\n");
        }

        $tar_cmd = ['tar', '-cjf', $dest, $this->plugin_name];
        $proc = new Process($tar_cmd, $src_dir);
        $proc->mustRun();

        // Cleanup src
        $fs->remove($src_dir);

        $this->io->success("Archive built: $dest");
    }

    private function cleanupVendor(string $vendor_dir): void
    {
        if (!is_dir($vendor_dir)) {
            return;
        }

        $fs = new Filesystem();
        $finder = new Finder();

        // Remove git directories
        $finder->directories()->in($vendor_dir)->name('.git*')->ignoreVCS(false);
        foreach ($finder as $dir) {
            $fs->remove($dir->getPathname());
        }

        // Remove test directories
        $finder = new Finder();
        $finder->directories()->in($vendor_dir)->name('test')->name('tests');
        foreach ($finder as $dir) {
            if (is_dir($dir->getPathname())) {
                $fs->remove($dir->getPathname());
            }
        }

        // Remove example directories
        $finder = new Finder();
        $finder->directories()->in($vendor_dir)->name('example')->name('examples');
        foreach ($finder as $dir) {
            if (is_dir($dir->getPathname())) {
                $fs->remove($dir->getPathname());
            }
        }

        // Remove doc directories
        $finder = new Finder();
        $finder->directories()->in($vendor_dir)->name('doc')->name('docs');
        foreach ($finder as $dir) {
            if (is_dir($dir->getPathname())) {
                $fs->remove($dir->getPathname());
            }
        }

        // Remove composer files in vendor subdirectories
        $finder = new Finder();
        $finder->files()->in($vendor_dir)->name('composer.*')->depth('> 0');
        foreach ($finder as $file) {
            $fs->remove($file->getPathname());
        }
    }

    private function sign(string $archive): void
    {
        $this->io->text("Signing archive...");
        $cmd = ['gpg', '--no-use-agent', '--detach-sign', '--armor', $archive];
        $proc = new Process($cmd);
        $proc->run();
        if (!$proc->isSuccessful()) {
            $this->io->error("Signing failed: " . $proc->getErrorOutput());
        } else {
            $this->io->success("Signed.");
        }
    }

    private function createGithubRelease(string $ver, ?string $commit, string $archive): void
    {
        $plugin_dir = $this->getPluginDirectory();
        $token_file = $plugin_dir . '/.gh_token';
        if (!file_exists($token_file)) {
            $this->io->error(".gh_token not found in plugin directory. Cannot release to GitHub.");
            return;
        }
        $token = trim(file_get_contents($token_file));

        $client = new Client([
            'base_uri' => 'https://api.github.com/',
            'headers'  => [
                'Authorization' => 'token ' . $token,
                'Accept'        => 'application/vnd.github.v3+json',
            ],
        ]);

        $repo = $this->gh_orga . '/' . $this->plugin_name;

        // Check if release exists
        $release = null;
        try {
            $resp = $client->get("repos/$repo/releases/tags/$ver");
            $release = json_decode($resp->getBody(), true);
            $this->io->text("Release $ver already exists. ID: " . $release['id']);
        } catch (\Exception $e) {
            // Not found - will create
        }

        if (!$release) {
            $this->io->text("Creating release $ver...");
            try {
                $resp = $client->post("repos/$repo/releases", [
                    'json' => [
                        'tag_name'         => $ver,
                        'target_commitish' => $commit ?: '',
                        'name'             => "GLPI {$this->plugin_name} $ver",
                        'body'             => 'Automated release from release script',
                        'draft'            => true,
                        'prerelease'       => (bool) $commit,
                    ],
                ]);
                $release = json_decode($resp->getBody(), true);
            } catch (\Exception $e) {
                $this->io->error("Failed to create release: " . $e->getMessage());
                if ($e instanceof ClientException) {
                    $this->io->error($e->getResponse()->getBody()->getContents());
                }
                return;
            }
        }

        // Upload Asset
        if ($this->io->confirm("Upload archive $archive?", true)) {
            $asset_name = basename($archive);
            $upload_url = str_replace('{?name,label}', '', $release['upload_url']);
            $this->io->text("Uploading $asset_name to $upload_url...");

            try {
                $client->post($upload_url, [
                    'headers' => [
                        'Content-Type' => 'application/octet-stream',
                    ],
                    'query'   => ['name' => $asset_name],
                    'body'    => fopen($archive, 'r'),
                ]);
                $this->io->success("Asset uploaded.");
            } catch (\Exception $e) {
                $this->io->error("Failed to upload asset: " . $e->getMessage());
            }
        }
    }
}
