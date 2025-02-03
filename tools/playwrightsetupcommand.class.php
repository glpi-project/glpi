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

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PlaywrightSetupCommand extends AbstractCommand
{
    private const ARCHIVE_ENTITY = 'Playwright archives';
    private const MAIN_ENTITY = 'Playwright';
    private const WORKER_ENTITY = 'Playwright worker';
    private const WORKER_USER = 'playwright_worker';

    private int $workers;
    private array $entities = [];

    #[Override]
    protected function configure()
    {
        parent::configure();

        $this->setName('tools:playwright:setup');
        $this->setDescription(__("Setup the needed API access, users and entities for playwright tests."));
        $this->addArgument('workers', InputArgument::REQUIRED);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workers = $input->getArgument('workers');

        // Needed because of check in entity pre creation and user post creation
        // that depends on the current session.
        $_SESSION['glpicronuserrunning'] = true;

        // Needed to avoid warnings.
        $_SESSION['glpiactiveentities_string'] = '';

        // Clean previous tests fixtures and store data that can't be deleted
        // into a specific archive entity.
        $this->createArchiveEntity();
        $this->clearPreviousTestsData();
        $this->clearUsers();

        // Enable API and create fixtures.
        $this->enableApi();
        $this->createMainEntity();
        $this->createWorkerEntities();
        $this->createWorkerUsers();

        return Command::SUCCESS;
    }

    private function clearPreviousTestsData(): void
    {
        $entity = (new Entity())->find([
            'name' => self::MAIN_ENTITY,
        ]);

        if (count($entity) === 0) {
            // Nothing to delete
            return;
        } elseif (count($entity) > 1) {
            // This can never happens
            throw new LogicException();
        } else {
            $row = current($entity);
            $find_children_stack = [$row['id']];
            $to_delete_stack = [];

            // Find all sub entities that needs to be deleted
            while (!empty($find_children_stack)) {
                $entity_id = array_pop($find_children_stack);
                $to_delete_stack[] = $entity_id;

                $entities = (new Entity())->find([
                    'entities_id' => $entity_id,
                ]);
                foreach ($entities as $child_row) {
                    $find_children_stack[] = $child_row['id'];
                }
            }

            while (!empty($to_delete_stack)) {
                $entity_id = array_pop($to_delete_stack);
                $delete = (new Entity())->delete([
                    'id' => $entity_id,
                    '_replace_by' => $this->entities[self::ARCHIVE_ENTITY],
                ], force: true);
                if (!$delete) {
                    throw new Exception("Failed to delete old entities");
                }
            }
        }
    }

    private function clearUsers()
    {
        $users = (new User())->find([
            ['name' => ['LIKE', self::WORKER_USER . "%"]]
        ]);

        foreach ($users as $row) {
            $delete = (new User())->delete([
                'id' => $row['id'],
            ], force: true);
            if (!$delete) {
                throw new Exception("Failed to delete user");
            }
        }
    }

    private function enableApi(): void
    {
        // Enable API
        Config::setConfigurationValues('core', [
            'enable_api' => 1,
            'enable_api_login_credentials' => 1,
        ]);
    }

    private function createArchiveEntity(): void
    {
        $entities = (new Entity())->find([
            'name' => self::ARCHIVE_ENTITY,
        ]);
        if (count($entities) == 1) {
            $row = current($entities);
            $this->entities[self::ARCHIVE_ENTITY] = $row['id'];
            return;
        }

        $entity_id = (new Entity())->add([
            'name' => self::ARCHIVE_ENTITY,
            'entities_id'  => 0,
        ]);
        if (!$entity_id) {
            throw new Exception('Failed to create archive entity');
        }
        $this->entities[self::ARCHIVE_ENTITY] = $entity_id;
    }

    private function createMainEntity(): void
    {
        $entity_id = (new Entity())->add([
            'name' => self::MAIN_ENTITY,
            'entities_id'  => 0,
        ]);
        if (!$entity_id) {
            throw new Exception('Failed to create main test entity');
        }
        $this->entities[self::MAIN_ENTITY] = $entity_id;
    }

    private function createWorkerEntities(): void
    {
        for ($i = 0; $i < $this->workers; $i++) {
            $entity_id = (new Entity())->add([
                'name' => self::WORKER_ENTITY . " $i",
                'entities_id' => $this->entities[self::MAIN_ENTITY],
            ]);
            if (!$entity_id) {
                throw new Exception('Failed to create main test entity');
            }
            $this->entities[self::WORKER_ENTITY . " $i"] = $entity_id;
        }
    }

    private function createWorkerUsers(): void
    {
        $profiles_to_add = [
            1, // Self-Service
            2, // Observer
            3, // Admin
            5, // Hotliner
            6, // Technician
            7, // Supervisor
            8, // Read-Only
        ];

        for ($i = 0; $i < $this->workers; $i++) {
            $user_id = (new User())->add([
                'name'          => self::WORKER_USER . "_$i",
                'firstname'     => "Worker $i",
                'realname'      => "Playwright",
                'password'      => self::WORKER_USER . "_$i",
                'password2'     => self::WORKER_USER . "_$i",
                '_profiles_id'  => 4, // Super-admin
                '_entities_id'  => $this->entities[self::WORKER_ENTITY . " $i"],
                '_is_recursive' => true,
                'profiles_id'   => 4,

                // Avoid loading the heavy dashboard page on login.
                'default_central_tab' => 4, // Rss feeds
            ]);
            if (!$user_id) {
                throw new Exception('Failed to create worker user');
            }

            foreach ($profiles_to_add as $profile_to_add) {
                $profile_user_id = (new Profile_User())->add([
                    'profiles_id'  => $profile_to_add,
                    'users_id'     => $user_id,
                    'entities_id'  => $this->entities[self::WORKER_ENTITY . " $i"],
                    'is_recursive' => true,
                ]);
                if (!$profile_user_id) {
                    throw new Exception('Failed to assign profile');
                }
            }
        }
    }
}
