<?php

namespace Glpi\Http;

use Glpi\Config\LegacyConfigProviderListener;

final class ListenersPriority
{
    public const LEGACY_LISTENERS_PRIORITIES = [
        LegacyRouterListener::class => 400,
        LegacyConfigProviderListener::class => 350,
        LegacyAssetsListener::class => 300,
    ];

    private function __construct()
    {
    }
}
