<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

class DomainRecordType extends CommonDropdown
{
    public static $rightname = 'dropdown';

    public static $knowtypes = [
        [
            'id'        => 1,
            'name'      => 'A',
            'comment'   => 'Host address',
            'fields'    => [],
        ], [
            'id'        => 2,
            'name'      => 'AAAA',
            'comment'   => 'IPv6 host address',
            'fields'    => [],
        ], [
            'id'        => 3,
            'name'      => 'ALIAS',
            'comment'   => 'Auto resolved alias',
            'fields'    => [],
        ], [
            'id'        => 4,
            'name'      => 'CNAME',
            'comment'   => 'Canonical name for an alias',
            'fields'    => [
                [
                    'key'         => 'target',
                    'label'       => 'Target',
                    'placeholder' => 'sip.example.com.',
                    'is_fqdn'     => true
                ],
            ],
        ], [
            'id'        => 5,
            'name'      => 'MX',
            'comment'   => 'Mail eXchange',
            'fields'    => [
                [
                    'key'         => 'priority',
                    'label'       => 'Priority',
                    'placeholder' => '10',
                ],
                [
                    'key'         => 'server',
                    'label'       => 'Server',
                    'placeholder' => 'mail.example.com.',
                    'is_fqdn'     => true
                ],
            ],
        ], [
            'id'        => 6,
            'name'      => 'NS',
            'comment'   => 'Name Server',
            'fields'    => [],
        ], [
            'id'        => 7,
            'name'      => 'PTR',
            'comment'   => 'Pointer',
            'fields'    => [],
        ], [
            'id'        => 8,
            'name'      => 'SOA',
            'comment'   => 'Start Of Authority',
            'fields'    => [
                [
                    'key'         => 'primary_name_server',
                    'label'       => 'Primary name server',
                    'placeholder' => 'ns1.example.com.',
                    'is_fqdn'     => true
                ],
                [
                    'key'         => 'primary_contact',
                    'label'       => 'Primary contact',
                    'placeholder' => 'admin.example.com.',
                    'is_fqdn'     => true
                ],
                [
                    'key'         => 'serial',
                    'label'       => 'Serial',
                    'placeholder' => '2020010101',
                ],
                [
                    'key'         => 'zone_refresh_timer',
                    'label'       => 'Zone refresh timer',
                    'placeholder' => '86400',
                ],
                [
                    'key'         => 'failed_refresh_retry_timer',
                    'label'       => 'Failed refresh retry timer',
                    'placeholder' => '7200',
                ],
                [
                    'key'         => 'zone_expiry_timer',
                    'label'       => 'Zone expiry timer',
                    'placeholder' => '1209600',
                ],
                [
                    'key'         => 'minimum_ttl',
                    'label'       => 'Minimum TTL',
                    'placeholder' => '300',
                ],
            ],
        ], [
            'id'        => 9,
            'name'      => 'SRV',
            'comment'   => 'Location of service',
            'fields'    => [
                [
                    'key'         => 'priority',
                    'label'       => 'Priority',
                    'placeholder' => '0',
                ],
                [
                    'key'         => 'weight',
                    'label'       => 'Weight',
                    'placeholder' => '10',
                ],
                [
                    'key'         => 'port',
                    'label'       => 'Port',
                    'placeholder' => '5060',
                ],
                [
                    'key'         => 'target',
                    'label'       => 'Target',
                    'placeholder' => 'sip.example.com.',
                    'is_fqdn'     => true
                ],
            ],
        ], [
            'id'        => 10,
            'name'      => 'TXT',
            'comment'   => 'Descriptive text',
            'fields'    => [
                [
                    'key'         => 'data',
                    'label'       => 'TXT record data',
                    'placeholder' => 'Your TXT record data',
                    'quote_value' => true,
                ],
            ],
        ], [
            'id'        => 11,
            'name'      => 'CAA',
            'comment'   => 'Certification Authority Authorization',
            'fields'    => [
                [
                    'key'         => 'flag',
                    'label'       => 'Flag',
                    'placeholder' => '0',
                ],
                [
                    'key'         => 'tag',
                    'label'       => 'Tag',
                    'placeholder' => 'issue',
                ],
                [
                    'key'         => 'value',
                    'label'       => 'Value',
                    'placeholder' => 'letsencrypt.org',
                    'quote_value' => true,
                ],
            ],
        ]
    ];


    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'fields',
                'label' => __('Fields'),
                'type'  => 'fields',
            ]
        ];
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        $field_name  = $field['name'];
        $field_type  = $field['type'];
        $field_value = $this->fields[$field_name];

        switch ($field_type) {
            case 'fields':
                $printable = json_encode(json_decode($field_value), JSON_PRETTY_PRINT);
                echo '<textarea name="' . $field_name . '" cols="75" rows="25">' . $printable . '</textarea >';
                break;
        }
    }

    public function prepareInputForAdd($input)
    {
        if (!array_key_exists('fields', $input)) {
            $input['fields'] = '[]';
        } else {
            $input['fields'] = Toolbox::cleanNewLines($input['fields']);
        }

        if (!$this->validateFieldsDescriptor($input['fields'])) {
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (array_key_exists('fields', $input)) {
            $input['fields'] = Toolbox::cleanNewLines($input['fields']);
            if (!$this->validateFieldsDescriptor($input['fields'])) {
                return false;
            }
        }

        return parent::prepareInputForUpdate($input);
    }

    public function post_updateItem($history = 1)
    {
        global $DB;

        if (in_array('fields', $this->updates)) {
            $old_fields = self::decodeFields($this->oldvalues['fields']);
            $new_fields = self::decodeFields($this->fields['fields']);

           // Checks only for keys changes as fields order, label, placeholder or quote_value properties changes
           // should have no impact on object representation.
            $old_keys = array_column($old_fields, 'key');
            $new_keys = array_column($new_fields, 'key');
            sort($old_keys);
            sort($new_keys);

            if ($old_keys != $new_keys) {
                // Remove data stored as obj as properties changed.
                // Do not remove data stored as string as this representation may still be valid.
                $DB->update(
                    DomainRecord::getTable(),
                    ['data_obj' => null],
                    [self::getForeignKeyField() => $this->fields['id']]
                );
            }
        }
    }

    /**
     * Validate fields descriptor.
     *
     * @param string $fields_str  Value of "fields" field (should be a JSON encoded string).
     *
     * @return bool
     */
    private function validateFieldsDescriptor($fields_str): bool
    {
        if (!is_string($fields_str)) {
            Session::addMessageAfterRedirect(__('Invalid JSON used to define fields.'), true, ERROR);
            return false;
        }

        $fields = self::decodeFields($fields_str);
        if (!is_array($fields)) {
            Session::addMessageAfterRedirect(__('Invalid JSON used to define fields.'), true, ERROR);
            return false;
        }

        foreach ($fields as $field) {
            if (
                !is_array($field)
                || !array_key_exists('key', $field) || !is_string($field['key'])
                || !array_key_exists('label', $field) || !is_string($field['label'])
                || (array_key_exists('placeholder', $field) && !is_string($field['placeholder']))
                || (array_key_exists('quote_value', $field) && !is_bool($field['quote_value']))
                || (array_key_exists('is_fqdn', $field) && !is_bool($field['is_fqdn']))
                || count(array_diff(array_keys($field), ['key', 'label', 'placeholder', 'quote_value', 'is_fqdn'])) > 0
            ) {
                Session::addMessageAfterRedirect(
                    __('Valid field descriptor properties are: key (string, mandatory), label (string, mandatory), placeholder (string, optionnal), quote_value (boolean, optional), is_fqdn (boolean, optional).'),
                    true,
                    ERROR
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Decode JSON encoded fields.
     * Handle decoding of sanitized value.
     * Returns null if unable to decode.
     *
     * @param string $json_encoded_fields
     *
     * @return array|null
     */
    public static function decodeFields(string $json_encoded_fields): ?array
    {
        $fields = json_decode($json_encoded_fields, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $fields_str = stripslashes(preg_replace('/(\\\r|\\\n)/', '', $json_encoded_fields));
            $fields = json_decode($fields_str, true);
        }
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($fields)) {
            return null;
        }

        return $fields;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Record type', 'Records types', $nb);
    }

    public static function getDefaults()
    {
        return array_map(
            function ($e) {
                $e['is_recursive'] = 1;
                $e['fields'] = json_encode($e['fields']);
                return $e;
            },
            self::$knowtypes
        );
    }

    /**
     * Display ajax form used to fill record data.
     *
     * @param string $str_input_id    Id of input used to get/store record data as string.
     * @param string $obj_input_id    Id of input used to get/store record data as object.
     */
    public function showDataAjaxForm(string $str_input_id, string $obj_input_id)
    {
        $rand = mt_rand();

        echo '<form id="domain_record_data' . $rand . '">';
        echo '<table class="tab_cadre_fixe">';

        $fields = json_decode($this->fields['fields'] ?? '[]', true);
        if (empty($fields)) {
            $fields = [
                [
                    'key'   => 'data',
                    'label' => __('Data'),
                ],
            ];
        }

        foreach ($fields as $field) {
            $placeholder = Html::entities_deep($field['placeholder'] ?? '');
            $quote_value = $field['quote_value'] ?? false;
            $is_fqdn = $field['is_fqdn'] ?? false;

            echo '<tr class="tab_bg_1">';
            echo '<td>' . $field['label'] . '</td>';
            echo '<td>';
            echo '<input name="' . $field['key'] . '" '
            . 'placeholder="' . $placeholder . '" '
            . 'data-quote-value="' . ($quote_value ? 'true' : 'false') . '" '
            . (!$quote_value ? 'pattern="[^\s]+" ' : '') // prevent usage of spaces in unquoted values
            . 'data-is-fqdn="' . ($is_fqdn ? 'true' : 'false') . '" '
            . ' />';
            echo '</td>';
            echo '</tr>';
        }

        echo '<tr class="tab_bg_2">';
        echo '<td colspan="2" class="right">';
        echo Html::submit('<i class="fas fa-save"></i> ' . _x('button', 'Save'));
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</form>';

        $js = <<<JAVASCRIPT
         $(
            function () {
               var form = $('#domain_record_data{$rand}');

               // Put existing data into fields
               var data_to_copy = $('#{$str_input_id}').val();
               form.find('input').each(
                  function () {
                     var endoffset = 0;
                     if ($(this).data('quote-value')) {
                        // Search for closing quote (quote inside value are escaped by a \)
                        do {
                           endoffset = endoffset + 1; // move to next char (ignore opening or escaped quote)
                           endoffset = data_to_copy.indexOf('" ', endoffset);
                        } while (endoffset !== -1 && data_to_copy.charAt(endoffset - 1) == '\\\');

                        if (endoffset !== -1) {
                           endoffset += 1; // capture closing quote
                        }
                     } else {
                        endoffset = data_to_copy.indexOf(' ');
                     }

                     if (endoffset === -1) {
                        endoffset = data_to_copy.length; // get whole value if no separator found
                     }

                     var value = data_to_copy.substring(0, endoffset).trim();
                     if ($(this).data('quote-value')) {
                        value = value.replace(/^"/, '').replace(/"$/, ''); // trim surrounding quotes
                        value = value.replace('\\\"', '"'); // unescape quotes
                     }
                     $(this).val(value);

                     // "endoffset + 1" to strip also ' ' separator char
                     data_to_copy = data_to_copy.substring(endoffset + 1);
                  }
               );

               // Copy values into data input on submit
               form.on(
                  'submit',
                  function(event) {
                     event.preventDefault();

                     var data_tokens = [];
                     var data_obj = {};
                     $(this).find('input').each(
                        function () {
                           var value = $(this).val();
                           data_obj[$(this).attr('name')] = value; // keep raw value

                           if ($(this).data('is-fqdn') && !value.match('/^\.$/')) {
                              value += '.'; // add ending dot
                           }
                           if ($(this).data('quote-value') && !value.match('/^".*"$/')) {
                              value = '"' + value.replace('"', '\\\"') + '"';
                           }
                           data_tokens.push(value);
                        }
                     );

                     $('#{$str_input_id}').val(data_tokens.join(' '));
                     $('#{$obj_input_id}').val(JSON.stringify(data_obj));
                  }
               );
            }
         );
JAVASCRIPT;
        echo Html::scriptBlock($js);
    }
}
