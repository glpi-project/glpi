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

return [
    [
        'key'     => 'central',
        'name'    => __("Central"),
        'context' => 'core',
        '_items'  => [
            [
                "x" => 3, "y" => 0, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Computer_4a315743-151c-40cb-a20b-762250668dac",
                "card_id"      => "bn_count_Computer",
                "card_options" => "{\"color\":\"#e69393\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 0, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Software_0690f524-e826-47a9-b50a-906451196b83",
                "card_id"      => "bn_count_Software",
                "card_options" => "{\"color\":\"#aaddac\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 6, "y" => 2, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Rack_c6502e0a-5991-46b4-a771-7f355137306b",
                "card_id"      => "bn_count_Rack",
                "card_options" => "{\"color\":\"#0e87a0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 2, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_SoftwareLicense_e755fd06-283e-4479-ba35-2d548f8f8a90",
                "card_id"      => "bn_count_SoftwareLicense",
                "card_options" => "{\"color\":\"#27ab3c\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 3, "y" => 2, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Monitor_7059b94c-583c-4ba7-b100-d40461165318",
                "card_id"      => "bn_count_Monitor",
                "card_options" => "{\"color\":\"#b52d30\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 14, "y" => 7, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Ticket_a74c0903-3387-4a07-9111-b0938af8f1e7",
                "card_id"      => "bn_count_Ticket",
                "card_options" => "{\"color\":\"#ffdc64\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 20, "y" => 7, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Problem_c1cf5cfb-f626-472e-82a1-49c3e200e746",
                "card_id"      => "bn_count_Problem",
                "card_options" => "{\"color\":\"#f08d7b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 4, "width" => 5, "height" => 4,
                "gridstack_id" => "count_Computer_Manufacturer_6129c451-42b5-489d-b693-c362adf32d49",
                "card_id"      => "count_Computer_Manufacturer",
                "card_options" => "{\"color\":\"#f8faf9\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ], [
                "x" => 14, "y" => 9, "width" => 6, "height" => 5,
                "gridstack_id" => "top_ticket_user_requester_c74f52a8-046a-4077-b1a6-c9f840d34b82",
                "card_id"      => "top_ticket_user_requester",
                "card_options" => "{\"color\":\"#f9fafb\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ], [
                "x" => 17, "y" => 7, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_late_04c47208-d7e5-4aca-9566-d46e68c45c67",
                "card_id"      => "bn_count_tickets_late",
                "card_options" => "{\"color\":\"#f8911f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 14, "y" => 0, "width" => 12, "height" => 7,
                "gridstack_id" => "ticket_status_2e4e968b-d4e6-4e33-9ce9-a1aaff53dfde",
                "card_id"      => "ticket_status",
                "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"stackedbars\",\"use_gradient\":\"0\",\"limit\":\"12\"}",
            ], [
                "x" => 20, "y" => 9, "width" => 6, "height" => 5,
                "gridstack_id" => "top_ticket_ITILCategory_37736ba9-d429-4cb3-9058-ef4d111d9269",
                "card_id"      => "top_ticket_ITILCategory",
                "card_options" => "{\"color\":\"#fbf9f9\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ], [
                "x" => 9, "y" => 2, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Printer_517684b0-b064-49dd-943e-fcb6f915e453",
                "card_id"      => "bn_count_Printer",
                "card_options" => "{\"color\":\"#365a8f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 9, "y" => 0, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Phone_f70c489f-02c1-46e5-978b-94a95b5038ee",
                "card_id"      => "bn_count_Phone",
                "card_options" => "{\"color\":\"#d5e1ec\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 23, "y" => 7, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Change_ab950dbd-cd25-466d-8dff-7dcaca386564",
                "card_id"      => "bn_count_Change",
                "card_options" => "{\"color\":\"#cae3c4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 4, "y" => 8, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_Group_b84a93f2-a26c-49d7-82a4-5446697cc5b0",
                "card_id"      => "bn_count_Group",
                "card_options" => "{\"color\":\"#e0e0e0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 4, "y" => 10, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_Profile_770b35e8-68e9-4b4f-9e09-5a11058f069f",
                "card_id"      => "bn_count_Profile",
                "card_options" => "{\"color\":\"#e0e0e0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 8, "y" => 8, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Supplier_36ff9011-e4cf-4d89-b9ab-346b9857d734",
                "card_id"      => "bn_count_Supplier",
                "card_options" => "{\"color\":\"#c9c9c9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 8, "y" => 10, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_KnowbaseItem_a3785a56-bed4-4a30-8387-f251f5365b3b",
                "card_id"      => "bn_count_KnowbaseItem",
                "card_options" => "{\"color\":\"#c9c9c9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 10, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_Entity_9b82951a-ba52-45cc-a2d3-1d238ec37adf",
                "card_id"      => "bn_count_Entity",
                "card_options" => "{\"color\":\"#f9f9f9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 11, "y" => 8, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Document_7dc7f4b8-61ff-4147-b994-5541bddd7b66",
                "card_id"      => "bn_count_Document",
                "card_options" => "{\"color\":\"#b4b4b4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 11, "y" => 10, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Project_4d412ee2-8b79-469b-995f-4c0a05ab849d",
                "card_id"      => "bn_count_Project",
                "card_options" => "{\"color\":\"#b3b3b3\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 6, "y" => 0, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_NetworkEquipment_c537e334-d584-43bc-b6de-b4a939143e89",
                "card_id"      => "bn_count_NetworkEquipment",
                "card_options" => "{\"color\":\"#bfe7ea\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 8, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_User_ac0cbe52-3593-43c1-8ecc-0eb115de494d",
                "card_id"      => "bn_count_User",
                "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 5, "y" => 4, "width" => 5, "height" => 4,
                "gridstack_id" => "count_Monitor_MonitorModel_5a476ff9-116e-4270-858b-c003c20841a9",
                "card_id"      => "count_Monitor_MonitorModel",
                "card_options" => "{\"color\":\"#f5fafa\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ], [
                "x" => 10, "y" => 4, "width" => 4, "height" => 4,
                "gridstack_id" => "count_NetworkEquipment_State_81f2ae35-b366-4065-ac26-02ea4e3704a6",
                "card_id"      => "count_NetworkEquipment_State",
                "card_options" => "{\"color\":\"#f5f3ef\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ]
        ]
    ], [
        'key'     => 'assets',
        'name'    => __("Assets"),
        'context' => 'core',
        '_items'  => [
            [
                "x" => 0, "y" => 0, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_Computer_34cfbaf9-a471-4852-b48c-0dadea7644de",
                "card_id"      => "bn_count_Computer",
                "card_options" => "{\"color\":\"#f3d0d0\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 4, "y" => 0, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_Software_60091467-2137-49f4-8834-f6602a482079",
                "card_id"      => "bn_count_Software",
                "card_options" => "{\"color\":\"#d1f1a8\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 8, "y" => 3, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_Printer_c9a385d4-76a3-4971-ad0e-1470efeafacc",
                "card_id"      => "bn_count_Printer",
                "card_options" => "{\"color\":\"#5da8d6\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 12, "y" => 3, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_PDU_60053eb6-8dda-4416-9a4b-afd51889bd09",
                "card_id"      => "bn_count_PDU",
                "card_options" => "{\"color\":\"#ffb62f\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 12, "y" => 0, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_Rack_0fdc196f-20d2-4f63-9ddb-b75c165cc664",
                "card_id"      => "bn_count_Rack",
                "card_options" => "{\"color\":\"#f7d79a\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 16, "y" => 3, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_Phone_c31fde2d-510a-4482-b17d-2f65b61eae08",
                "card_id"      => "bn_count_Phone",
                "card_options" => "{\"color\":\"#a0cec2\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 16, "y" => 0, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_Enclosure_c21ce30a-58c3-456a-81ec-3c5f01527a8f",
                "card_id"      => "bn_count_Enclosure",
                "card_options" => "{\"color\":\"#d7e8e4\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 8, "y" => 0, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_NetworkEquipment_76f1e239-777b-4552-b053-ae5c64190347",
                "card_id"      => "bn_count_NetworkEquipment",
                "card_options" => "{\"color\":\"#c8dae4\",\"widgettype\":\"bigNumber\"}",
            ], [
                "x" => 4, "y" => 3, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_SoftwareLicense_576e58fe-a386-480f-b405-1c2315b8ab47",
                "card_id"      => "bn_count_SoftwareLicense",
                "card_options" => "{\"color\":\"#9bc06b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 3, "width" => 4, "height" => 3,
                "gridstack_id" => "bn_count_Monitor_890e16d3-b121-48c6-9713-d9c239d9a970",
                "card_id"      => "bn_count_Monitor",
                "card_options" => "{\"color\":\"#dc6f6f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 4, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "count_Computer_Manufacturer_986e92e8-32e8-4a6f-806f-6f5383acbb3f",
                "card_id"      => "count_Computer_Manufacturer",
                "card_options" => "{\"color\":\"#f3f5f1\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ], [
                "x" => 0, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "count_Computer_State_290c5920-9eab-4db8-8753-46108e60f1d8",
                "card_id"      => "count_Computer_State",
                "card_options" => "{\"color\":\"#fbf7f7\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ], [
                "x" => 8, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "count_Computer_ComputerType_c58f9c7e-22d5-478b-8226-d2a752bcbb09",
                "card_id"      => "count_Computer_ComputerType",
                "card_options" => "{\"color\":\"#f5f9fa\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ], [
                "x" => 12, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "count_NetworkEquipment_Manufacturer_8132b21c-6f7f-4dc1-af54-bea794cb96e9",
                "card_id"      => "count_NetworkEquipment_Manufacturer",
                "card_options" => "{\"color\":\"#fcf8ed\",\"widgettype\":\"hbar\",\"use_gradient\":\"0\",\"limit\":\"5\"}",
            ], [
                "x" => 16, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "count_Monitor_Manufacturer_43b0c16b-af82-418e-aac1-f32b39705c0d",
                "card_id"      => "count_Monitor_Manufacturer",
                "card_options" => "{\"color\":\"#f9fbfb\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
            ]
        ]
    ], [
        'key'     => 'assistance',
        'name'    => __("Assistance"),
        'context' => 'core',
        '_items'  => [
            [
                "x" => 0, "y" => 0, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Ticket_344e761b-f7e8-4617-8c90-154b266b4d67",
                "card_id"      => "bn_count_Ticket",
                "card_options" => "{\"color\":\"#ffdc64\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 4, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Problem_bdb4002b-a674-4493-820f-af85bed44d2a",
                "card_id"      => "bn_count_Problem",
                "card_options" => "{\"color\":\"#f0967b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 6, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_Change_b9b87513-4f40-41e6-8621-f51f9a30fb19",
                "card_id"      => "bn_count_Change",
                "card_options" => "{\"color\":\"#cae3c4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 2, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_late_1e9ae481-21b4-4463-a830-dec1b68ec5e7",
                "card_id"      => "bn_count_tickets_late",
                "card_options" => "{\"color\":\"#f8911f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 3, "y" => 6, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_incoming_336a36d9-67fe-4475-880e-447bd766b8fe",
                "card_id"      => "bn_count_tickets_incoming",
                "card_options" => "{\"color\":\"#a0e19d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 9, "y" => 8, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_closed_e004bab5-f2b6-4060-a401-a2a8b9885245",
                "card_id"      => "bn_count_tickets_closed",
                "card_options" => "{\"color\":\"#515151\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 6, "y" => 6, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_assigned_7455c855-6df8-4514-a3d9-8b0fce52bd63",
                "card_id"      => "bn_count_tickets_assigned",
                "card_options" => "{\"color\":\"#eaf5f7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 9, "y" => 6, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_solved_5e9759b3-ee7e-4a14-b68f-1ac024ef55ee",
                "card_id"      => "bn_count_tickets_solved",
                "card_options" => "{\"color\":\"#d8d8d8\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 3, "y" => 8, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_waiting_102b2c2a-6ac6-4d73-ba47-8b09382fe00e",
                "card_id"      => "bn_count_tickets_waiting",
                "card_options" => "{\"color\":\"#ffcb7d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 8, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_TicketRecurrent_13f79539-61f6-45f7-8dde-045706e652f2",
                "card_id"      => "bn_count_TicketRecurrent",
                "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 6, "y" => 8, "width" => 3, "height" => 2,
                "gridstack_id" => "bn_count_tickets_planned_267bf627-9d5e-4b6c-b53d-b8623d793ccf",
                "card_id"      => "bn_count_tickets_planned",
                "card_options" => "{\"color\":\"#6298d5\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 12, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "top_ticket_ITILCategory_0cba0c84-6c62-4cd8-8564-18614498d8e4",
                "card_id"      => "top_ticket_ITILCategory",
                "card_options" => "{\"color\":\"#f1f5ef\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"7\"}",
            ], [
                "x" => 16, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "top_ticket_RequestType_b9e43f34-8e94-4a6e-9023-c5d1e2ce7859",
                "card_id"      => "top_ticket_RequestType",
                "card_options" => "{\"color\":\"#f9fafb\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"4\"}",
            ], [
                "x" => 20, "y" => 6, "width" => 4, "height" => 4,
                "gridstack_id" => "top_ticket_Entity_a8e65812-519c-488e-9892-9adbe22fbd5c",
                "card_id"      => "top_ticket_Entity",
                "card_options" => "{\"color\":\"#f7f1f0\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"7\"}",
            ], [
                "x" => 3, "y" => 0, "width" => 12, "height" => 6,
                "gridstack_id" => "ticket_evolution_76fd4926-ee5e-48db-b6d6-e2947c190c5e",
                "card_id"      => "ticket_evolution",
                "card_options" => "{\"color\":\"#f3f7f8\",\"widgettype\":\"areas\",\"use_gradient\":\"0\",\"limit\":\"12\"}",
            ], [
                "x" => 15, "y" => 0, "width" => 11, "height" => 6,
                "gridstack_id" => "ticket_status_5b256a35-b36b-4db5-ba11-ea7c125f126e",
                "card_id"      => "ticket_status",
                "card_options" => "{\"color\":\"#f7f3f2\",\"widgettype\":\"stackedbars\",\"use_gradient\":\"0\",\"limit\":\"12\"}",
            ]
        ]
    ], [
        'key'     => 'mini_tickets',
        'name'    => __("Mini tickets dashboard"),
        'context' => 'mini_core',
        '_items'  => [
            [
                "x" => 24, "y" => 0, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_tickets_closed_ccf7246b-645a-40d2-8206-fa33c769e3f5",
                "card_id"      => "bn_count_tickets_closed",
                "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 0, "y" => 0, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_Ticket_d5bf3576-5033-40fb-bbdb-292294a7698e",
                "card_id"      => "bn_count_Ticket",
                "card_options" => "{\"color\":\"#ffd957\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 4, "y" => 0, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_tickets_incoming_055e813c-b0ce-4687-91ef-559249e8ddd8",
                "card_id"      => "bn_count_tickets_incoming",
                "card_options" => "{\"color\":\"#6fd169\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 8, "y" => 0, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_tickets_waiting_793c665b-b620-4b3a-a5a8-cf502defc008",
                "card_id"      => "bn_count_tickets_waiting",
                "card_options" => "{\"color\":\"#ffcb7d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 12, "y" => 0, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_tickets_assigned_d3d2f697-52b4-435e-9030-a760dd649085",
                "card_id"      => "bn_count_tickets_assigned",
                "card_options" => "{\"color\":\"#eaf4f7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 16, "y" => 0, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_tickets_planned_0c7f3569-c23b-4ee3-8e85-279229b23e70",
                "card_id"      => "bn_count_tickets_planned",
                "card_options" => "{\"color\":\"#6298d5\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ], [
                "x" => 20, "y" => 0, "width" => 4, "height" => 2,
                "gridstack_id" => "bn_count_tickets_solved_ae2406cf-e8e8-410b-b355-46e3f5705ee8",
                "card_id"      => "bn_count_tickets_solved",
                "card_options" => "{\"color\":\"#d7d7d7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
            ]
        ]
    ]
];
