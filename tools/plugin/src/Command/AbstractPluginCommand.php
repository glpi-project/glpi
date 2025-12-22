<?php

namespace Glpi\Tools\Plugin\Command;

use Glpi\Tools\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


abstract class AbstractPluginCommand extends AbstractCommand
{


    protected function configure(): void
    {
        $this->addOption(
            'plugin',
            'p',
            InputOption::VALUE_REQUIRED,
            'Plugin name'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $plugin_name = $this->input->getOption('plugin');
        if (!$plugin_name) {
            throw new InvalidOptionException('The "--plugin" option is required.');
        }

        $root_dir = dirname(__DIR__, 4);
        $plugin_dir = $root_dir . '/plugins/' . $plugin_name;

        if (!is_dir($plugin_dir)) {
            throw new RuntimeException(
                sprintf('Plugin directory "%s" not found.', $plugin_dir)
            );
        }
    }

    protected function getPluginDirectory(): string
    {
        $plugin_name = $this->input->getOption('plugin');
        $root_dir = dirname(__DIR__, 4);
        return $root_dir . '/plugins/' . $plugin_name;
    }
}
