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

namespace Glpi\Console\User;

use Glpi\Console\AbstractCommand;
use Profile;
use Profile_User;
use Session;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use User;
use UserTitle;

class ImportFromSrmCommand extends AbstractCommand
{
    private \PDO $srm_pdo;

    private array $report = [];

    private int $created_count = 0;

    private int $updated_count = 0;

    private int $skipped_count = 0;

    private array $usertitles_cache = [];

    protected function configure(): void
    {
        parent::configure();

        $this->setName('user:import_from_srm');
        $this->setDescription(__('Import or synchronize users from SRM (SQL Server) database'));

        $this->addOption('dry-run', null, InputOption::VALUE_NONE, __('Only list what would be done, without making any changes'));
        $this->addOption('only-create', null, InputOption::VALUE_NONE, __('Only create new users, skip existing ones'));
        $this->addOption('only-update', null, InputOption::VALUE_NONE, __('Only update existing users, skip new ones'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->connectSrmDatabase();

        $only_create = $input->getOption('only-create');
        $only_update = $input->getOption('only-update');
        $dry_run = $input->getOption('dry-run');

        if ($only_create && $only_update) {
            $output->writeln('<error>' . __('--only-create and --only-update cannot be used together') . '</error>');
            return 1;
        }

        $rows = $this->fetchSrmUsers();

        if (empty($rows)) {
            $output->writeln('<info>' . __('No users found in SRM database') . '</info>');
            return 0;
        }

        $output->writeln(
            sprintf('<info>' . __('Found %d users in SRM database') . '</info>', count($rows))
        );

        $this->loadUsertitlesCache();

        if ($dry_run) {
            $output->writeln('<comment>' . __('DRY RUN — no changes will be made') . '</comment>');
        }

        foreach ($this->iterate($rows) as $row) {
            $this->processUser($row, $only_create, $only_update, $dry_run);
        }

        $this->outputSummary($output);
        $this->generateReport($dry_run);

        return 0;
    }

    private function connectSrmDatabase(): void
    {
        $dsn = 'dblib:host=SRM;dbname=CONTROLE_SRM_TESTE';
        $this->srm_pdo = new \PDO($dsn, 'oberdan.brito', 'qe446pnh@', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    private function fetchSrmUsers(): array
    {
        $sql = "SELECT * FROM dbo.vw_glpi_usuarios ORDER BY name";
        $stmt = $this->srm_pdo->query($sql);
        $rows = $stmt->fetchAll();

        foreach ($rows as $i => $row) {
            $rows[$i]['name'] = mb_strtolower(trim($row['name'] ?? ''));
            $rows[$i]['realname'] = trim($row['realname'] ?? '');
            $rows[$i]['firstname'] = trim($row['firstname'] ?? '');
            $rows[$i]['mobile'] = trim($row['mobile'] ?? '');
            $rows[$i]['phone2'] = trim($row['phone2'] ?? '');
            $rows[$i]['registration_number'] = trim($row['registration_number'] ?? '');
            $rows[$i]['usertitle_name'] = trim($row['usertitle_name'] ?? '');
        }

        return $rows;
    }

    private function processUser(array $row, bool $only_create, bool $only_update, bool $dry_run): void
    {
        global $DB;

        $registration_number = $row['registration_number'];
        $name = $row['name'];

        $existing_user_id = null;

        if (!empty($registration_number)) {
            $iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM' => User::getTable(),
                'WHERE' => ['registration_number' => $registration_number],
            ]);
            foreach ($iterator as $data) {
                $existing_user_id = (int) $data['id'];
                break;
            }
        }

        if ($existing_user_id !== null) {
            if ($only_create) {
                $this->report[] = [
                    'name' => $name,
                    'password' => '',
                    'realname' => $row['realname'],
                    'firstname' => $row['firstname'],
                    'registration_number' => $registration_number,
                    'status' => 'skipped (--only-create)',
                ];
                ++$this->skipped_count;
                return;
            }

            $this->updateUser($existing_user_id, $row, $dry_run);
        } else {
            if ($only_update) {
                $this->report[] = [
                    'name' => $name,
                    'password' => '',
                    'realname' => $row['realname'],
                    'firstname' => $row['firstname'],
                    'registration_number' => $registration_number,
                    'status' => 'skipped (--only-update)',
                ];
                ++$this->skipped_count;
                return;
            }

            $this->createUser($row, $dry_run);
        }
    }

    private function createUser(array $row, bool $dry_run): void
    {
        $name = $row['name'];
        $registration_number = $row['registration_number'];

        $existing = new User();
        $collision_suffix = '';
        if ($existing->getFromDBbyName($name)) {
            $suffix = 2;
            while ($existing->getFromDBbyName($name . '.' . $suffix)) {
                ++$suffix;
            }
            $name = $name . '.' . $suffix;
            $collision_suffix = ' (renamed to ' . $name . ')';
        }

        $password = bin2hex(random_bytes(6));

        if ($dry_run) {
            $this->report[] = [
                'name' => $name,
                'password' => $password,
                'realname' => $row['realname'],
                'firstname' => $row['firstname'],
                'registration_number' => $registration_number,
                'status' => 'would_create' . $collision_suffix,
            ];
            ++$this->created_count;
            return;
        }

        $input = [
            'name' => $name,
            'password' => $password,
            'password2' => $password,
            'realname' => $row['realname'],
            'firstname' => $row['firstname'],
            'mobile' => $row['mobile'],
            'phone2' => $row['phone2'],
            'locations_id' => 1,
            'language' => 'pt_BR',
            'is_active' => 1,
            'date_sync' => date('Y-m-d H:i:s'),
            'entities_id' => 0,
            'begin_date' => date('Y-m-d H:i:s'),
            'date_creation' => date('Y-m-d H:i:s'),
            'registration_number' => $registration_number,
            'date_format' => 1,
            'usertitles_id' => $this->resolveUsertitleId($row['usertitle_name'] ?? ''),
            'number_format' => 4,
            'names_format' => 1,
            '_profiles_id' => 1,
            '_entities_id' => 0,
            '_is_recursive' => 1,
        ];

        $user = new User();
        $success = Session::callAsSystem(fn() => $user->add($input));

        if ($success) {
            $this->report[] = [
                'name' => $name,
                'password' => $password,
                'realname' => $row['realname'],
                'firstname' => $row['firstname'],
                'registration_number' => $registration_number,
                'status' => 'created' . $collision_suffix,
            ];
            ++$this->created_count;
        } else {
            $this->report[] = [
                'name' => $name,
                'password' => '',
                'realname' => $row['realname'],
                'firstname' => $row['firstname'],
                'registration_number' => $registration_number,
                'status' => 'error — add() returned false',
            ];
            ++$this->skipped_count;

            $this->writelnOutputWithProgressBar(
                '<error>' . sprintf(__('Failed to create user %s'), $name) . '</error>',
                $this->progress_bar
            );
        }
    }

    private function updateUser(int $user_id, array $row, bool $dry_run): void
    {
        $name = $row['name'];
        $registration_number = $row['registration_number'];

        if ($dry_run) {
            $this->report[] = [
                'name' => $name,
                'password' => '',
                'realname' => $row['realname'],
                'firstname' => $row['firstname'],
                'registration_number' => $registration_number,
                'status' => 'would_update',
            ];
            ++$this->updated_count;
            return;
        }

        $input = [
            'id' => $user_id,
            'realname' => $row['realname'],
            'firstname' => $row['firstname'],
            'mobile' => $row['mobile'],
            'phone2' => $row['phone2'],
            'is_active' => 1,
            'date_sync' => date('Y-m-d H:i:s'),
            'registration_number' => $registration_number,
            'usertitles_id' => $this->resolveUsertitleId($row['usertitle_name'] ?? ''),
        ];

        $user = new User();
        $success = Session::callAsSystem(fn() => $user->update($input));

        if ($success) {
            $this->report[] = [
                'name' => $name,
                'password' => '',
                'realname' => $row['realname'],
                'firstname' => $row['firstname'],
                'registration_number' => $registration_number,
                'status' => 'updated',
            ];
            ++$this->updated_count;
        } else {
            $this->report[] = [
                'name' => $name,
                'password' => '',
                'realname' => $row['realname'],
                'firstname' => $row['firstname'],
                'registration_number' => $registration_number,
                'status' => 'error — update() returned false',
            ];
            ++$this->skipped_count;

            $this->writelnOutputWithProgressBar(
                '<error>' . sprintf(__('Failed to update user %s (id=%d)'), $name, $user_id) . '</error>',
                $this->progress_bar
            );
        }
    }

    private function loadUsertitlesCache(): void
    {
        global $DB;

        $it = $DB->request(['FROM' => UserTitle::getTable()]);
        foreach ($it as $row) {
            $this->usertitles_cache[mb_strtoupper(trim($row['name']))] = (int) $row['id'];
        }
    }

    private function resolveUsertitleId(?string $title_name): int
    {
        if (empty($title_name)) {
            return 0;
        }

        $key = mb_strtoupper(trim($title_name));

        if (isset($this->usertitles_cache[$key])) {
            return $this->usertitles_cache[$key];
        }

        $title = new UserTitle();
        $tid = $title->add(['name' => trim($title_name)]);
        if ($tid) {
            $this->usertitles_cache[$key] = (int) $tid;
            return (int) $tid;
        }

        return 0;
    }

    private function outputSummary(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln(sprintf('<info>' . __('Created: %d') . '</info>', $this->created_count));
        $output->writeln(sprintf('<info>' . __('Updated: %d') . '</info>', $this->updated_count));
        $output->writeln(sprintf('<comment>' . __('Skipped: %d') . '</comment>', $this->skipped_count));
    }

    private function generateReport(bool $dry_run): void
    {
        $base_path = defined('GLPI_ROOT') ? GLPI_ROOT : dirname(__DIR__, 4);
        $suffix = $dry_run ? '_dryrun' : '';
        $filename = sprintf(
            '%s/files/_import_users_%s%s.csv',
            $base_path,
            date('Ymd_His'),
            $suffix
        );

        $handle = fopen($filename, 'w');
        if ($handle === false) {
            $this->output->writeln(
                '<error>' . sprintf(__('Failed to create report file: %s'), $filename) . '</error>'
            );
            return;
        }

        fputcsv($handle, ['name', 'password', 'realname', 'firstname', 'registration_number', 'status']);

        foreach ($this->report as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->output->writeln(
            '<info>' . sprintf(__('Report saved to: %s'), $filename) . '</info>'
        );
    }
}
