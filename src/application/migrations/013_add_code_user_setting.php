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

class Migration_Add_code_user_setting extends CI_Migration {
    public function up()
    {
        if ( ! $this->db->field_exists('code', 'ea_user_settings'))
        {
            $fields = [
                'code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '32',
                    'default'    => null,
                    null         => 'TRUE'
                ]
            ];

            $this->dbforge->add_column('ea_user_settings', $fields);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('code', 'ea_user_settings'))
        {
            $this->dbforge->drop_column('ea_user_settings', 'code');
        }
    }
}
