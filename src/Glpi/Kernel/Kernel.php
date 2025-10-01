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

namespace Glpi\Kernel;

use Glpi\Application\Environment;
use Glpi\Application\SystemConfigurator;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Throwable;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private LoggerInterface $logger;

    private bool $in_reboot = false;

    public function __construct(?string $env = null)
    {
        // Initialize system configuration.
        // It must be done after the autoload inclusion that requires some constants to be defined (e.g. GLPI_VERSION).
        // It must be done before the Kernel boot as some of the define constants must be defined during the boot sequence
        // and as it initializes the error handler that will catch errors that may happen during the boot sequence.
        $configurator = new SystemConfigurator($this->getProjectDir(), $env);
        $this->logger = $configurator->getLogger();

        $env = Environment::get();
        parent::__construct(
            $env->value,
            // `debug: true` will ensure that cache is recompiled everytime a corresponding resource is updated.
            // Reserved for dev/test environments as it consumes many disk I/O.
            debug: $env->shouldExpectResourcesToChange(),
        );
    }

    public function __destruct()
    {
        $this->triggerGlobalsDeprecation();
    }

    /**
     * Returns the cache root directory.
     */
    public static function getCacheRootDir(): string
    {
        // FIXME: Inject it as a DI parameter when corresponding services will be instanciated from the DI system.
        return GLPI_CACHE_DIR . '/' . GLPI_FILES_VERSION . '-' . Environment::get()->value;
    }

    #[Override()]
    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    #[Override()]
    public function getCacheDir(): string
    {
        return self::getCacheRootDir() . '/app';
    }

    #[Override()]
    public function getLogDir(): string
    {
        return GLPI_LOG_DIR;
    }

    #[Override()]
    public function registerBundles(): iterable
    {
        $bundles = [];

        $bundles[] = new FrameworkBundle();

        if (Environment::get()->shouldEnableExtraDevAndDebugTools()) {
            $dev_bundles_classes = [
                WebProfilerBundle::class,
                DebugBundle::class,
                TwigBundle::class,
            ];
            foreach ($dev_bundles_classes as $bundle_class) {
                if (\class_exists($bundle_class)) {
                    $bundles[] = new $bundle_class();
                }
            }
        }

        return $bundles;
    }

    #[Override()]
    public function boot(): void
    {
        $already_booted = $this->booted;

        parent::boot();

        // Define synthetic logger service
        $this->container->set('logger', $this->logger);

        if (!$already_booted && !$this->in_reboot) {
            $this->container->get('event_dispatcher')->dispatch(new PostBootEvent());
        }
    }

    #[Override()]
    public function reboot(?string $warmupDir)
    {
        $this->in_reboot = true;

        parent::reboot($warmupDir);

        $this->in_reboot = false;
    }

    #[Override()]
    protected function buildContainer(): ContainerBuilder
    {
        // Exit with a clear message if there is a missing write access that would prevent the Symfony container
        // to be built. This prevent to have a useless generic messages and no available logs when both the cache
        // and the log dirs are not writable.
        foreach ([$this->getCacheDir(), $this->getBuildDir(), $this->getLogDir()] as $dir) {
            if (
                (is_dir($dir) === false && @mkdir($dir, recursive: true) === false) // @phpstan-ignore theCodingMachineSafe.function
                || is_writable($dir) === false
            ) {
                $filesystem = new Filesystem();
                $relative_path = $filesystem->makePathRelative($dir, $this->getProjectDir());

                echo sprintf('Unable to write in the `%s` directory.', $relative_path) . PHP_EOL;
                echo 'Files ACL must be fixed.' . PHP_EOL;
                exit(1); // @phpstan-ignore glpi.forbidExit (Script execution should be stopped to prevent further errors)
            }
        }

        return parent::buildContainer();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $projectDir = $this->getProjectDir();

        $container->import($projectDir . '/dependency_injection/services.php', 'php');
        $container->import($projectDir . '/dependency_injection/framework.php', 'php');
        $container->import($projectDir . '/dependency_injection/web_profiler.php', 'php');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // Global core controllers
        $routes->import($this->getProjectDir() . '/src/Glpi/Controller', 'attribute');

        // Env-specific route files.
        if (\is_file($path = $this->getProjectDir() . '/routes/' . $this->environment . '.php')) {
            (require $path)($routes->withPath($path), $this);
        }
    }

    private function triggerGlobalsDeprecation(): void
    {
        if (in_array($this->getProjectDir() . '/inc/includes.php', get_included_files(), true)) {
            // The following deprecations/warnings are already triggered in `inc/includes.php`.
            return;
        }

        /**
         * @var mixed|null $AJAX_INCLUDE
         */
        global $AJAX_INCLUDE;
        if (isset($AJAX_INCLUDE)) {
            trigger_error(
                'The global `$AJAX_INCLUDE` variable has no effect anymore.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $SECURITY_STRATEGY
         */
        global $SECURITY_STRATEGY;
        if (isset($SECURITY_STRATEGY)) {
            trigger_error(
                'The global `$SECURITY_STRATEGY` variable has no effect anymore.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $USEDBREPLICATE
         * @var mixed|null $DBCONNECTION_REQUIRED
         */
        global $USEDBREPLICATE, $DBCONNECTION_REQUIRED;
        if (isset($USEDBREPLICATE) || isset($DBCONNECTION_REQUIRED)) {
            trigger_error(
                'The global `$USEDBREPLICATE` and `$DBCONNECTION_REQUIRED` variables has no effect anymore. Use "DBConnection::getReadConnection()" to get the most apporpriate connection for read only operations.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $PLUGINS_EXCLUDED
         * @var mixed|null $PLUGINS_INCLUDED
         */
        global $PLUGINS_EXCLUDED, $PLUGINS_INCLUDED;
        if (isset($PLUGINS_EXCLUDED) || isset($PLUGINS_INCLUDED)) {
            trigger_error(
                'The global `$PLUGINS_EXCLUDED` and `$PLUGINS_INCLUDED` variables has no effect anymore.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $skip_db_check
         */
        global $skip_db_check;
        if (isset($skip_db_check)) {
            trigger_error(
                'The global `$skip_db_check` variable has no effect anymore.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $dont_check_maintenance_mode
         */
        global $dont_check_maintenance_mode;
        if (isset($dont_check_maintenance_mode)) {
            trigger_error(
                'The global `$dont_check_maintenance_mode` variable has no effect anymore.',
                E_USER_WARNING
            );
        }
    }

    /**
     * Send the response and catch any exception that may occurs to forward it to the request error handling.
     *
     * It permits to correctly handle errors that may be thrown during the response sending. This will mainly
     * occurs when handling the GLPI legacy scripts using a streamed response, but may also rarely occurs
     * in other contexts.
     *
     * @param Request $request
     * @param Response $response
     */
    public function sendResponse(Request $request, Response $response): void
    {
        try {
            $response->send();
        } catch (Throwable $exception) {
            $event = new ExceptionEvent($this, $request, self::MAIN_REQUEST, $exception);

            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

            if ($event->hasResponse()) {
                $event->getResponse()->send();
            } else {
                throw $exception;
            }
        }
    }
}
