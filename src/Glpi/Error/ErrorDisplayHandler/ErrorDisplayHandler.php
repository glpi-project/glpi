<?php

namespace Glpi\Error\ErrorDisplayHandler;

interface ErrorDisplayHandler
{
    public function canOutput(string $log_level, string $env): bool;

    public function displayErrorMessage(string $error_type, string $message, string $log_level, mixed $env): void;
}
