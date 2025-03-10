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

use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;

/**
 * TicketValidation class
 */
class TicketValidation extends CommonITILValidation
{
   // From CommonDBChild
    public static $itemtype           = 'Ticket';
    public static $items_id           = 'tickets_id';

    public static $rightname                 = 'ticketvalidation';

    const CREATEREQUEST               = 1024;
    const CREATEINCIDENT              = 2048;
    const VALIDATEREQUEST             = 4096;
    const VALIDATEINCIDENT            = 8192;



    public static function getCreateRights()
    {
        return [static::CREATEREQUEST, static::CREATEINCIDENT];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Ticket approval', 'Ticket approvals', $nb);
    }

    public static function getValidateRights()
    {
        return [static::VALIDATEREQUEST, static::VALIDATEINCIDENT];
    }


    /**
     * @since 0.85
     **/
    public function canCreateItem(): bool
    {

        if ($this->canChildItem('canViewItem', 'canView')) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                // No validation for closed tickets
                if (in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {
                    return false;
                }

                if ($ticket->fields['type'] == Ticket::INCIDENT_TYPE) {
                    return Session::haveRight(self::$rightname, self::CREATEINCIDENT);
                }
                if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
                    return Session::haveRight(self::$rightname, self::CREATEREQUEST);
                }
            }
        }

        return parent::canCreateItem();
    }

    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[UPDATE], $values[CREATE], $values[READ]);

        $values[self::CREATEREQUEST]
                              = ['short' => __('Create for request'),
                                  'long'  => __('Create a validation request for a request')
                              ];
        $values[self::CREATEINCIDENT]
                              = ['short' => __('Create for incident'),
                                  'long'  => __('Create a validation request for an incident')
                              ];
        $values[self::VALIDATEREQUEST]
                              = __('Validate a request');
        $values[self::VALIDATEINCIDENT]
                              = __('Validate an incident');

        if ($interface == 'helpdesk') {
            unset($values[PURGE]);
        }

        return $values;
    }

    public function prepareInputForAdd($input)
    {
        // validation step is mandatory : add default value is not set
        if (!isset($input['validationsteps_id'])) {
            $input['validationsteps_id'] = ValidationStep::getDefault()->getID();
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        // validation step is mandatory
        if (isset($input['validationsteps_id']) && !is_numeric($input['validationsteps_id'])) { // @todo vérifier si existe en base
            Session::addMessageAfterRedirect(msg: sprintf(__s('The %s field is mandatory'), 'validationsteps_id'), message_type: ERROR);
            return false;
        }

        return parent::prepareInputForUpdate($input);
    }

    /**
     * Differences with the parent method:
     * - validations are grouped by validation step
     * - validations_step are passed to twig
     * - @todo à completer
     * @return false|void
     */
    #[\Override]
    public function showSummary(CommonITILObject $ticket)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (
            !Session::haveRightsOr(
                static::$rightname,
                array_merge(
                    static::getCreateRights(),
                    static::getValidateRights(),
                    static::getPurgeRights()
                )
            )
        ) {
            return false;
        }

        $tID    = $ticket->fields['id'];
        $tmp    = [static::$items_id => $tID];
        $rand   = mt_rand();

        $validation_sql_results = $DB->Request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [static::$items_id => $ticket->getField('id')],
            'ORDER'  => ['validationsteps_id ASC', 'submission_date DESC']
        ]);

        Session::initNavigateListItems(
            static::class,
            //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $ticket::getTypeName(1),
                $ticket->fields["name"]
            )
        );
        $validations = [];
        foreach ($validation_sql_results as $result) {
            $canedit = $this->canEdit($result["id"]);
            Session::addToNavigateListItems($this->getType(), $result["id"]); // ??
            $status  = sprintf(
                '<div class="badge fw-normal fs-4 text-wrap" style="border-color: %s;border-width: 2px;">%s</div>',
                htmlescape(self::getStatusColor($result['status'])),
                htmlescape(self::getStatus($result['status']))
            );

            $comment_submission = RichText::getEnhancedHtml($this->fields['comment_submission'], ['images_gallery' => true]);
            $type_name   = null;
            $target_name = null;
            if ($result["itemtype_target"] === User::class) {
                $type_name   = User::getTypeName();
                $target_name = getUserName($result["items_id_target"]);
            } elseif (is_a($result["itemtype_target"], CommonDBTM::class, true)) {
                $target = new $result["itemtype_target"]();
                $type_name = $target::getTypeName();
                if ($target->getFromDB($result["items_id_target"])) {
                    $target_name = $target->getName();
                }
            }
            $is_answered = $result['status'] !== self::WAITING && $result['users_id_validate'] > 0;
            $comment_validation = RichText::getEnhancedHtml($this->fields['comment_validation'] ?? '', ['images_gallery' => true]);

            $doc_item = new Document_Item();
            $docs = $doc_item->find([
                "itemtype"          => static::class,
                "items_id"           => $this->getID(),
                "timeline_position"  => ['>', CommonITILObject::NO_TIMELINE]
            ]);

            $document = "";
            foreach ($docs as $docs_values) {
                $doc = new Document();
                if ($doc->getFromDB($docs_values['documents_id'])) {
                    $document .= sprintf(
                        '<a href="%s">%s</a><br />',
                        htmlescape($doc->getLinkURL()),
                        htmlescape($doc->getName())
                    );
                }
            }

            $script = "";
            if ($canedit) {
                $edit_title = __s('Edit');
                $item_id = (int)$ticket->fields['id'];
                $row_id = (int)$result["id"];
                $rand = htmlescape($rand);
                $view_validation_id = htmlescape($this->fields[static::$items_id]);
                $root_doc = htmlescape($CFG_GLPI["root_doc"]);
                $params_json = json_encode([
                    'type'             => static::class,
                    'parenttype'       => static::$itemtype,
                    static::$items_id  => $this->fields[static::$items_id],
                    'id'               => $result["id"]
                ]);

                $script = <<<HTML
                    <span class="ti ti-edit" style="cursor:pointer" title="{$edit_title}" 
                          onclick="viewEditValidation{$item_id}{$row_id}{$rand}();" 
                          id="viewvalidation{$view_validation_id}{$row_id}{$rand}">
                    </span>
                    <script>
                        function viewEditValidation{$item_id}{$row_id}{$rand}() {
                            $('#viewvalidation{$item_id}{$rand}').load('$root_doc/ajax/viewsubitem.php', $params_json);
                        };
                    </script>
HTML;
            }

            $validationstep_id = $result['validationsteps_id'];
            $validations[$validationstep_id]['entries'][] = [
                'edit'                  => $script,
                'status'                => $status,
                'type_name'             => $type_name,
                'target_name'           => $target_name,
                'is_answered'           => $is_answered,
                'comment_submission'    => $comment_submission,
                'comment_validation'    => $comment_validation,
                'document'              => $document,
                'submission_date'       => $result["submission_date"],
                'validation_date'       => $result["validation_date"],
                'user'                  => getUserName($result["users_id"]),
            ];

            // @todo écraser à chaque itération de la boucle, a optimiser
            $validations[$validationstep_id]['validationstep'] = [
                'id' => $result['validationsteps_id'],
                'name' => 'Validation step name',
                // @todo fake data
                // structured to be later replaced by a DTO with getStatus(), Status::isAccepted()|isRefused()|isWaiting()
                'status' => [
                    'waiting' => false,
                    'refused' => false,
                    'accepted' => true,
                ],
                // structured to be later replaced by a DTO with getAchievement()
                'achievement' => [
                    'waiting' => 60,
                    'refused' => 10,
                    'accepted' => 30,
                ],
            ];
        }

        TemplateRenderer::getInstance()->display('components/itilobject/validation.html.twig', [
            'canadd' => $this->can(-1, CREATE, $tmp),
            'item' => $ticket,
            'itemtype' => static::$itemtype,
            'tID' => $tID,
            'donestatus' => array_merge($ticket->getSolvedStatusArray(), $ticket->getClosedStatusArray()),
            'validation' => $this,
            'rand' => $rand,
            'items_id' => static::$items_id,
        ]);

        TemplateRenderer::getInstance()->display('components/sections_datatable.html.twig', [
            'is_tab' => true,
            'nopager' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'edit' => '',
                'status' => _x('item', 'State'),
                'submission_date' => __('Request date'),
                'user' => __('Approval requester'),
                'comment_submission' => __('Request comments'),
                'validation_date' => __('Approval date'),
                'type_name' => __('Requested approver type'),
                'target_name' => __('Requested approver'),
                'comment_validation' => __('Approval Comment'),
                'document' => __('Documents'),
            ],
            'formatters' => [
                'edit' => 'raw_html',
                'status' => 'raw_html',
                'submission_date' => 'date',
                'comment_submission' => 'raw_html',
                'validation_date' => 'date',
                'comment_validation' => 'raw_html',
                'document' => 'raw_html',
            ],
            'validationsteps' => $validations, // replace 'entries' in parent implementation
            'total_number' => count($validations),
            'showmassiveactions' => false,
        ]);
    }
}
