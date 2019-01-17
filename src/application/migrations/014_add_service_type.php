<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

class Migration_Add_service_type extends CI_Migration {
    public function up()
    {
        if ( ! $this->db->field_exists('type', 'ea_services'))
        {
            $fields = [
                'type' => [
                    'type'       => 'ENUM'
                ]
            ];

            $this->dbforge->add_column('ea_services', $fields);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('type', 'ea_services'))
        {
            $this->dbforge->drop_column('ea_services', 'type');
        }
    }
}
