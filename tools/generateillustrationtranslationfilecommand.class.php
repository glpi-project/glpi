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

use Glpi\UI\IllustrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateIllustrationTranslationFileCommand extends Command
{
    protected $requires_db = false;

    #[Override]
    protected function configure()
    {
        parent::configure();

        $this->setName('tools:generate_illustration_translations');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $content = "<?php" . PHP_EOL . PHP_EOL;

        $manager = new IllustrationManager();
        foreach ($manager->getAllIconsTitles() as $title) {
            $title = addslashes($title);
            $content .= '_x("Icon", "' . $title . '");' . PHP_EOL;
        }

        foreach ($manager->getAllIconsTags() as $tag) {
            $tag = addslashes($tag);
            $content .= '_x("Icon", "' . $tag . '");' . PHP_EOL;
        }

        $written_bytes = file_put_contents(
            IllustrationManager::TRANSLATION_FILE,
            $content
        );
        if ($written_bytes !== strlen($content)) {
            throw new RuntimeException('Unable to write the illustration translations file contents.');
        }

        $output->writeln('Illustration translations file generated successfully.');

        return Command::SUCCESS;
    }
}
