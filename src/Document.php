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
use Glpi\DBAL\QuerySubQuery;
use Glpi\Event;
use Glpi\Features\ParentStatus;
use Glpi\Features\TreeBrowse;
use Glpi\Features\TreeBrowseInterface;
use Safe\Exceptions\FilesystemException;
use Symfony\Component\HttpFoundation\Response;

use function Safe\copy;
use function Safe\filesize;
use function Safe\finfo_open;
use function Safe\getimagesize;
use function Safe\mkdir;
use function Safe\opendir;
use function Safe\preg_match;
use function Safe\rename;
use function Safe\session_destroy;
use function Safe\session_id;
use function Safe\sha1_file;
use function Safe\unlink;

/**
 * Document class
 **/
class Document extends CommonDBTM implements TreeBrowseInterface
{
    use TreeBrowse;
    use ParentStatus;

    // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['Document_Item'];

    public static $rightname                   = 'document';
    public static $tag_prefix                  = '#';
    protected $usenotepad               = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Document', 'Documents', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', self::class];
    }

    /**
     * Check if given object can have Document
     *
     * @since 0.85
     *
     * @param string|object $item An object or a string
     *
     * @return boolean
     **/
    public static function canApplyOn($item): bool
    {
        return in_array(is_string($item) ? $item : $item::class, self::getItemtypesThatCanHave(), true);
    }

    /**
     * Get all the types that can have a document
     *
     * @since 0.85
     *
     * @return array of the itemtypes
     **/
    public static function getItemtypesThatCanHave(): array
    {
        global $CFG_GLPI;

        return array_merge(
            $CFG_GLPI['document_types'],
            CommonDevice::getDeviceTypes(),
            Item_Devices::getDeviceTypes()
        );
    }

    public static function getMenuShorcut()
    {
        return 'd';
    }

    public static function canCreate(): bool
    {
        // Have right to add document OR ticket followup
        return (Session::haveRight('document', CREATE)
              || Session::haveRight('followup', ITILFollowup::ADDMY));
    }

    public function canCreateItem(): bool
    {
        if (isset($this->input['itemtype'], $this->input['items_id'])) {
            if (
                ($item = getItemForItemtype($this->input['itemtype']))
                && $item->getFromDB($this->input['items_id'])
            ) {
                return $item->canAddItem('Document');
            } else {
                unset($this->input['itemtype'], $this->input['items_id']);
            }
        }

        // From Ticket Document Tab => check right to add followup.
        if (
            isset($this->fields['tickets_id'])
            && ($this->fields['tickets_id'] > 0)
        ) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                return $ticket->canAddFollowups();
            }
        }

        if (self::canCreate()) {
            return parent::canCreateItem();
        }
        return false;
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Document_Item::class,
            ]
        );

        // Unlink/delete the file
        if (!empty($this->fields["filepath"])) {
            if (
                is_file(GLPI_DOC_DIR . "/" . $this->fields["filepath"])
                && !is_dir(GLPI_DOC_DIR . "/" . $this->fields["filepath"])
                && (countElementsInTable(
                    static::getTable(),
                    ['sha1sum' => $this->fields["sha1sum"] ]
                ) <= 1)
            ) {
                try {
                    unlink(GLPI_DOC_DIR . "/" . $this->fields["filepath"]);
                    Session::addMessageAfterRedirect(htmlescape(sprintf(
                        __('Successful deletion of the file %s'),
                        $this->fields["filepath"]
                    )));
                } catch (FilesystemException $e) {
                    trigger_error(
                        sprintf(
                            'Failed to delete the file %s',
                            GLPI_DOC_DIR . "/" . $this->fields["filepath"]
                        ),
                        E_USER_WARNING
                    );
                    Session::addMessageAfterRedirect(
                        htmlescape(sprintf(
                            __('Failed to delete the file %s'),
                            $this->fields["filepath"]
                        )),
                        false,
                        ERROR
                    );
                }
            }
        }
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function prepareInputForAdd($input)
    {
        global $CFG_GLPI;

        // security (don't accept filename from $_REQUEST)
        if (array_key_exists('filename', $_REQUEST)) {
            unset($input['filename']);
        }

        if ($uid = Session::getLoginUserID()) {
            $input["users_id"] = Session::getLoginUserID();
        }

        // Create a doc only selecting a file from a item form
        $create_from_item = false;
        if (
            isset($input["items_id"], $input["itemtype"])
            && ($item = getItemForItemtype($input["itemtype"])) && ($input["items_id"] > 0)
        ) {
            $create_from_item = true;
        }

        $upload_ok = false;
        if (!empty($input["_filename"])) {
            $upload_ok = self::moveDocument($input, array_shift($input["_filename"]));
        } elseif (!empty($input["upload_file"])) {
            // Move doc from upload dir
            $upload_ok = $this->moveUploadedDocument($input, $input["upload_file"]);
        } elseif (isset($input['filepath']) && file_exists(GLPI_DOC_DIR . '/' . $input['filepath'])) {
            // Document is created using an existing document file
            $upload_ok = true;
        }

        // Tag
        if (!empty($input["_tag_filename"])) {
            $input['tag'] = array_shift($input["_tag_filename"]);
        }

        if (empty($input["tag"])) {
            $input['tag'] = Rule::getUuid();
        }

        // Upload failed : do not create document
        if ($create_from_item && !$upload_ok) {
            return false;
        }

        // Default document name
        if (empty($input['name']) && isset($input['filename'])) {
            $input['name'] = $input['filename'];
        }

        unset($input["upload_file"]);

        // Don't add if no file
        if (
            isset($input["_only_if_upload_succeed"])
            && $input["_only_if_upload_succeed"]
            && (empty($input['filename']))
        ) {
            return false;
        }

        // Set default category for document linked to tickets
        if (
            isset($input['itemtype']) && ($input['itemtype'] === 'Ticket')
            && (!isset($input['documentcategories_id']) || ($input['documentcategories_id'] == 0))
        ) {
            $input['documentcategories_id'] = $CFG_GLPI["documentcategories_id_forticket"];
        }

        if (!empty($input['link']) && !Toolbox::isValidWebUrl($input['link'])) {
            Session::addMessageAfterRedirect(
                __s('Invalid link'),
                false,
                ERROR
            );
            return false;
        }
        return $input;
    }

    public function post_addItem()
    {
        if (
            isset($this->input["items_id"], $this->input["itemtype"]) && (($this->input["items_id"] > 0)
                || (((int) $this->input["items_id"] === 0)
                    && ($this->input["itemtype"] === 'Entity'))) && !empty($this->input["itemtype"])
        ) {
            $docitem = new Document_Item();
            $docitem->add(['documents_id' => $this->fields['id'],
                'itemtype'     => $this->input["itemtype"],
                'items_id'     => $this->input["items_id"],
            ]);

            if (is_a($this->input["itemtype"], CommonITILObject::class, true)) {
                $main_item = new $this->input["itemtype"]();
                $main_item->getFromDB($this->input["items_id"]);
                NotificationEvent::raiseEvent('add_document', $main_item);

                $this->updateParentStatus($main_item, $this->input);
            }

            Event::log(
                $this->fields['id'],
                "documents",
                4,
                "document",
                //TRANS: %s is the user login
                sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"])
            );
        }
    }

    public function prepareInputForUpdate($input)
    {
        // security (don't accept filename from $_REQUEST)
        if (array_key_exists('filename', $_REQUEST)) {
            unset($input['filename']);
        }

        if (isset($input['current_filepath'])) {
            if (!empty($input["_filename"])) {
                self::moveDocument($input, array_shift($input["_filename"]));
            } elseif (!empty($input["upload_file"])) {
                // Move doc from upload dir
                $this->moveUploadedDocument($input, $input["upload_file"]);
            }
        }

        unset($input['current_filepath'], $input['current_filename']);

        if (!empty($input['link']) && !Toolbox::isValidWebUrl($input['link'])) {
            Session::addMessageAfterRedirect(
                __s('Invalid link'),
                false,
                ERROR
            );
            return false;
        }

        return $input;
    }

    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
        }

        TemplateRenderer::getInstance()->display('pages/management/document.html.twig', [
            'item'  => $this,
            'uploader' => $this->fields['users_id'] > 0 ? getUserLink($this->fields["users_id"]) : '',
            'uploaded_files' => self::getUploadedFiles(),
            'params' => [
                'canedit' => $this->canUpdateItem(),
            ],
        ]);

        return true;
    }

    /**
     * Get max upload size from php config
     **/
    public static function getMaxUploadSize()
    {
        global $CFG_GLPI;

        //TRANS: %s is a size
        return sprintf(__('%s Mio max'), $CFG_GLPI['document_max_size']);
    }

    /**
     * Get a Symfony response for the given document.
     */
    public function getAsResponse(): Response
    {
        $file = GLPI_DOC_DIR . "/" . $this->fields['filepath'];
        return Toolbox::getFileAsResponse($file, $this->fields['filename'], $this->fields['mime']);
    }

    /**
     * Send a document to navigator
     *
     * @return void
     *
     * @deprecated 11.0.0
     */
    public function send()
    {
        Toolbox::deprecated();

        $this->getAsResponse()->send();
    }

    /**
     * Get download link for a document
     *
     * @param CommonDBTM|null   $linked_item    Item linked to the document, to check access right
     * @param integer           $len            maximum length of displayed string (default 20)
     * @return string HTML link
     **/
    public function getDownloadLink($linked_item = null, $len = 20): string
    {
        global $CFG_GLPI, $DB;

        $link_params = '';
        if (is_string($linked_item)) {
            // Old behaviour.
            Toolbox::deprecated('Passing additionnal URL parameters in Document::getDownloadLink() is deprecated.', true, '11.0');
            $linked_item = null;
            $link_params = $linked_item;
        } elseif ($linked_item !== null && !($linked_item instanceof CommonDBTM)) {
            throw new InvalidArgumentException();
        } elseif ($linked_item !== null) {
            $link_params = sprintf('&itemtype=%s&items_id=%s', $linked_item::class, $linked_item->getID());
        }

        $splitter = $this->fields['filename'] !== null ? explode("/", $this->fields['filename']) : [];

        if (count($splitter) === 2) {
            // Old documents in EXT/filename
            $fileout = $splitter[1];
        } else {
            // New document
            $fileout = $this->fields['filename'];
        }

        $initfileout = null;
        if ($fileout !== null) {
            $initfileout = htmlescape($fileout);
            $fileout     = Toolbox::strlen($fileout) > $len
                ? htmlescape(Toolbox::substr($fileout, 0, $len)) . "&hellip;"
                : htmlescape($fileout);
        }

        $out   = '';
        $open  = '';
        $close = '';

        $can_view_options = $linked_item !== null
            ? ['itemtype' => $linked_item::class, 'items_id' => $linked_item->getID()]
            : ['itemtype' => Ticket::class, 'items_id' => $this->fields['tickets_id']];

        if (self::canView() || $this->canViewFile($can_view_options)) {
            $open  = "<a href='" . htmlescape($CFG_GLPI["root_doc"] . "/front/document.send.php?docid="
                    . $this->fields['id'] . $link_params) . "' alt=\"" . $initfileout . "\"
                    title=\"" . $initfileout . "\"target='_blank'>";
            $close = "</a>";
        }
        $splitter = $this->fields['filename'] !== null ? explode("/", $this->fields['filepath']) : [];

        if (count($splitter)) {
            $iterator = $DB->request([
                'SELECT' => 'icon',
                'FROM'   => 'glpi_documenttypes',
                'WHERE'  => [
                    'ext'    => ['LIKE', $splitter[0]],
                    'icon'   => ['<>', ''],
                ],
            ]);

            if (count($iterator) > 0) {
                $result = $iterator->current();
                $icon = $result['icon'];
                if (!file_exists(GLPI_ROOT . "/pics/icones/$icon")) {
                    $icon = "defaut-dist.png";
                }
                $out .= "<img class='middle' style='margin-left:3px; margin-right:6px;' alt=\""
                              . $initfileout . "\" title=\"" . $initfileout . "\" src='"
                              . htmlescape($CFG_GLPI["typedoc_icon_dir"] . "/$icon") . "'>";
            }
        }
        $out .= "$open<span class='fw-bold'>" . $fileout . "</span>$close";

        return $out;
    }

    /**
     * find a document with a file attached
     *
     * @param integer $entity    entity of the document
     * @param string  $path      path of the searched file
     *
     * @return boolean
     **/
    public function getFromDBbyContent($entity, $path)
    {

        global $DB;

        if (empty($path)) {
            return false;
        }

        $sum = sha1_file($path);
        if (!$sum) {
            return false;
        }

        $table = static::getTable();
        $doc_iterator = $DB->request(
            [
                'SELECT' => 'id',
                'FROM'   => $table,
                'WHERE'  => [
                    $table . '.sha1sum'      => $sum,
                    $table . '.entities_id'  => $entity,
                ],
                'LIMIT'  => 1,
            ]
        );

        if ($doc_iterator->count() === 0) {
            return false;
        }

        $doc_data = $doc_iterator->current();
        return $this->getFromDB($doc_data['id']);
    }

    /**
     * Check is the curent user is allowed to see the file.
     *
     * @param array $options array of possible options used to check rights:
     *     - itemtype/items_id:     itemtype and ID of item linked to document
     *     - changes_id (legacy):   ID of Change linked to document. Ignored if itemtype/items_id options are set.
     *     - problems_id (legacy):  ID of Problem linked to document. Ignored if itemtype/items_id options are set.
     *     - tickets_id (legacy):   ID of Ticket linked to document. Ignored if itemtype/items_id options are set.
     *
     * @return boolean
     **/
    public function canViewFile(array $options = [])
    {

        // Check if it is my doc
        if (
            Session::getLoginUserID()
            && ($this->can($this->fields["id"], READ)
              || ($this->fields["users_id"] === Session::getLoginUserID()))
        ) {
            return true;
        }

        if ($this->canViewFileFromReminder()) {
            return true;
        }

        if ($this->canViewFileFromKnowbaseItem()) {
            return true;
        }

        // new options
        $itemtype = $options['itemtype'] ?? null;
        $items_id = $options['items_id'] ?? null;

        // legacy options
        $changes_id  = $itemtype === null ? ($options['changes_id'] ?? null) : ($itemtype === 'Change' ? $items_id : null);
        $problems_id = $itemtype === null ? ($options['problems_id'] ?? null) : ($itemtype === 'Problem' ? $items_id : null);
        $tickets_id  = $itemtype === null ? ($options['tickets_id'] ?? null) : ($itemtype === 'Ticket' ? $items_id : null);

        if ($changes_id !== null && $this->canViewFileFromItilObject('Change', $changes_id)) {
            return true;
        }

        if ($problems_id !== null && $this->canViewFileFromItilObject('Problem', $problems_id)) {
            return true;
        }

        if (
            $itemtype !== null
            && is_a($itemtype, CommonDBTM::class, true)
            && $items_id !== null
            && $this->canViewFileFromItem($itemtype, $items_id)
        ) {
            return true;
        }

        // The following case should be reachable from the API
        self::loadAPISessionIfExist();

        if ($tickets_id !== null && $this->canViewFileFromItilObject('Ticket', $tickets_id)) {
            return true;
        }

        return false;
    }

    /**
     * Try to load the session from the API Tolen
     *
     * @return void
     * @since 9.5
     */
    private static function loadAPISessionIfExist(): void
    {
        $session_token = Toolbox::getHeader('Session-Token');

        // No api token found
        if ($session_token === null) {
            return;
        }

        $current_session = session_id();

        // Clean current session
        if (!empty($current_session) && $current_session !== $session_token) {
            session_destroy();
        }

        // Load API session
        session_id($session_token);
        Session::start();
    }

    /**
     * Check if file of current instance can be viewed from a Reminder.
     *
     * @return boolean
     */
    private function canViewFileFromReminder()
    {
        global $DB;

        if (!Session::getLoginUserID()) {
            return false;
        }

        $criteria = array_merge_recursive(
            [
                'COUNT'     => 'cpt',
                'FROM'      => 'glpi_documents_items',
                'LEFT JOIN' => [
                    'glpi_reminders'  => [
                        'ON' => [
                            'glpi_documents_items'  => 'items_id',
                            'glpi_reminders'        => 'id', [
                                'AND' => [
                                    'glpi_documents_items.itemtype'  => 'Reminder',
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'     => [
                    'glpi_documents_items.documents_id' => $this->fields['id'],
                ],
            ],
            Reminder::getVisibilityCriteria()
        );

        $result = $DB->request($criteria)->current();
        return $result['cpt'] > 0;
    }

    /**
     * Check if file of current instance can be viewed from a KnowbaseItem.
     *
     * @global array $CFG_GLPI
     * @global DBmysql $DB
     * @return boolean
     */
    private function canViewFileFromKnowbaseItem()
    {

        global $CFG_GLPI, $DB;

        // Knowbase items can be viewed by non connected user in case of public FAQ
        if (!Session::getLoginUserID() && !$CFG_GLPI['use_public_faq']) {
            return false;
        }

        if (
            !Session::haveRight(KnowbaseItem::$rightname, READ)
            && !Session::haveRight(KnowbaseItem::$rightname, KnowbaseItem::READFAQ)
            && !$CFG_GLPI['use_public_faq']
        ) {
            return false;
        }

        $visibilityCriteria = KnowbaseItem::getVisibilityCriteria();

        $request = [
            'FROM'      => 'glpi_documents_items',
            'COUNT'     => 'cpt',
            'LEFT JOIN' => [
                'glpi_knowbaseitems' => [
                    'FKEY' => [
                        'glpi_knowbaseitems'   => 'id',
                        'glpi_documents_items' => 'items_id',
                        ['AND' => ['glpi_documents_items.itemtype' => 'KnowbaseItem']],
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_documents_items.documents_id' => $this->fields['id'],
            ],
        ];

        if (array_key_exists('LEFT JOIN', $visibilityCriteria) && count($visibilityCriteria['LEFT JOIN']) > 0) {
            $request['LEFT JOIN'] += $visibilityCriteria['LEFT JOIN'];
        }
        if (array_key_exists('WHERE', $visibilityCriteria) && count($visibilityCriteria['WHERE']) > 0) {
            $request['WHERE'] += $visibilityCriteria['WHERE'];
        }

        $result = $DB->request($request)->current();

        return $result['cpt'] > 0;
    }

    /**
     * Check if file of current instance can be viewed from a CommonITILObject.
     *
     * @global DBmysql $DB
     * @param string  $itemtype
     * @param integer $items_id
     * @return boolean
     */
    private function canViewFileFromItilObject($itemtype, $items_id)
    {

        global $DB;

        if (!Session::getLoginUserID()) {
            return false;
        }

        if (!is_a($itemtype, CommonITILObject::class, true)) {
            return false;
        }

        $itil = new $itemtype();
        if (!$itil->can($items_id, READ)) {
            return false;
        }

        $itil->getFromDB($items_id);

        $result = $DB->request([
            'FROM'  => Document_Item::getTable(),
            'COUNT' => 'cpt',
            'WHERE' => [
                'documents_id' => $this->fields['id'],
                $itil->getAssociatedDocumentsCriteria(),
            ],
            'LIMIT' => 1, // Only need to see one result
        ])->current();

        return $result['cpt'] > 0;
    }

    /**
     * Check if file of current instance can be viewed from item having given itemtype/items_id.
     *
     * @global DBmysql $DB
     *
     * @param string  $itemtype
     * @param integer $items_id
     *
     * @return boolean
     */
    private function canViewFileFromItem($itemtype, $items_id): bool
    {
        global $DB;

        if (!is_a($itemtype, CommonDBTM::class, true)) {
            return false;
        }

        $item = new $itemtype();

        if (!$item->can($items_id, READ)) {
            return false;
        }

        /** @var CommonDBTM $item */
        $item->getFromDB($items_id);
        if (!$item->canViewItem()) {
            return false;
        }

        $result = $DB->request([
            'FROM'  => Document_Item::getTable(),
            'COUNT' => 'cpt',
            'WHERE' => [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ],
            'LIMIT' => 1, // Only need to see one result
        ])->current();

        if ($result['cpt'] === 0) {
            return false;
        }

        return true;
    }

    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'document',
            'name'               => self::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '119',
            'table'              => 'glpi_documents_items',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of documents'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
            ],
        ];

        return $tab;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'filename',
            'name'               => __('File'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'link',
            'name'               => __('Web link'),
            'datatype'           => 'weblink',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'mime',
            'name'               => __('MIME type'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'tag',
            'name'               => __('Tag'),
            'datatype'           => 'text',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => 'glpi_documentcategories',
            'field'              => 'completename',
            'name'               => __('Heading'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => static::getTable(),
            'field'              => 'sha1sum',
            'name'               => sprintf(__('%1$s (%2$s)'), __('Checksum'), __('SHA1')),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '72',
            'table'              => 'glpi_documents_items',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of associated items'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    /**
     * Move a file to a new location
     * Work even if dest file already exists
     *
     * @param string $srce   source file path
     * @param string $dest   destination file path
     *
     * @return boolean : success
     **/
    public static function renameForce($srce, $dest)
    {
        // File already present
        if (is_file($dest)) {
            // As content is the same (sha1sum), no need to copy
            @unlink($srce);
            return true;
        }
        // Move
        try {
            rename($srce, $dest);
            return true;
        } catch (FilesystemException $e) {
            return false;
        }
    }

    /**
     * Move an uploaded document (files in GLPI_DOC_DIR."/_uploads" dir)
     *
     * @param array  $input     array of datas used in adding process (need current_filepath)
     * @param string $filename  filename to move
     *
     * @return boolean for success / $input array is updated
     **/
    public function moveUploadedDocument(array &$input, $filename)
    {
        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            // Filename is not supposed to contains directory separators.
            trigger_error(sprintf('Moving file `%s` is forbidden for security reasons.', $filename), E_USER_WARNING);
            return false;
        }

        $prefix = '';
        if (isset($input['_prefix_filename'])) {
            $prefix = array_shift($input['_prefix_filename']);
        }

        $fullpath = GLPI_UPLOAD_DIR . "/" . $filename;
        $filename = str_replace($prefix, '', $filename);

        if (!is_dir(GLPI_UPLOAD_DIR)) {
            Session::addMessageAfterRedirect(__s("Upload directory doesn't exist"), false, ERROR);
            return false;
        }

        if (!is_file($fullpath)) {
            trigger_error(
                sprintf('File %s not found.', $fullpath),
                E_USER_WARNING
            );
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(__('File %s not found.'), $filename)),
                false,
                ERROR
            );
            return false;
        }
        $sha1sum  = sha1_file($fullpath);
        $dir      = self::isValidDoc($filename);
        $new_path = self::getUploadFileValidLocationName($dir, $sha1sum);

        if (!$sha1sum || !$dir || !$new_path) {
            return false;
        }

        // Delete old file (if not used by another doc)
        if (
            isset($input['current_filepath'])
            && !empty($input['current_filepath'])
            && is_file(GLPI_DOC_DIR . "/" . $input['current_filepath'])
            && (countElementsInTable(
                'glpi_documents',
                ['sha1sum' => sha1_file(GLPI_DOC_DIR . "/"
                . $input['current_filepath']),
                ]
            ) <= 1)
        ) {
            try {
                unlink(GLPI_DOC_DIR . "/" . $input['current_filepath']);
                Session::addMessageAfterRedirect(htmlescape(sprintf(
                    __('Successful deletion of the file %s'),
                    $input['current_filename']
                )));
            } catch (FilesystemException $e) {
                // TRANS: %1$s is the curent filename, %2$s is its directory
                trigger_error(
                    sprintf(
                        'Failed to delete the file %1$s (%2$s)',
                        $input['current_filename'],
                        GLPI_DOC_DIR . "/" . $input['current_filepath']
                    ),
                    E_USER_WARNING
                );
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('Failed to delete the file %1$s'),
                        $input['current_filename']
                    )),
                    false,
                    ERROR
                );
            }
        }

        // Local file: try to detect mime type
        $input['mime'] = Toolbox::getMime($fullpath);

        if (
            is_writable(GLPI_UPLOAD_DIR)
            && is_writable($fullpath)
        ) { // Move if allowed
            if (self::renameForce($fullpath, GLPI_DOC_DIR . "/" . $new_path)) {
                Session::addMessageAfterRedirect(__s('Document move succeeded.'));
            } else {
                Session::addMessageAfterRedirect(__s('File move failed.'), false, ERROR);
                return false;
            }
        } else { // Copy (will overwrite dest file is present)
            try {
                copy($fullpath, GLPI_DOC_DIR . "/" . $new_path);
                Session::addMessageAfterRedirect(__s('Document copy succeeded.'));
            } catch (FilesystemException $e) {
                Session::addMessageAfterRedirect(__s('File move failed'), false, ERROR);
                return false;
            }
        }

        // For display
        $input['filename'] = $filename;
        // Storage path
        $input['filepath'] = $new_path;
        // Checksum
        $input['sha1sum']  = $sha1sum;
        return true;
    }

    /**
     * Move a document (files in GLPI_DOC_DIR."/_tmp" dir)
     *
     * @param array  $input     array of datas used in adding process (need current_filepath)
     * @param string $filename  filename to move
     *
     * @return boolean for success / $input array is updated
     **/
    public static function moveDocument(array &$input, $filename)
    {
        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            // Filename is not supposed to contains directory separators.
            trigger_error(sprintf('Moving file `%s` is forbidden for security reasons.', $filename), E_USER_WARNING);
            return false;
        }

        $prefix = '';
        if (isset($input['_prefix_filename'])) {
            $prefix = array_shift($input['_prefix_filename']);
        }

        $fullpath = GLPI_TMP_DIR . "/" . $filename;
        $filename = str_replace($prefix, '', $filename);
        if (!is_dir(GLPI_TMP_DIR)) {
            Session::addMessageAfterRedirect(__s("Temporary directory doesn't exist"), false, ERROR);
            return false;
        }

        if (!is_file($fullpath)) {
            trigger_error(
                sprintf('File %s not found.', $fullpath),
                E_USER_WARNING
            );
            Session::addMessageAfterRedirect(
                sprintf(__s('File %s not found.'), $filename),
                false,
                ERROR
            );
            return false;
        }
        $sha1sum  = sha1_file($fullpath);
        $dir      = self::isValidDoc($filename);
        $new_path = self::getUploadFileValidLocationName($dir, $sha1sum);

        if (!$sha1sum || !$dir || !$new_path) {
            @unlink($fullpath);
            return false;
        }

        // Delete old file (if not used by another doc)
        if (
            !empty($input['current_filepath'])
            && is_file(GLPI_DOC_DIR . "/" . $input['current_filepath'])
            && (countElementsInTable(
                'glpi_documents',
                ['sha1sum' => sha1_file(GLPI_DOC_DIR . "/"
                . $input['current_filepath']),
                ]
            ) <= 1)
        ) {
            try {
                unlink(GLPI_DOC_DIR . "/" . $input['current_filepath']);
                Session::addMessageAfterRedirect(sprintf(
                    __s('Successful deletion of the file %s'),
                    htmlescape($input['current_filename'])
                ));
            } catch (FilesystemException $e) {
                // TRANS: %1$s is the curent filename, %2$s is its directory
                trigger_error(
                    sprintf(
                        'Failed to delete the file %1$s (%2$s)',
                        $input['current_filename'],
                        GLPI_DOC_DIR . "/" . $input['current_filepath']
                    ),
                    E_USER_WARNING
                );
                Session::addMessageAfterRedirect(
                    sprintf(
                        __s('Failed to delete the file %1$s'),
                        htmlescape($input['current_filename'])
                    ),
                    false,
                    ERROR
                );
            }
        }

        // Local file: try to detect mime type
        $input['mime'] = Toolbox::getMime($fullpath);

        // Copy (will overwrite dest file if present)
        try {
            copy($fullpath, GLPI_DOC_DIR . "/" . $new_path);
            Session::addMessageAfterRedirect(__s('Document copy succeeded.'));
        } catch (FilesystemException $e) {
            Session::addMessageAfterRedirect(__s('File move failed'), false, ERROR);
            @unlink($fullpath);
            return false;
        }

        // For display
        $input['filename'] = $filename;
        // Storage path
        $input['filepath'] = $new_path;
        // Checksum
        $input['sha1sum']  = $sha1sum;
        return true;
    }

    /**
     * Find a valid path for the new file
     *
     * @param string $dir      dir to search a free path for the file
     * @param string $sha1sum  SHA1 of the file
     *
     * @return string
     **/
    public static function getUploadFileValidLocationName($dir, $sha1sum)
    {
        if (empty($dir)) {
            $message = __s('Unauthorized file type');

            if (Session::haveRight('dropdown', READ)) {
                $message .= " <a target='_blank' href='" . htmlescape(DocumentType::getSearchURL()) . "' class='pointer'>
                         <i class='fa fa-info'</i><span class='sr-only'>" . __s('Manage document types') . "</span></a>";
            }
            Session::addMessageAfterRedirect($message, false, ERROR);
            return '';
        }

        if (!is_dir(GLPI_DOC_DIR)) {
            trigger_error(
                sprintf(
                    "The directory %s doesn't exist.",
                    GLPI_DOC_DIR
                ),
                E_USER_WARNING
            );
            Session::addMessageAfterRedirect(
                __s("Documents directory doesn't exist."),
                false,
                ERROR
            );
            return '';
        }
        $subdir = $dir . '/' . substr($sha1sum, 0, 2);

        if (!is_dir(GLPI_DOC_DIR . "/" . $subdir)) {
            try {
                mkdir(GLPI_DOC_DIR . "/" . $subdir, 0o777, true);
                Session::addMessageAfterRedirect(sprintf(
                    __s('Create the directory %s'),
                    $subdir
                ));
            } catch (FilesystemException $e) {
                //emtpy catch
            }
        }

        if (!is_dir(GLPI_DOC_DIR . "/" . $subdir)) {
            trigger_error(
                sprintf(
                    'Failed to create the directory %s.',
                    GLPI_DOC_DIR . "/" . $subdir
                ),
                E_USER_WARNING
            );
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('Failed to create the directory %s. Verify that you have the correct permission'),
                    htmlescape($subdir)
                ),
                false,
                ERROR
            );
            return '';
        }
        return $subdir . '/' . substr($sha1sum, 2) . '.' . $dir;
    }

    /**
     * @return array Array of uploaded files to be used in a dropdown
     */
    private static function getUploadedFiles()
    {
        $uploaded_files = [];
        if ($handle = opendir(GLPI_UPLOAD_DIR)) {
            while (false !== ($file = readdir($handle))) {
                if (!in_array($file, ['.', '..', '.gitkeep', 'remove.txt'])) {
                    $dir = self::isValidDoc($file);
                    if (!empty($dir)) {
                        $uploaded_files[$file] = $file;
                    }
                }
            }
            closedir($handle);
        }
        return $uploaded_files;
    }

    /**
     * Is this file a valid file ? check based on file extension
     *
     * @param string $filename filename to clean
     **/
    public static function isValidDoc($filename)
    {
        global $DB;

        $splitter = explode(".", $filename);
        $ext      = end($splitter);

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_documenttypes',
            'WHERE'  => [
                'is_uploadable'   => 1,
                'ext'             => ['LIKE', $ext],
            ],
            'LIMIT'  => 1,
        ]);

        if (count($iterator)) {
            return Toolbox::strtoupper($ext);
        }

        // Not found try with regex one
        $iterator = $DB->request([
            'SELECT' => ['ext'],
            'FROM'   => 'glpi_documenttypes',
            'WHERE'  => [
                'is_uploadable'   => 1,
                'ext'             => ['LIKE', '/%/'],
            ],
        ]);

        foreach ($iterator as $data) {
            if (preg_match($data['ext'] . "i", $ext) > 0) {
                return Toolbox::strtoupper($ext);
            }
        }

        return "";
    }

    /**
     * Make a select box for link document
     *
     * Parameters which could be used in options array :
     *    - name : string / name of the select (default is documents_id)
     *    - entity : integer or array / restrict to a defined entity or array of entities
     *                   (default -1 : no restriction)
     *    - used : array / Already used items ID: not to display in dropdown (default empty)
     *    - hide_if_no_elements  : boolean / hide dropdown if there is no elements (default false)
     *
     * @param array $options Array of possible options
     *
     * @return integer|string|void
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     *    void if hide_if_no_elements=true and no elements
     **/
    public static function dropdown($options = [])
    {
        global $CFG_GLPI, $DB;

        $p['name']    = 'documents_id';
        $p['entity']  = '';
        $p['used']    = [];
        $p['display'] = true;
        $p['hide_if_no_elements'] = false;
        $p['readonly'] = false;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        if (isset($p['value']) && ($p['value'] > 0)) {
            $document = new Document();
            $document->getFromDB($p['value']);
            $p['rubdoc'] = $document->fields['documentcategories_id'];
        }

        $subwhere = [
            'glpi_documents.is_deleted'   => 0,
        ] + getEntitiesRestrictCriteria('glpi_documents', '', $p['entity'], true);

        if (count($p['used'])) {
            $subwhere['NOT'] = ['id' => array_merge([0], $p['used'])];
        }

        $criteria = [
            'FROM'   => 'glpi_documentcategories',
            'WHERE'  => [
                'id' => new QuerySubQuery([
                    'SELECT'          => 'documentcategories_id',
                    'DISTINCT'        => true,
                    'FROM'            => 'glpi_documents',
                    'WHERE'           => $subwhere,
                ]),
            ],
            'ORDER'  => 'name',
        ];
        $iterator = $DB->request($criteria);

        if ($p['hide_if_no_elements'] && $iterator->count() === 0) {
            return;
        }

        $values = [];
        foreach ($iterator as $data) {
            $values[$data['id']] = $data['name'];
        }
        $rand = mt_rand();
        $readonly = $p['readonly'];
        $out = '';
        $width = '30%';
        if ($readonly) {
            $width = '100%';
            $out .= '<div class="row">';
            $out .= '<div class="col-xxl-5 p-0">';
        }
        $out  .= Dropdown::showFromArray('_rubdoc', $values, [
            'width'               => $width,
            'rand'                => $rand,
            'display'             => false,
            'display_emptychoice' => true,
            'value'               => $p['rubdoc'] ?? 0,
            'readonly'            => $readonly,
        ]);
        $field_id = Html::cleanId("dropdown__rubdoc$rand");

        $params   = ['rubdoc' => '__VALUE__',
            'entity' => $p['entity'],
            'rand'   => $rand,
            'myname' => $p['name'],
            'used'   => $p['used'],
        ];

        if ($readonly) {
            $out .= '</div>';
            $out .= '<div class="col-xxl-7 p-0">';
        }
        $out .= Ajax::updateItemOnSelectEvent(
            $field_id,
            "show_" . $p['name'] . $rand,
            $CFG_GLPI["root_doc"] . "/ajax/dropdownRubDocument.php",
            $params,
            false
        );
        $out .= "<span id='show_" . htmlescape($p['name']) . "$rand'>";
        $out .= "</span>\n";

        $params['rubdoc'] = $p['rubdoc'] ?? 0;
        $params['value'] = $p['value'] ?? 0;
        if ($readonly) {
            $document = new Document();
            $doclist = $document->find([]);
            foreach ($doclist as $doc) {
                $docvalue[$doc['id']] = $doc['name'];
            }

            $out .= Dropdown::showFromArray('document', $docvalue ?? [], [
                'width'               => $width,
                'rand'                => $rand,
                'display'             => false,
                'display_emptychoice' => true,
                'value'               => $p['value'] ?? 0,
                'readonly'            => $readonly,
            ]);
            $out .= '</div>';
            $out .= '</div>';
        } else {
            $out .= Ajax::updateItem(
                toupdate  : "show_" . $p['name'] . $rand,
                url       : $CFG_GLPI["root_doc"] . "/ajax/dropdownRubDocument.php",
                parameters: $params,
                display   : false,
            );
        }
        if ($p['display']) {
            echo $out;
            return $rand;
        }
        return $out;
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ) {
        $action_prefix = 'Document_Item' . MassiveAction::CLASS_ACTION_SEPARATOR;

        if (self::canApplyOn($itemtype)) {
            if (self::canView()) {
                $actions[$action_prefix . 'add']    = "<i class='ti ti-file-plus'></i>"
                                                . _sx('button', 'Add a document');
                $actions[$action_prefix . 'remove'] = "<i class='ti ti-file-minus'></i>"
                                                . _sx('button', 'Remove a document');
            }
        }

        if ((is_a($itemtype, self::class, true)) && (static::canUpdate())) {
            $actions[$action_prefix . 'add_item']    = "<i class='ti ti-package'></i>" . _sx('button', 'Add an item');
            $actions[$action_prefix . 'remove_item'] = "<i class='ti ti-package-off'></i>" . _sx('button', 'Remove an item');
        }
    }

    /**
     * @since 0.85
     *
     * @param $string
     *
     * @return string
     **/
    public static function getImageTag($string): string
    {
        return self::$tag_prefix . $string . self::$tag_prefix;
    }

    /**
     * Is file an image
     *
     * @since 9.2.1
     *
     * @param string $file File name
     *
     * @return boolean
     */
    public static function isImage($file): bool
    {
        if (!file_exists($file) || !is_file($file)) {
            return false;
        }
        if (extension_loaded('exif')) {
            if (filesize($file) < 12) {
                return false;
            }
            $etype = exif_imagetype($file);
            return in_array($etype, [IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_BMP, IMAGETYPE_WEBP], true);
        }

        trigger_error(
            'For security reasons, you should consider using exif PHP extension to properly check images.',
            E_USER_WARNING
        );
        $fileinfo = finfo_open(FILEINFO_MIME_TYPE);
        return in_array(
            finfo_file($fileinfo, $file),
            ['image/jpeg', 'image/png','image/gif', 'image/bmp', 'image/webp']
        );
    }

    /**
     * Get resized image path.
     *
     * @since 10.0.1
     *
     * @param string  $path
     * @param integer $width
     * @param integer $height
     *
     * @return string
     */
    public static function getResizedImagePath(string $path, int $width, int $height): string
    {
        // let's see if original image needs resize
        $img_infos  = getimagesize($path);
        if ($img_infos[0] <= $width && $img_infos[1] <= $height) {
            // no resize needed, source image is smaller than requested width/height
            return $path;
        }

        $infos = pathinfo($path);
        // output images with possible transparency to png, other to jpg
        $extension = in_array(strtolower($infos['extension']), ['png', 'gif']) ? 'png' : 'jpg';
        $context_path = sprintf(
            '%1$s_%2$s-%3$s.%4$s',
            $infos['dirname'] . '/' . $infos['filename'],
            $width,
            $height,
            $extension
        );

        // let's check if file already exists
        if (file_exists($context_path)) {
            return $context_path;
        }

        // do resize
        $result = Toolbox::resizePicture(
            $path,
            $context_path,
            $width,
            $height,
            0,
            0,
            0,
            0,
            ($width > $height ? $width : $height)
        );
        return ($result ? $context_path : $path);
    }

    /**
     * Give cron information
     *
     * @param string $name task's name
     *
     * @return array of information
     **/
    public static function cronInfo($name): array
    {
        return match ($name) {
            'cleanorphans' => ['description' => __('Clean orphaned documents: deletes all documents that are not associated with any items.')],
            default => [],
        };
    }

    /**
     * Cron for clean orphan documents (without Document_Item)
     *
     * @param CronTask $task CronTask object
     *
     * @return integer (0 : nothing done - 1 : done)
     * @used-by CronTask
     **/
    public static function cronCleanOrphans(CronTask $task): int
    {
        global $DB;

        $dtable = static::getTable();
        $ditable = Document_Item::getTable();
        // documents that are not present in Document_Item are oprhan
        $iterator = $DB->request([
            'SELECT'    => ["$dtable.id"],
            'FROM'      => $dtable,
            'LEFT JOIN' => [
                $ditable => [
                    'ON'  => [
                        $dtable  => 'id',
                        $ditable => 'documents_id',
                    ],
                ],
            ],
            'WHERE'     => [
                "$ditable.documents_id" => null,
            ],
        ]);

        $nb = 0;
        if (count($iterator)) {
            foreach ($iterator as $row) {
                $doc = new Document();
                $doc->delete(['id' => $row['id']], true);
                ++$nb;
            }
        }

        if ($nb) {
            $task->addVolume($nb);
            $task->log("Documents : $nb");
        }

        return ($nb > 0 ? 1 : 0);
    }

    public static function getIcon()
    {
        return "ti ti-files";
    }

    /**
     * Find and load a document which is a duplicate of a file, with respect of blacklisting
     *
     * @param integer $entities_id    Entity of the document
     * @param string  $filename      Name of the searched file
     *
     * @return boolean
     */
    public function getDuplicateOf(int $entities_id, string $filename): bool
    {
        if (!$this->getFromDBbyContent($entities_id, $filename)) {
            return false;
        }

        if ($this->fields['is_blacklisted']) {
            return false;
        }

        return true;
    }

    /**
     * It checks if a file exists and is readable
     *
     * @param string $filename The name of the file to check.
     *
     * @return boolean
     */
    public function checkAvailability(string $filename): bool
    {
        $file = GLPI_DOC_DIR . '/' . $filename;
        return file_exists($file) && is_readable($file);
    }
}
