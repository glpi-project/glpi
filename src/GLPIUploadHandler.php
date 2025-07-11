<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use function Safe\preg_match;
use function Safe\unlink;

/** GLPIUploadHandler class
 *
 * @since 9.2
 **/
class GLPIUploadHandler extends UploadHandler
{
    public static function uploadFiles($params = [])
    {
        $default_params = [
            'name'           => '',
            'showfilesize'   => false,
            'print_response' => true,
        ];
        $params = array_merge($default_params, $params);

        $pname = $params['name'];

        $upload_dir     = GLPI_TMP_DIR . '/';
        $upload_handler = new self(['param_name' => $pname]);
        $response       = $upload_handler->post(false);

        // clean compute display filesize
        if (isset($response[$pname]) && is_array($response[$pname])) {
            foreach ($response[$pname] as &$val) {
                if (isset($val->error) && file_exists($upload_dir . $val->name)) {
                    unlink($upload_dir . $val->name);
                } else {
                    if (isset($val->name)) {
                        $val->prefix = substr($val->name, 0, 23);
                        $val->display = str_replace($val->prefix, '', $val->name);
                    }
                    if (isset($val->size)) {
                        $val->filesize = Toolbox::getSize($val->size);
                        if (isset($params['showfilesize']) && $params['showfilesize']) {
                            $val->display = sprintf('%1$s %2$s', $val->display, $val->filesize);
                        }
                    }
                }
                $val->id = 'doc' . $params['name'] . mt_rand();
            }
        }

        // send answer
        return $upload_handler->generate_response($response, $params['print_response']);
    }

    protected function validate($uploaded_file, $file, $error, $index, $content_range)
    {
        if (
            !empty(GLPI_DISALLOWED_UPLOADS_PATTERN)
            && preg_match(GLPI_DISALLOWED_UPLOADS_PATTERN, $file->name) === 1
        ) {
            $file->error = __('The file upload has been refused for security reasons.');
            return false;
        }

        return parent::validate($uploaded_file, $file, $error, $index, $content_range);
    }
}
