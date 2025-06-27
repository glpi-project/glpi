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
use Glpi\Form\Form;
use Glpi\UI\IllustrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateFormForeachIllustrationsCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('tools:generate_forms_for_each_illustrations');
        $this->setDescription(__("Generate one form per available illustration."));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new IllustrationManager();
        $illustrations = $manager->getAllIconsIds();

        foreach ($illustrations as $illustration) {
            if ($this->demoFormExistsForIllustration($illustration)) {
                $output->writeln("A form already exist for $illustration.");
                continue;
            }

            $this->createDemoFormForIllustration($illustration);
            $output->writeln("Created a form for $illustration.");
        }

        return Command::SUCCESS;
    }

    private function demoFormExistsForIllustration(string $illustration): bool
    {
        $forms = (new Form())->find([
            'description' => "Demo form for the '$illustration' illustration.",
        ]);

        return count($forms) > 0;
    }

    private function createDemoFormForIllustration(string $illustration): void
    {
        (new Form())->add([
            'name'         => $this->normalizeIllustrationName($illustration),
            'description'  => "Demo form for the '$illustration' illustration.",
            'illustration' => $illustration,
            'entities_id'  => 0,
            'is_recursive' => true,
            'is_active'    => true,
        ]);
    }

    private function normalizeIllustrationName(string $illustration): string
    {
        // Transform "my-illustration.svg' into "My illustration".
        $illustration = substr($illustration, 0, -4);
        $illustration = str_replace('-', ' ', $illustration);

        return ucfirst($illustration);
    }
}
