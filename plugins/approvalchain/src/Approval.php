<?php

namespace GlpiPlugin\Approvalchain;

use CommonDBTM;
use CommonGLPI;
use Dropdown;
use GLPIMailer;
use Glpi\RichText\RichText;
use Html;
use ITILFollowup;
use ITILSolution;
use Plugin;
use Session;
use Ticket;
use Toolbox;
use User;
use UserEmail;

/**
 * An approval chain running on a ticket + the engine that drives it.
 */
class Approval extends CommonDBTM
{
    public static $rightname = 'ticket';
    public $dohistory        = false;

    public const IN_PROGRESS = 1;
    public const APPROVED    = 2;
    public const REFUSED     = 3;
    public const CANCELLED   = 4;

    /** Decision notes added to the ticket: private (technicians only) or public */
    public const FOLLOWUP_PRIVATE = false;

    public static function getTypeName($nb = 0): string
    {
        return _n('Approval chain', 'Approval chains', $nb, 'approvalchain');
    }

    public static function getIcon(): string
    {
        return 'ti ti-list-check';
    }

    /* ------------------------------------------------------------------
     * Hooks
     * ---------------------------------------------------------------- */

    public static function onTicketAdd(Ticket $ticket): void
    {
        $fresh = new Ticket();
        if (!$fresh->getFromDB((int) $ticket->getID())) {
            return;
        }
        $chain_id = Chain::findForTicket($fresh);
        if ($chain_id > 0) {
            self::start($fresh, $chain_id, (int) Session::getLoginUserID());
        }
    }

    public static function onTicketPreUpdate(Ticket $ticket): void
    {
        $status = (int) ($ticket->input['status'] ?? 0);
        if (!in_array($status, [Ticket::SOLVED, Ticket::CLOSED], true)) {
            return;
        }
        if (self::getRunning((int) $ticket->getID()) !== null) {
            $ticket->input = false;
            Session::addMessageAfterRedirect(
                __('This ticket cannot be solved or closed while its approval chain is in progress.', 'approvalchain'),
                false,
                ERROR
            );
        }
    }

    public static function onSolutionPreAdd(ITILSolution $solution): void
    {
        if (($solution->input['itemtype'] ?? '') !== 'Ticket') {
            return;
        }
        if (self::getRunning((int) ($solution->input['items_id'] ?? 0)) !== null) {
            $solution->input = false;
            Session::addMessageAfterRedirect(
                __('A solution cannot be added while the approval chain is in progress.', 'approvalchain'),
                false,
                ERROR
            );
        }
    }

    public static function onTicketPurge(Ticket $ticket): void
    {
        global $DB;
        foreach ($DB->request(['SELECT' => ['id'], 'FROM' => self::getTable(), 'WHERE' => ['tickets_id' => $ticket->getID()]]) as $row) {
            $DB->delete(ApprovalStep::getTable(), ['plugin_approvalchain_approvals_id' => $row['id']]);
            $DB->delete(self::getTable(), ['id' => $row['id']]);
        }
    }

    /* ------------------------------------------------------------------
     * Engine
     * ---------------------------------------------------------------- */

