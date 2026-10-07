<?php

namespace GlpiPlugin\Approvalchain;

use CommonDBTM;
use Dropdown;
use Entity;
use ITILCategory;
use Plugin;
use Session;
use Ticket;

/**
 * A reusable approval chain definition (ordered list of steps).
 */
class Chain extends CommonDBTM
{
    use ConfigRights;

    public static $rightname = 'config';
    public $dohistory        = false;

    public static function getTypeName($nb = 0): string
    {
        return _n('Approval chain', 'Approval chains', $nb, 'approvalchain');
    }

    public static function getIcon(): string
    {
        return 'ti ti-list-check';
    }

    public static function getMenuContent(): array
    {
        global $CFG_GLPI;

        $pending = Plugin::getWebDir('approvalchain', false) . '/front/pending.php';
        $menu = [
            'title' => self::getTypeName(Session::getPluralNumber()),
            'icon'  => self::getIcon(),
        ];
        if (self::canView()) {
            $menu['page']                = self::getSearchURL(false);
            $menu['links']['search']     = self::getSearchURL(false);
            if (self::canCreate()) {
                $menu['links']['add']    = self::getFormURL(false);
            }
        } else {
            $menu['page'] = $pending;
        }
        $menu['links']['<i class="ti ti-inbox" title="' . __('My pending approvals', 'approvalchain') . '"></i>'] = $pending;

        return $menu;
    }

    public function defineTabs($options = []): array
    {
        $tabs = [];
        $this->addDefaultFormTab($tabs);
        $this->addStandardTab(ChainStep::class, $tabs, $options);
        return $tabs;
    }

    public function rawSearchOptions(): array
    {
        $table = self::getTable();
        return [
            ['id' => 'common', 'name' => self::getTypeName(2)],
            ['id' => '1', 'table' => $table, 'field' => 'name', 'name' => __('Name'),
                'datatype' => 'itemlink', 'massiveaction' => false],
            ['id' => '2', 'table' => $table, 'field' => 'id', 'name' => __('ID'),
                'datatype' => 'number', 'massiveaction' => false],
            ['id' => '3', 'table' => $table, 'field' => 'is_active', 'name' => __('Active'),
                'datatype' => 'bool'],
            ['id' => '4', 'table' => ITILCategory::getTable(), 'field' => 'completename',
                'name' => __('Category'), 'datatype' => 'dropdown'],
            ['id' => '19', 'table' => $table, 'field' => 'date_mod', 'name' => __('Last update'),
                'datatype' => 'datetime', 'massiveaction' => false],
            ['id' => '80', 'table' => 'glpi_entities', 'field' => 'completename',
                'name' => Entity::getTypeName(1), 'datatype' => 'dropdown'],
        ];
    }

    public function prepareInputForAdd($input): array|false
    {
        if (trim((string) ($input['name'] ?? '')) === '') {
            Session::addMessageAfterRedirect(__('A name is required.', 'approvalchain'), false, ERROR);
            return false;
        }
        $input['is_active'] = 0; // activate once steps exist
        return $input;
    }

    public function prepareInputForUpdate($input): array|false
    {
        if (!empty($input['is_active'])) {
            $id    = (int) ($input['id'] ?? $this->getID());
            $where = ['plugin_approvalchain_chains_id' => $id];
            $total      = countElementsInTable(ChainStep::getTable(), $where);
            $unassigned = countElementsInTable(ChainStep::getTable(), $where + ['users_id' => 0, 'groups_id' => 0]);
            if ($total === 0 || $unassigned > 0) {
                Session::addMessageAfterRedirect(
                    __('A chain needs at least one step and every step needs an approver before it can be activated.', 'approvalchain'),
                    false,
                    ERROR
                );
                $input['is_active'] = 0;
            }
        }
        return $input;
    }

    public function cleanDBonPurge(): void
    {
        $this->deleteChildrenAndRelationsFromDb([ChainStep::class]);
    }

    public function showForm($ID, array $options = []): bool
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        $isnew = $this->isNewID($ID);

        echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td><td>";
        echo "<input type='text' class='form-control' name='name' required value='" . Util::e($this->fields['name'] ?? '') . "'>";
        echo "</td><td>" . __('Trigger category', 'approvalchain') . "</td><td>";
        ITILCategory::dropdown([
            'name'                => 'itilcategories_id',
            'value'               => (int) ($this->fields['itilcategories_id'] ?? 0),
            'entity'              => (int) ($this->fields['entities_id'] ?? 0),
            'display_emptychoice' => true,
            'emptylabel'          => __('Any category', 'approvalchain'),
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Active') . "</td><td>";
        if ($isnew) {
            echo "<input type='hidden' name='entities_id' value='" . (int) $this->fields['entities_id'] . "'>";
            echo "<span class='text-muted'>" . __('Add the steps first, then activate the chain.', 'approvalchain') . "</span>";
        } else {
            Dropdown::showYesNo('is_active', (int) $this->fields['is_active']);
        }
        echo "</td><td>" . __('Child entities') . "</td><td>";
        Dropdown::showYesNo('is_recursive', (int) ($this->fields['is_recursive'] ?? 0));
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td><td colspan='3'>";
        echo "<textarea class='form-control' name='comment' rows='3'>" . Util::e($this->fields['comment'] ?? '') . "</textarea>";
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Best matching active chain for a ticket (specific category wins over "any").
     */
    public static function findForTicket(Ticket $ticket): int
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'is_active'         => 1,
                'itilcategories_id' => [0, (int) $ticket->fields['itilcategories_id']],
            ] + getEntitiesRestrictCriteria(self::getTable(), 'entities_id', (int) $ticket->fields['entities_id'], true),
            'ORDER'  => ['itilcategories_id DESC', 'id ASC'],
            'LIMIT'  => 1,
        ]);
        foreach ($iterator as $row) {
            return (int) $row['id'];
        }
        return 0;
    }
}
