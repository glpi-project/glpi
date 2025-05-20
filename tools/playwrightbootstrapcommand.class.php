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

final class PlaywrightBootstrapCommand extends AbstractCommand
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

        $this->setName('tools:playwright:bootstrap');
        $this->setDescription(__("Setup the needed API access, users and entities for playwright tests."));
        $this->addArgument('workers', InputArgument::REQUIRED);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workers = $input->getArgument('workers');

        // Needed because of a check before entity creation and after user
        // creation that depends on the current session ("callAsSystem" don't
        // work here).
        $_SESSION['glpicronuserrunning'] = true;

        // Needed to avoid warnings.
        $_SESSION['glpiactiveentities_string'] = '';

        // Enable API and create fixtures.
        $this->enableApi();
        $this->createMainEntity();
        $this->createWorkerEntities();
        $this->createWorkerUsers();

        return Command::SUCCESS;
    }

    private function enableApi(): void
    {
        // Enable API
        Config::setConfigurationValues('core', [
            'enable_api' => 1,
            'enable_api_login_credentials' => 1,
        ]);

        // Add a client that will allow sending http requests from outside docker.
        $client = $this->findItemByName(new APIClient(), "Playwright tests");
        if ($client) {
            return;
        }

        $client_id = (new APIClient())->add([
            'name'             => "Playwright tests",
            'is_active'        => 1,
            'app_token'        => "",
            // Allow all ips
            'ipv4_range_start' => null,
            'ipv4_range_end'   => null,
        ]);
        if (!$client_id) {
            throw new Exception('Failed to create API client');
        }
    }

    private function createMainEntity(): void
    {
        // Skip if already created
        $entity = $this->findItemByName(new Entity(), self::MAIN_ENTITY);
        if ($entity) {
            $this->entities[self::MAIN_ENTITY] = $entity->getID();
            return;
        }

        // Create entity
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
            $name = self::WORKER_ENTITY . " $i";

            // Skip if already created
            $entity = $this->findItemByName(new Entity(), $name);
            if ($entity) {
                $this->entities[$name] = $entity->getID();
                continue;
            }

            // Create entity
            $entity_id = (new Entity())->add([
                'name' => $name,
                'entities_id' => $this->entities[self::MAIN_ENTITY],
            ]);
            if (!$entity_id) {
                throw new Exception('Failed to create main test entity');
            }
            $this->entities[$name] = $entity_id;
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
            $name = self::WORKER_USER . "_$i";

            // Skip if already created
            $user = $this->findItemByName(new User(), $name);
            if ($user) {
                continue;
            }

            $user_id = (new User())->add([
                'name'          => $name,
                'firstname'     => "Worker $i",
                'realname'      => "Playwright",
                'password'      => $name,
                'password2'     => $name,
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

            // Add each profiles
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

    private function findItemByName(CommonDBTM $item, string $name): ?CommonDBTM
    {
        $res = $item->getFromDBByCrit(['name' => $name]);
        if (!$res) {
            return null;
        }

        return $item;
    }
}
