<?php

namespace Glpi\PHPUnit\Tests\Glpi;

use OLA;
use SLM;

trait ITILTrait
{
    /**
     * @param $data array additionnal data (override default data (@see getValidTicketData())
     */
    private function createTicket(array $data = [], array $skip_fields = []): \Ticket
    {
        return $this->createItem(
            \Ticket::class,
            $data + $this->getValidTicketData(),
            $skip_fields
        );
    }

    /**
     * @todo missing fields for form submission (_actors, kb_linked_id, etc, @see front/ticket.form.php, Ticket::post_updateItem, etc)
     */
    private function getValidTicketData(): array
    {
        return [
//            'id' => 0,
//            'entities_id' => 0,
            'name' => 'ticket name ' . time(),
//            'date' => null,
//            'closedate' => null,
//            'solvedate' => null,
//            'takeintoaccountdate' => null,
//            'date_mod' => null,
//            'users_id_lastupdater' => 0,
            'status' => \CommonITILObject::WAITING,
//            'users_id_recipient' => 0,
//            'requesttypes_id' => 0,
            'content' => 'Ticket Example content',
//            'urgency' => 1,
//            'impact' => 1,
//            'priority' => 1,
//            'itilcategories_id' => 0,
//            'type' => 1,
//            'global_validation' => 1,
//            'slas_id_ttr' => 0,
//            'slas_id_tto' => 0,
//            'slalevels_id_ttr' => 0,
//            'time_to_resolve' => null,
//            'time_to_own' => null,
//            'begin_waiting_date' => null,
//            'sla_waiting_duration' => 0,
//            'waiting_duration' => 0,
//            'close_delay_stat' => 0,
//            'solve_delay_stat' => 0,
//            'takeintoaccount_delay_stat' => 0,
//            'actiontime' => 0,
//            'is_deleted' => 0,
//            'locations_id' => 0,
//            'validation_percent' => 0,
//            'date_creation' => null,
//            'tickettemplates_id' => 0,
//            'externalid' => null,
        ];
    }

}