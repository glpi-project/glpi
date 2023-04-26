<?php

namespace Glpi\Debug;

use Glpi\Application\View\TemplateRenderer;

final class Toolbar
{
    public function show()
    {
        $info = Profile::getCurrent()->getDebugInfo();

        // Needed widgets: summary (exec time, memory, etc), SQL, super-globals
        TemplateRenderer::getInstance()->display('components/debug/debug_toolbar.html.twig', [
            'debug_info' => $info,
        ]);
    }
}
