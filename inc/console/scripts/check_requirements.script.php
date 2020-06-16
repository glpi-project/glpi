<?php
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

$requirements_manager = new \Glpi\System\RequirementsManager();
$core_requirements = $requirements_manager->getCoreRequirementList(
   class_exists('DB') ? new DB() : null
);

$output = new ConsoleOutput();

if (isset($_SERVER['argv']) && in_array('glpi:system:check_requirements', $_SERVER['argv'])) {
   // Display requirements checks results.

   $informations = new Table($output);
   $informations->setHeaders(
      [
         __('Requirement'),
         __('Status'),
         __('Messages'),
      ]
   );

   /* @var \Glpi\System\Requirement\RequirementInterface $requirement */
   foreach ($core_requirements as $requirement) {
      if ($requirement->isOutOfContext()) {
         continue; // skip requirement if not relevant
      }

      if ($requirement->isValidated()) {
         $status = sprintf('<%s>[%s]</>', 'fg=black;bg=green', __('OK'));
      } else {
         $status = $requirement->isOptional()
            ? sprintf('<%s>[%s]</> ', 'fg=white;bg=yellow', __('WARNING'))
            : sprintf('<%s>[%s]</> ', 'fg=white;bg=red', __('ERROR'));
      }

      $informations->addRow(
         [
            $requirement->getTitle(),
            $status,
            $requirement->isValidated() ? '' : implode("\n", $requirement->getValidationMessages())
         ]
      );
   }

   $informations->render();

   exit(0); // Exit with success code
} else if ($core_requirements->hasMissingMandatoryRequirements()) {
   // Prevent execution if a mandatory requirement is missing.
   $message = __('Some mandatory system requirements are missing.')
      . ' '
      . __('Run "php bin/console glpi:system:check_requirements" for more details.');
   $output->writeln(
      '<error>' . $message . '</error>',
      OutputInterface::VERBOSITY_QUIET
   );

   exit(1); // Exit with error code
} else if ($core_requirements->hasMissingOptionalRequirements()) {
   // Warn about missing optional requirement.
   $message = __('Some optional system requirements are missing.')
      . ' '
      . __('Run "php bin/console glpi:system:check_requirements" for more details.');
   $output->writeln(
      '<comment>' . $message . '</comment>',
      OutputInterface::VERBOSITY_NORMAL
   );
   $output->writeln(''); // Add empty line for spacing
}
