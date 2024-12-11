<?php

namespace Glpi\Http\Error;

use Html;
use Session;
use Glpi\Application\View\TemplateRenderer;

final readonly class DisplayGlpiMisconfiguredPage
{
    public function __construct(private ?string $root_doc = null)
    {
    }

    public function __invoke(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $root_doc = $this->root_doc;

        if (!$root_doc) {
            $root_doc = $CFG_GLPI['root_doc'];
        }

        // Prevent inclusion of debug information in footer, as they are based on vars that are not initialized here.
        $debug_mode = $_SESSION['glpi_use_mode'];
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

        Html::nullHeader('Missing configuration', $CFG_GLPI["root_doc"]);
        $twig_params = [
            'config_db' => GLPI_CONFIG_DIR . '/config_db.php',
            'install_exists' => file_exists($root_doc . '/install/install.php'),
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
