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

use GLPIKey;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Glpi\Console\AbstractCommand;
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
        $token_fields = ['api_token', "cookie_token"];

        foreach($token_fields as $token_field){
            $token_name = explode("_", $token_field)[0];
            $this->output->writeln(
                '<info>'. sprintf(__('Checking %s tokens'), $token_name) .'</info>'
            );
            $tokens_in_error = $this->checkTokens($token_field, $token_name);
            if (empty($tokens_in_error)) {
                continue;
            }
            $this->output->writeln('<comment>' . __('Asking for permission to fix the malformed tokens') . '</comment>');
            $this->askForConfirmation();
            $outcome = $this->fixTokens($tokens_in_error, $token_name);
            if ($outcome === 1) {
                // If we encountered an error, we stop the process.
                return $outcome;
            }
        }
        return 0;
    }

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

        $tokens_in_error = [];
        foreach($iterator as $row){
            if ($glpi_key->decrypt($row[$token_field]) === ''){
                $tokens_in_error[] = $row;
            }
        }

        if (empty($tokens_in_error)){
            $this->output->writeln(
                '<info>' . sprintf(__('No malformed %s token found, all good !'), $token_name) . '</info>'
            );
            return $tokens_in_error;
        }

        $count = count($tokens_in_error);
        $this->output->writeln(
            '<comment>' . sprintf(
                _n('Found %d user with a malformed %s token.', 'Found %d users with a malformed %s token.', $count),
                $count,
                $token_name
            ) . '</comment>'
        );

        $table = new Table($this->output);
        $table->setHeaders(['User ID']);
        foreach ($tokens_in_error as $row) {
            $table->addRow([$row['id']]);
        }
        $table->render();

        return $tokens_in_error;
    }

    private function fixTokens(array $tokens_in_error, string $token_name): int
    {
        $failed = 0;
        $user = new User();
        $progress_message = fn(array $row) => sprintf(__('Regenerating token for user %d...'), $row['id']);
        foreach ($this->iterate($tokens_in_error, $progress_message) as $row) {
            $success = $user->update([
                'id'                    => $row['id'],
                '_regenerate_api_token' => true,
            ]);
            if (!$success) {
                $this->outputMessage(
                    '<error>' . sprintf(__('Failed to regenerate %s token for user %d.'), $token_name, $row['id']) . '</error>',
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
