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
use GLPIKey;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use User;

class VerifyUsersTokensCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('user:verifytokens');
        $this->setDescription(__('Verify integrity of users tokens'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $token_fields = ['api_token'];

        foreach ($token_fields as $token_field) {
            $token_name = explode("_", $token_field)[0];
            $output->writeln(
                '<info>' . sprintf(__('Checking %s tokens'), $token_name) . '</info>'
            );
            $users_with_token_in_error = $this->checkTokens($token_field, $token_name);
            if ($users_with_token_in_error === []) {
                continue;
            }
            $output->writeln('<comment>' . __('Asking for permission to fix the malformed tokens') . '</comment>');
            $this->askForConfirmation();
            $outcome = $this->fixTokens($users_with_token_in_error, $token_field, $token_name);
            if ($outcome === 1) {
                // If we encountered an error, we stop the process.
                return $outcome;
            }
        }
        return 0;
    }

    /**
     * Checks for malformed tokens in User table
     * @param string $token_field Name of the field in User database table.
     * @param string $token_name Display name of the token.
     * @phpstan-return array<string> A list of users ids with malformed tokens.
     */
    private function checkTokens(string $token_field, string $token_name): array
    {
        global $DB;

        $glpi_key = new GLPIKey();

        $iterator = $DB->request([
            'SELECT' => ['id', $token_field],
            'FROM'   => User::getTable(),
            'WHERE'  => [
                ['NOT' => [$token_field => null]],
                ['NOT' => [$token_field => '']],
            ],
        ]);

        $users_with_token_in_error = [];
        foreach ($iterator as $row) {
            if ($glpi_key->decrypt($row[$token_field]) === '') {
                $users_with_token_in_error[] = $row['id'];
            }
        }

        if ($users_with_token_in_error === []) {
            $this->output->writeln(
                '<info>' . sprintf(__('No malformed %s token found, all good !'), $token_name) . '</info>'
            );
            return $users_with_token_in_error;
        }

        $count = count($users_with_token_in_error);
        $this->output->writeln(
            '<comment>' . sprintf(
                _n('Found %d user with a malformed %s token.', 'Found %d users with a malformed %s token.', $count),
                $count,
                $token_name
            ) . '</comment>'
        );

        $table = new Table($this->output);
        $table->setHeaders(['User ID']);
        foreach ($users_with_token_in_error as $user_id) {
            $table->addRow([$user_id]);
        }
        $table->render();

        return $users_with_token_in_error;
    }

    /**
     * Fix tokens in a given array
     * @param array<string> $users_with_token_in_error A list of user ids for which the token is in error
     * @param string $token_field Name of the field in User database table.
     * @param string $token_name The display name of the token
     * @return int 1 in case of error, 0 otherwise
     */
    private function fixTokens(array $users_with_token_in_error, string $token_field, string $token_name): int
    {
        $failed = 0;
        $user = new User();
        $progress_message = fn(int $user_id) => sprintf(__('Regenerating token for user %d...'), $user_id);
        $regenerate_field = "_regenerate_{$token_field}";
        foreach ($this->iterate($users_with_token_in_error, $progress_message) as $user_id) {
            $success = $user->update([
                'id'                    => $user_id,
                $regenerate_field       => true,
            ]);
            if (!$success) {
                $this->outputMessage(
                    '<error>' . sprintf(__('Failed to regenerate %s token for user %d.'), $token_name, $user_id) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->output->writeln(
                '<error>' . sprintf(_n('Failed to fix %d %s token.', 'Failed to fix %d %s tokens.', $failed), $failed, $token_name) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return 1;
        }

        $this->output->writeln(
            '<info>' . sprintf(__('All malformed %s tokens have been regenerated.'), $token_name) . '</info>'
        );
        return 0;
    }
}