    public static function getRunning(int $tickets_id): ?array
    {
        global $DB;
        foreach ($DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['tickets_id' => $tickets_id, 'status' => self::IN_PROGRESS],
            'LIMIT' => 1,
        ]) as $row) {
            return $row;
        }
        return null;
    }

    /** Snapshot a chain onto a ticket and activate step 1. */
    public static function start(Ticket $ticket, int $chains_id, int $users_id = 0): int|false
    {
        global $DB;

        $chain = new Chain();
        if (!$chain->getFromDB($chains_id) || !$chain->fields['is_active']) {
            return false;
        }
        if (self::getRunning((int) $ticket->getID()) !== null) {
            return false;
        }
        $steps = iterator_to_array($DB->request([
            'FROM'  => ChainStep::getTable(),
            'WHERE' => ['plugin_approvalchain_chains_id' => $chains_id],
            'ORDER' => ['position ASC', 'id ASC'],
        ]), false);
        if (!count($steps)) {
            return false;
        }

        $approval = new self();
        $id = $approval->add([
            'tickets_id'                     => (int) $ticket->getID(),
            'plugin_approvalchain_chains_id' => $chains_id,
            'name'                           => $chain->fields['name'],
            'status'                         => self::IN_PROGRESS,
            'users_id'                       => $users_id,
        ]);
        if (!$id) {
            return false;
        }

        $first = 0;
        foreach (array_values($steps) as $i => $s) {
            $step = new ApprovalStep();
            $sid  = $step->add([
                'plugin_approvalchain_approvals_id' => $id,
                'position'  => $i + 1,
                'name'      => $s['name'],
                'comment'   => $s['comment'],
                'users_id'  => (int) $s['users_id'],
                'groups_id' => (int) $s['groups_id'],
                'status'    => ApprovalStep::WAITING,
            ]);
            if ($i === 0) {
                $first = (int) $sid;
            }
        }
        self::activate($first, $ticket);

        Session::addMessageAfterRedirect(
            sprintf(__('Approval chain "%s" started.', 'approvalchain'), $chain->fields['name']),
            false,
            INFO
        );
        return (int) $id;
    }

    private static function activate(int $step_id, Ticket $ticket): void
    {
        global $DB;
        $DB->update(ApprovalStep::getTable(), ['status' => ApprovalStep::PENDING], ['id' => $step_id]);
        $step = new ApprovalStep();
        if ($step->getFromDB($step_id)) {
            self::notify($step->fields, $ticket);
        }
    }

    public static function userCanDecide(array $step, int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }
        if ((int) $step['users_id'] === $uid) {
            return true;
        }
        if ((int) $step['groups_id'] > 0) {
            return countElementsInTable('glpi_groups_users', [
                'users_id'  => $uid,
                'groups_id' => (int) $step['groups_id'],
            ]) > 0;
        }
        return false;
    }

    private static function setStatus(int $id, int $status): void
    {
        global $DB;
        $DB->update(self::getTable(), [
            'status'   => $status,
            'date_mod' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    private static function error(string $msg): bool
    {
        Session::addMessageAfterRedirect($msg, false, ERROR);
        return false;
    }

    public static function decide(int $step_id, bool $approve, string $comment, int $uid): bool
    {
        global $DB;

        $comment = trim($comment);
        $step    = new ApprovalStep();
        $approval = new self();

        if (!$step->getFromDB($step_id)
            || !$approval->getFromDB((int) $step->fields['plugin_approvalchain_approvals_id'])
            || (int) $step->fields['status'] !== ApprovalStep::PENDING
            || (int) $approval->fields['status'] !== self::IN_PROGRESS) {
            return self::error(__('This step is no longer awaiting a decision.', 'approvalchain'));
        }
        if (!self::userCanDecide($step->fields, $uid)) {
            return self::error(__('You are not an approver for this step.', 'approvalchain'));
        }
        if (!$approve && $comment === '') {
            return self::error(__('Please give a reason when refusing.', 'approvalchain'));
        }

        $now = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
        // Conditional update => safe against two group members clicking at once
        $DB->update(ApprovalStep::getTable(), [
            'status'            => $approve ? ApprovalStep::APPROVED : ApprovalStep::REFUSED,
            'users_id_decision' => $uid,
            'comment_decision'  => $comment,
            'date_decision'     => $now,
            'date_mod'          => $now,
        ], ['id' => $step_id, 'status' => ApprovalStep::PENDING]);
        if ($DB->affectedRows() !== 1) {
            return self::error(__('This step was already decided.', 'approvalchain'));
        }

        $approval_id = (int) $approval->getID();
        $position    = (int) $step->fields['position'];
        $ticket      = new Ticket();
        $ticket->getFromDB((int) $approval->fields['tickets_id']);

        $head = sprintf(
            __('Approval chain "%1$s", step %2$d "%3$s"', 'approvalchain'),
            $approval->fields['name'],
            $position,
            $step->fields['name']
        );
        $who = User::getFriendlyNameById($uid);

        if ($approve) {
            $next = 0;
            foreach ($DB->request([
                'SELECT' => ['id'],
                'FROM'   => ApprovalStep::getTable(),
                'WHERE'  => ['plugin_approvalchain_approvals_id' => $approval_id, 'position' => ['>', $position]],
                'ORDER'  => 'position ASC',
                'LIMIT'  => 1,
            ]) as $row) {
                $next = (int) $row['id'];
            }
            $text = sprintf(__('%1$s approved by %2$s.', 'approvalchain'), $head, $who);
            if ($next > 0) {
                self::activate($next, $ticket);
                $text .= ' ' . __('Moving on to the next step.', 'approvalchain');
            } else {
                self::setStatus($approval_id, self::APPROVED);
                $text .= ' ' . __('All steps approved: the chain is complete.', 'approvalchain');
            }
        } else {
            $DB->update(ApprovalStep::getTable(), ['status' => ApprovalStep::SKIPPED], [
                'plugin_approvalchain_approvals_id' => $approval_id,
                'status' => ApprovalStep::WAITING,
            ]);
            self::setStatus($approval_id, self::REFUSED);
            $text = sprintf(__('%1$s refused by %2$s. The chain is stopped.', 'approvalchain'), $head, $who);
        }

        self::followup($ticket, $uid, $text, $comment);
        Session::addMessageAfterRedirect(__('Your decision has been recorded.', 'approvalchain'), false, INFO);
        return true;
    }

    public static function cancel(int $approvals_id): bool
    {
        global $DB;
        $approval = new self();
        if (!$approval->getFromDB($approvals_id) || (int) $approval->fields['status'] !== self::IN_PROGRESS) {
            return false;
        }
        $DB->update(ApprovalStep::getTable(), ['status' => ApprovalStep::SKIPPED], [
            'plugin_approvalchain_approvals_id' => $approvals_id,
            'status' => [ApprovalStep::WAITING, ApprovalStep::PENDING],
        ]);
        self::setStatus($approvals_id, self::CANCELLED);
        return true;
    }

    private static function followup(Ticket $ticket, int $uid, string $text, string $comment): void
    {
        try {
            $html = '<p><strong>' . Util::e($text) . '</strong></p>';
            if ($comment !== '') {
                $html .= '<p>' . nl2br(Util::e($comment)) . '</p>';
            }
            $fup = new ITILFollowup();
            $fup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => (int) $ticket->getID(),
                'content'    => $html,
                'is_private' => self::FOLLOWUP_PRIVATE ? 1 : 0,
                'users_id'   => $uid,
            ]);
        } catch (\Throwable $e) {
            Toolbox::logInFile('approvalchain', $e->getMessage() . "\n");
        }
    }

    /** Best-effort e-mail to the approver(s) of a step that just became active. */
    private static function notify(array $step, Ticket $ticket): void
    {
        global $CFG_GLPI, $DB;

        try {
            if (empty($CFG_GLPI['use_notifications']) || empty($CFG_GLPI['notifications_mailing'])) {
                return;
            }
            $uids = [];
            if ((int) $step['users_id'] > 0) {
                $uids[] = (int) $step['users_id'];
            }
            if ((int) $step['groups_id'] > 0) {
                foreach ($DB->request(['SELECT' => ['users_id'], 'FROM' => 'glpi_groups_users',
                    'WHERE' => ['groups_id' => (int) $step['groups_id']]]) as $r) {
                    $uids[] = (int) $r['users_id'];
                }
            }
            $emails = [];
            foreach (array_unique($uids) as $uid) {
                $mail = UserEmail::getDefaultForUser($uid);
                if (!empty($mail)) {
                    $emails[$mail] = $mail;
                }
            }
            if (!$emails) {
                return;
            }
            $from = !empty($CFG_GLPI['from_email']) ? $CFG_GLPI['from_email'] : ($CFG_GLPI['admin_email'] ?? '');
            $link = rtrim($CFG_GLPI['url_base'] ?? '', '/') . '/plugins/approvalchain/front/pending.php';
            $subject = sprintf(__('Approval required: ticket #%1$d - %2$s', 'approvalchain'), $ticket->getID(), $step['name']);
            $body = '<p>' . Util::e(sprintf(
                __('Your approval is needed on ticket #%1$d "%2$s" (step "%3$s").', 'approvalchain'),
                $ticket->getID(),
                $ticket->fields['name'],
                $step['name']
            )) . '</p><p><a href="' . Util::e($link) . '">' . Util::e(__('Open my pending approvals', 'approvalchain')) . '</a></p>';

            foreach ($emails as $email) {
                $mailer = new GLPIMailer();
                $mailer->setFrom($from, $CFG_GLPI['admin_email_name'] ?? '');
                $mailer->addAddress($email);
                $mailer->isHTML(true);
                $mailer->Subject = $subject;
                $mailer->Body    = $body;
                $mailer->AltBody = strip_tags($body);
                $mailer->send();
            }
        } catch (\Throwable $e) {
            Toolbox::logInFile('approvalchain', 'notify: ' . $e->getMessage() . "\n");
        }
    }

    /* ------------------------------------------------------------------
     * UI: ticket tab
     * ---------------------------------------------------------------- */

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
    {
        if ($item instanceof Ticket && $item->getID() > 0) {
            $nb = countElementsInTable(self::getTable(), ['tickets_id' => $item->getID()]);
            return self::createTabEntry(__('Approval chain', 'approvalchain'), $nb);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item instanceof Ticket) {
            self::showForTicket($item);
        }
        return true;
    }

    public static function showForTicket(Ticket $ticket): void
    {
        global $DB;

        $tid = (int) $ticket->getID();
        $uid = (int) Session::getLoginUserID();

        echo "<div class='p-3'>";
        if (self::getRunning($tid) === null && Ticket::canUpdate()) {
            self::showStartForm($ticket);
        }
        $any = false;
        foreach ($DB->request(['FROM' => self::getTable(), 'WHERE' => ['tickets_id' => $tid], 'ORDER' => 'id DESC']) as $row) {
            $any = true;
            self::showCard($row, $uid);
        }
        if (!$any) {
            echo "<div class='text-muted'>" . __('No approval chain on this ticket.', 'approvalchain') . "</div>";
        }
        echo "</div>";
    }

    private static function showStartForm(Ticket $ticket): void
    {
        global $DB;

        $chains = [];
        foreach ($DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => Chain::getTable(),
            'WHERE'  => ['is_active' => 1] + getEntitiesRestrictCriteria(Chain::getTable(), 'entities_id', (int) $ticket->fields['entities_id'], true),
            'ORDER'  => 'name',
        ]) as $c) {
            $chains[(int) $c['id']] = $c['name'];
        }
        if (!$chains) {
            echo "<div class='alert alert-info'>" . __('No active approval chain is available for this ticket\'s entity.', 'approvalchain') . "</div>";
            return;
        }
        echo "<form method='post' action='" . self::getFormURL() . "' class='d-flex gap-2 align-items-center mb-3'>";
        echo "<input type='hidden' name='tickets_id' value='" . (int) $ticket->getID() . "'>";
        Dropdown::showFromArray('plugin_approvalchain_chains_id', $chains);
        echo "<button type='submit' name='start' class='btn btn-primary'><i class='ti ti-player-play'></i> " . __('Start approval chain', 'approvalchain') . "</button>";
        Html::closeForm();
    }

    private static function showCard(array $row, int $uid): void
    {
        global $DB;

        $aid     = (int) $row['id'];
        $running = ((int) $row['status'] === self::IN_PROGRESS);
        $labels  = [
            self::IN_PROGRESS => ['warning', __('In progress', 'approvalchain')],
            self::APPROVED    => ['success', __('Approved', 'approvalchain')],
            self::REFUSED     => ['danger',  __('Refused', 'approvalchain')],
            self::CANCELLED   => ['secondary', __('Cancelled', 'approvalchain')],
        ];
        [$color, $label] = $labels[(int) $row['status']];

        echo "<div class='card mb-3'><div class='card-header'><div class='card-title'>" . Util::e($row['name']) . "</div>";
        echo "<div class='ms-auto d-flex gap-2 align-items-center'><span class='badge bg-{$color}-lt'>" . Util::e($label) . "</span>";
        if ($running && Ticket::canUpdate()) {
            echo "<form method='post' action='" . self::getFormURL() . "'><input type='hidden' name='id' value='{$aid}'>";
            echo "<button type='submit' name='cancel' class='btn btn-sm btn-outline-danger' onclick=\"return confirm('" . Util::e(__('Cancel this approval chain?', 'approvalchain')) . "')\">" . __('Cancel chain', 'approvalchain') . "</button>";
            Html::closeForm();
        }
        echo "</div></div><div class='list-group list-group-flush'>";

        foreach ($DB->request([
            'FROM'  => ApprovalStep::getTable(),
            'WHERE' => ['plugin_approvalchain_approvals_id' => $aid],
            'ORDER' => 'position ASC',
        ]) as $s) {
            echo "<div class='list-group-item'><div class='row align-items-center'>";
            echo "<div class='col-auto'><span class='badge bg-blue-lt'>" . (int) $s['position'] . "</span></div>";
            echo "<div class='col'><strong>" . Util::e($s['name']) . "</strong>";
            if ($s['comment'] !== null && $s['comment'] !== '') {
                echo "<div class='text-muted small'>" . Util::e($s['comment']) . "</div>";
            }
            echo "<div class='small'>" . __('Approver', 'approvalchain') . ": " . Util::approver((int) $s['users_id'], (int) $s['groups_id']) . "</div>";
            if ((int) $s['users_id_decision'] > 0) {
                echo "<div class='small text-muted'>" . Util::e(User::getFriendlyNameById((int) $s['users_id_decision']))
                    . " &middot; " . Util::e(Html::convDateTime($s['date_decision']));
                if ($s['comment_decision'] !== null && $s['comment_decision'] !== '') {
                    echo " &mdash; " . Util::e($s['comment_decision']);
                }
                echo "</div>";
            }
            echo "</div><div class='col-auto'>" . ApprovalStep::badge((int) $s['status']) . "</div></div>";

            if ($running && (int) $s['status'] === ApprovalStep::PENDING && self::userCanDecide($s, $uid)) {
                self::decisionForm((int) $s['id']);
            }
            echo "</div>";
        }
        echo "</div></div>";
    }

    public static function decisionForm(int $step_id): void
    {
        echo "<form method='post' action='" . self::getFormURL() . "' class='mt-2'>";
        echo "<input type='hidden' name='step_id' value='{$step_id}'>";
        echo "<textarea name='comment' rows='2' class='form-control mb-2' placeholder='" . Util::e(__('Comment (required when refusing)', 'approvalchain')) . "'></textarea>";
        echo "<button type='submit' name='approve' class='btn btn-success me-2'><i class='ti ti-check'></i> " . __('Approve', 'approvalchain') . "</button>";
        echo "<button type='submit' name='refuse' class='btn btn-danger'><i class='ti ti-x'></i> " . __('Refuse', 'approvalchain') . "</button>";
        Html::closeForm();
    }

    /* ------------------------------------------------------------------
     * UI: "My pending approvals" page
     * ---------------------------------------------------------------- */

    public static function showPending(int $uid): void
    {
        global $DB;

        $gids = [];
        foreach ($DB->request(['SELECT' => ['groups_id'], 'FROM' => 'glpi_groups_users', 'WHERE' => ['users_id' => $uid]]) as $r) {
            $gids[] = (int) $r['groups_id'];
        }
        $who = $gids
            ? ['OR' => [['users_id' => $uid], ['groups_id' => $gids]]]
            : ['users_id' => $uid];

        echo "<div class='container-xl'><h2 class='mb-3'>" . __('My pending approvals', 'approvalchain') . "</h2>";
        $any = false;
        foreach ($DB->request([
            'FROM'  => ApprovalStep::getTable(),
            'WHERE' => ['status' => ApprovalStep::PENDING] + $who,
            'ORDER' => 'date_creation ASC',
        ]) as $s) {
            $approval = new self();
            $ticket   = new Ticket();
            if (!$approval->getFromDB((int) $s['plugin_approvalchain_approvals_id'])
                || !$ticket->getFromDB((int) $approval->fields['tickets_id'])) {
                continue;
            }
            $any = true;
            echo "<div class='card mb-3'><div class='card-header'><div class='card-title'>";
            echo "<a href='" . Ticket::getFormURLWithID($ticket->getID()) . "'>#" . (int) $ticket->getID() . " &ndash; " . Util::e($ticket->fields['name']) . "</a></div>";
            echo "<div class='ms-auto text-muted'>" . Util::e($approval->fields['name']) . "</div></div>";
            echo "<div class='card-body'><p><strong>" . sprintf(__('Step %1$d: %2$s', 'approvalchain'), (int) $s['position'], Util::e($s['name'])) . "</strong>";
            if ($s['comment'] !== null && $s['comment'] !== '') {
                echo "<br><span class='text-muted'>" . Util::e($s['comment']) . "</span>";
            }
            echo "</p><div class='border rounded p-2 mb-3 bg-light-subtle'>" . RichText::getSafeHtml($ticket->fields['content']) . "</div>";
            self::decisionForm((int) $s['id']);
            echo "</div></div>";
        }
        if (!$any) {
            echo "<div class='alert alert-success'>" . __('Nothing is waiting for your approval.', 'approvalchain') . "</div>";
        }
        echo "</div>";
    }
}
