<?php

namespace Glpi\Error;

class ErrorUtils
{
    /**
     * @param string $message
     *
     * Replace GLPI_ROOT path by '.' in $message
     * @return string
     */
    public static function cleanPaths(string $message): string
    {
        return str_replace(GLPI_ROOT, ".", $message);
    }
}
