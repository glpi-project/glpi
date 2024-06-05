<?php

namespace Glpi\Config;

class InitializeAppConfig
{
    private bool $initialized = false;

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // TODO

        $this->initialized = true;
    }
}
