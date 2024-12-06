<?php

namespace Glpi\Http;

use Config;
use Html;
use Session;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CheckGlpiConfigListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::LEGACY_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $skip_db_checks = false;
        $skip_maintenance_checks = false;
        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            if (preg_match('#^' . $CFG_GLPI['root_doc'] . '/front/(css|locale).php#', $_SERVER['REQUEST_URI']) === 1) {
                $skip_db_checks  = true;
                $skip_maintenance_checks = true;
            }

            $no_db_checks_scripts = [
                '#^' . $CFG_GLPI['root_doc'] . '/$#',
                '#^' . $CFG_GLPI['root_doc'] . '/index.php#',
                '#^' . $CFG_GLPI['root_doc'] . '/install/install.php#',
                '#^' . $CFG_GLPI['root_doc'] . '/install/update.php#',
            ];
            foreach ($no_db_checks_scripts as $pattern) {
                if (preg_match($pattern, $_SERVER['REQUEST_URI']) === 1) {
                    $skip_db_checks = true;
                    break;
                }
            }
        }

        // Check if the DB is configured properly
        if (!\file_exists(\GLPI_CONFIG_DIR . "/config_db.php")) {
            $missing_db_config = true;
        } else {
            include_once(\GLPI_CONFIG_DIR . "/config_db.php");
            $missing_db_config = !class_exists('DB', false);
        }
        if (!$missing_db_config) {
            //Database connection
            if (
                !DBConnection::establishDBConnection(false, false, false)
                && !$skip_db_checks
            ) {
                throw new \RuntimeException(DBConnection::getLastDatabaseError());
            }

            //Options from DB, do not touch this part.
            if (
                !Config::loadLegacyConfiguration()
                && !$skip_db_checks
            ) {
                throw new \RuntimeException('Error accessing config table');
            }
        } elseif (!$skip_db_checks) {
            Session::loadLanguage('', false);

            $event->setResponse(new StreamedResponse($this->display(...)));
        }

        if ($skip_db_checks) {
            \define('SKIP_UPDATES', true);
        }
    }

    private function display(): void
    {
        // Prevent inclusion of debug information in footer, as they are based on vars that are not initialized here.
        $debug_mode = $_SESSION['glpi_use_mode'];
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

        Html::nullHeader('Missing configuration');
        $twig_params = [
            'config_db' => GLPI_CONFIG_DIR . '/config_db.php',
            'install_exists' => file_exists(GLPI_ROOT . '/install/install.php'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="container-fluid mb-4">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-7 col-md-9 col-sm-12">
                        <h2>GLPI seems to not be configured properly.</h2>
                        <p class="mt-2 mb-n2 alert alert-warning">
                            Database configuration file "{{ config_db }}" is missing or is corrupted.
                            You have to either restart the install process, either restore this file.
                            <br />
                            <br />
                            {% if install_exists %}
                                <a class="btn btn-primary" href="{{ path('install/install.php') }}">Go to install page</a>
                            {% endif %}
                        </p>
                    </div>
                </div>
            </div>
        TWIG, $twig_params);
        Html::nullFooter();
        $_SESSION['glpi_use_mode'] = $debug_mode;
    }
}
