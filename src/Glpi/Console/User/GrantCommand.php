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

namespace Glpi\Console\User;

use Entity;
use Profile;
use Profile_User;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use User;

class GrantCommand extends AbstractUserCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('user:grant');
        $this->setDescription(__('Grant a profile assignment to a user'));

        $this->addOption('profile', 'p', InputOption::VALUE_REQUIRED, Profile::getTypeName(1));
        $this->addOption('entity', 'e', InputOption::VALUE_REQUIRED, Entity::getTypeName(1), 0);
        $this->addOption('recursive', 'r', InputOption::VALUE_NONE, __('Recursive'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $username = $input->getArgument('username');
        $profile = $input->getOption('profile');
        $entity = $input->getOption('entity');
        $recursive = $input->getOption('recursive');

        $profile_obj = new Profile();
        $entity_obj = new Entity();
        if (!$profile_obj->getFromDB($profile)) {
            $output->writeln('<error>' . __('Profile not found') . '</error>');
            return 1;
        }
        if (!$entity_obj->getFromDB($entity)) {
            $output->writeln('<error>' . __('Entity not found') . '</error>');
            return 1;
        }

        $user = new User();
        if (!$user->getFromDBbyName($username)) {
            $output->writeln('<error>' . __('User not found') . '</error>');
            return 1;
        }

        $profile_user = new Profile_User();
        $profile_user_input = [
            'users_id' => $user->getID(),
            'profiles_id' => $profile_obj->getID(),
            'entities_id' => $entity_obj->getID(),
            'is_recursive' => $recursive,
        ];
        if ($profile_user->add($profile_user_input)) {
            $output->writeln('<info>' . __('Profile granted') . '</info>');
            return 0;
        } else {
            $output->writeln('<error>' . __('Failed to grant profile') . '</error>');
            return 1;
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        // Ask for the profile if not provided and provide a list of available profiles
        if (!$input->getOption('profile')) {
            $input->setOption('profile', $this->askForProfile($input, $output));
        }
    }

    private function askForProfile(InputInterface $input, OutputInterface $output): string
    {
        global $DB;

        $profiles = [];
        $it = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM' => Profile::getTable(),
        ]);
        foreach ($it as $row) {
            $profiles[$row['id']] = $row['name'];
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion(Profile::getTypeName(1), $profiles);
        return $helper->ask($input, $output, $question);
    }
}
