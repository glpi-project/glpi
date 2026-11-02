<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Error;

use Toolbox;

use function Safe\ini_get;

/**
 * Reasons for which the PHP request startup may have dropped a part of the input data.
 *
 * When one of these limits is exceeded, PHP truncates (or empties) the corresponding superglobal
 * during the request startup, i.e. before any userland code is executed. The request therefore
 * cannot be safely processed, as GLPI would operate on incomplete data.
 *
 * @see StartupErrors
 */
enum InputTruncationReason
{
    /**
     * Too many input variables.
     * PHP message: `PHP Request Startup: Input variables exceeded 1000. To increase the limit change max_input_vars in php.ini.`
     */
    case MAX_INPUT_VARS;

    /**
     * Request body larger than the allowed POST size.
     * PHP message: `PHP Request Startup: POST Content-Length of 12345 bytes exceeds the limit of 8388608 bytes`
     */
    case POST_MAX_SIZE;

    /**
     * Too many uploaded files.
     * PHP message: `PHP Request Startup: Maximum number of allowable file uploads has been exceeded`
     */
    case MAX_FILE_UPLOADS;

    /**
     * Find the truncation reason corresponding to a PHP startup error message, if any.
     */
    public static function fromPhpErrorMessage(string $message): ?self
    {
        return match (true) {
            \str_contains($message, 'Input variables exceeded')                 => self::MAX_INPUT_VARS,
            \str_contains($message, 'POST Content-Length of')                   => self::POST_MAX_SIZE,
            \str_contains($message, 'Maximum number of allowable file uploads') => self::MAX_FILE_UPLOADS,
            default                                                             => null,
        };
    }

    /**
     * Name of the PHP configuration directive that has been exceeded.
     */
    public function getIniDirective(): string
    {
        return match ($this) {
            self::MAX_INPUT_VARS   => 'max_input_vars',
            self::POST_MAX_SIZE    => 'post_max_size',
            self::MAX_FILE_UPLOADS => 'max_file_uploads',
        };
    }

    /**
     * Current value of the exceeded PHP configuration directive.
     */
    public function getIniValue(): string
    {
        return match ($this) {
            // `Toolbox::get_max_input_vars()` also handles the `suhosin.post.max_vars` fallback.
            self::MAX_INPUT_VARS => (string) Toolbox::get_max_input_vars(),
            default              => (string) ini_get($this->getIniDirective()),
        };
    }

    /**
     * HTTP status code to use when the request has to be rejected for this reason.
     */
    public function getStatusCode(): int
    {
        return match ($this) {
            self::POST_MAX_SIZE => 413, // Content Too Large
            default             => 400, // Bad Request
        };
    }

    /**
     * Message to display to the user.
     */
    public function getMessageToDisplay(): string
    {
        $message = match ($this) {
            self::MAX_INPUT_VARS => __('The submitted form contains more fields than allowed by the "%1$s" PHP configuration directive (current value: %2$s). Part of the submitted data has been dropped, therefore the request cannot be processed. Please reduce the number of submitted items, or ask your administrator to increase this limit.'),
            self::POST_MAX_SIZE => __('The submitted data exceeds the maximum size allowed by the "%1$s" PHP configuration directive (current value: %2$s). The submitted data has been dropped, therefore the request cannot be processed. Please submit less data at once, or ask your administrator to increase this limit.'),
            self::MAX_FILE_UPLOADS => __('The number of uploaded files exceeds the maximum allowed by the "%1$s" PHP configuration directive (current value: %2$s). Part of the uploaded files has been dropped, therefore the request cannot be processed. Please upload fewer files at once, or ask your administrator to increase this limit.'),
        };

        return \sprintf($message, $this->getIniDirective(), $this->getIniValue());
    }
}
