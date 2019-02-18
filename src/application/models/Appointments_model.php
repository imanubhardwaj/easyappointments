<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Appointments Model
 *
 * @package Models
 */
class Appointments_Model extends CI_Model {
    /**
     * Add an appointment record to the database.
     *
     * This method adds a new appointment to the database. If the appointment doesn't exists it is going to be inserted,
     * otherwise the record is going to be updated.
     *
     * @param array $appointment Associative array with the appointment data. Each key has the same name with the
     * database fields.
     *
     * @return int Returns the appointments id.
     */
    public function add($appointment)
    {
        // Validate the appointment data before doing anything.
        $this->validate($appointment);

        // Perform insert() or update() operation.
        if ( ! isset($appointment['id']))
        {
            $appointment['id'] = $this->_insert($appointment);
        }
        else
        {
            $this->_update($appointment);
        }

        return $appointment['id'];
    }

    /**
     * Check if a particular appointment record already exists.
     *
     * This method checks whether the given appointment already exists in the database. It doesn't search with the id,
     * but by using the following fields: "start_datetime", "end_datetime", "id_users_provider", "id_users_customer",
     * "id_services".
     *
     * @param array $appointment Associative array with the appointment's data. Each key has the same name with the
     * database fields.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception If appointment fields are missing.
     */
    public function exists($appointment)
    {
        if ( ! isset($appointment['start_datetime'])
            || ! isset($appointment['end_datetime'])
            || ! isset($appointment['id_users_provider'])
            || ! isset($appointment['id_users_customer'])
            || ! isset($appointment['id_services']))
        {
            throw new Exception('Not all appointment field values are provided: '
                . print_r($appointment, TRUE));
        }

        $num_rows = $this->db->get_where('ea_appointments', [
            'start_datetime' => $appointment['start_datetime'],
            'end_datetime' => $appointment['end_datetime'],
            'id_users_provider' => $appointment['id_users_provider'],
            'id_users_customer' => $appointment['id_users_customer'],
            'id_services' => $appointment['id_services'],
        ])
            ->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Insert a new appointment record to the database.
     *
     * @param array $appointment Associative array with the appointment's data. Each key has the same name with the
     * database fields.
     *
     * @return int Returns the id of the new record.
     *
     * @throws Exception If appointment record could not be inserted.
     */
    protected function _insert($appointment)
    {
        $appointment['book_datetime'] = date('Y-m-d H:i:s');
        $appointment['hash'] = $this->generate_hash();

        if ( ! $this->db->insert('ea_appointments', $appointment))
        {
            throw new Exception('Could not insert appointment record.');
        }

        return (int)$this->db->insert_id();
    }

    /**
     * Update an existing appointment record in the database.
     *
     * The appointment data argument should already include the record ID in order to process the update operation.
     *
     * @param array $appointment Associative array with the appointment's data. Each key has the same name with the
     * database fields.
     *
     * @throws Exception If appointment record could not be updated.
     */
    protected function _update($appointment)
    {
        $this->db->where('id', $appointment['id']);
        if ( ! $this->db->update('ea_appointments', $appointment))
        {
            throw new Exception('Could not update appointment record.');
        }
    }

    /**
     * Find the database id of an appointment record.
     *
     * The appointment data should include the following fields in order to get the unique id from the database:
     * "start_datetime", "end_datetime", "id_users_provider", "id_users_customer", "id_services".
     *
     * IMPORTANT: The record must already exists in the database, otherwise an exception is raised.
     *
     * @param array $appointment Array with the appointment data. The keys of the array should have the same names as
     * the db fields.
     *
     * @return int Returns the db id of the record that matches the appointment data.
     *
     * @throws Exception If appointment could not be found.
     */
    public function find_record_id($appointment)
    {
        $this->db->where([
            'start_datetime' => $appointment['start_datetime'],
            'end_datetime' => $appointment['end_datetime'],
            'id_users_provider' => $appointment['id_users_provider'],
            'id_users_customer' => $appointment['id_users_customer'],
            'id_services' => $appointment['id_services']
        ]);

        $result = $this->db->get('ea_appointments');

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find appointment record id.');
        }

        return $result->row()->id;
    }

    /**
     * Validate appointment data before the insert or update operations are executed.
     *
     * @param array $appointment Contains the appointment data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If appointment validation fails.
     */
    public function validate($appointment)
    {
        $this->load->helper('data_validation');

        // If a appointment id is given, check whether the record exists
        // in the database.
        if (isset($appointment['id']))
        {
            $num_rows = $this->db->get_where('ea_appointments',
                ['id' => $appointment['id']])->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Provided appointment id does not exist in the database.');
            }
        }

        // Check if appointment dates are valid.
        if ( ! validate_mysql_datetime($appointment['start_datetime']))
        {
            throw new Exception('Appointment start datetime is invalid.');
        }

        if ( ! validate_mysql_datetime($appointment['end_datetime']))
        {
            throw new Exception('Appointment end datetime is invalid.');
        }

        // Check if the provider's id is valid.
        $num_rows = $this->db
            ->select('*')
            ->from('ea_users')
            ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
            ->where('ea_users.id', $appointment['id_users_provider'])
            ->where('ea_roles.slug', DB_SLUG_PROVIDER)
            ->get()->num_rows();
        if ($num_rows == 0)
        {
            throw new Exception('Appointment provider id is invalid.');
        }

        if ($appointment['is_unavailable'] == FALSE)
        {
            // Check if the customer's id is valid.
            $num_rows = $this->db
                ->select('*')
                ->from('ea_users')
                ->join('ea_roles', 'ea_roles.id = ea_users.id_roles', 'inner')
                ->where('ea_users.id', $appointment['id_users_customer'])
                ->where('ea_roles.slug', DB_SLUG_CUSTOMER)
                ->get()->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Appointment customer id is invalid.');
            }

            // Check if the service id is valid.
            $num_rows = $this->db->get_where('ea_services',
                ['id' => $appointment['id_services']])->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Appointment service id is invalid.');
            }
        }

        return TRUE;
    }

    /**
     * Delete an existing appointment record from the database.
     *
     * @param int $appointment_id The record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $appointment_id argument is invalid.
     */
    public function delete($appointment_id)
    {
        if ( ! is_numeric($appointment_id))
        {
            throw new Exception('Invalid argument type $appointment_id (value:"' . $appointment_id . '")');
        }

        $num_rows = $this->db->get_where('ea_appointments', ['id' => $appointment_id])->num_rows();

        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist.
        }

        $this->db->where('id', $appointment_id);
        return $this->db->delete('ea_appointments');
    }

    /**
     * Get a specific row from the appointments table.
     *
     * @param int $appointment_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $appointment_id argumnet is invalid.
     */
    public function get_row($appointment_id)
    {
        if ( ! is_numeric($appointment_id))
        {
            throw new Exception('Invalid argument given. Expected integer for the $appointment_id: '
                . $appointment_id);
        }

        return $this->db->get_where('ea_appointments', ['id' => $appointment_id])->row_array();
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $appointment_id The selected record's id.
     *
     * @return string Returns the records value from the database.
     *
     * @throws Exception If $appointment_id argument is invalid.
     * @throws Exception If $field_name argument is invalid.
     * @throws Exception If requested appointment record was not found.
     * @throws Exception If requested field name does not exist.
     */
    public function get_value($field_name, $appointment_id)
    {
        if ( ! is_numeric($appointment_id))
        {
            throw new Exception('Invalid argument given, expected integer for the $appointment_id: '
                . $appointment_id);
        }

        if ( ! is_string($field_name))
        {
            throw new Exception('Invalid argument given, expected  string for the $field_name: ' . $field_name);
        }

        if ($this->db->get_where('ea_appointments', ['id' => $appointment_id])->num_rows() == 0)
        {
            throw new Exception('The record with the provided id '
                . 'does not exist in the database: ' . $appointment_id);
        }

        $row_data = $this->db->get_where('ea_appointments', ['id' => $appointment_id])->row_array();

        if ( ! isset($row_data[$field_name]))
        {
            throw new Exception('The given field name does not exist in the database: ' . $field_name);
        }

        return $row_data[$field_name];
    }

    /**
     * Get all, or specific records from appointment's table.
     *
     * @example $this->Model->getBatch('id = ' . $recordId);
     *
     * @param string $where_clause (OPTIONAL) The WHERE clause of the query to be executed. DO NOT INCLUDE 'WHERE'
     * KEYWORD.
     *
     * @param bool $aggregates (OPTIONAL) Defines whether to add aggregations or not.
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch($where_clause = '', $aggregates = FALSE)
    {
        if ($where_clause != '')
        {
            $this->db->where($where_clause);
        }

        $appointments = $this->db->get('ea_appointments')->result_array();

        if ($aggregates)
        {
            foreach ($appointments as &$appointment)
            {
                $appointment = $this->get_aggregates($appointment);
            }
        }

        return $appointments;
    }

    /**
     * Generate a unique hash for the given appointment data.
     *
     * This method uses the current date-time to generate a unique hash string that is later used to identify this
     * appointment. Hash is needed when the email is send to the user with an edit link.
     *
     * @return string Returns the unique appointment hash.
     */
    public function generate_hash()
    {
        $current_date = new DateTime();
        return md5($current_date->getTimestamp());
    }

    /**
     * Inserts or updates an unavailable period record in the database.
     *
     * @param array $unavailable Contains the unavailable data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception If unavailability validation fails.
     * @throws Exception If provider record could not be found in database.
     */
    public function add_unavailable($unavailable)
    {
        // Validate period
        $start = strtotime($unavailable['start_datetime']);
        $end = strtotime($unavailable['end_datetime']);
        if ($start > $end)
        {
            throw new Exception('Unavailable period start must be prior to end.');
        }

        // Validate provider record
        $where_clause = [
            'id' => $unavailable['id_users_provider'],
            'id_roles' => $this->db->get_where('ea_roles', ['slug' => DB_SLUG_PROVIDER])->row()->id
        ];

        if ($this->db->get_where('ea_users', $where_clause)->num_rows() == 0)
        {
            throw new Exception('Provider id was not found in database.');
        }

        // Add record to database (insert or update).
        if ( ! isset($unavailable['id']))
        {
            $unavailable['book_datetime'] = date('Y-m-d H:i:s');
            $unavailable['is_unavailable'] = TRUE;

            $this->db->insert('ea_appointments', $unavailable);
            $unavailable['id'] = $this->db->insert_id();
        }
        else
        {
            $this->db->where(['id' => $unavailable['id']]);
            $this->db->update('ea_appointments', $unavailable);
        }

        return $unavailable['id'];
    }

    /**
     * Delete an unavailable period.
     *
     * @param int $unavailable_id Record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $unavailable_id argument is invalid.
     */
    public function delete_unavailable($unavailable_id)
    {
        if ( ! is_numeric($unavailable_id))
        {
            throw new Exception('Invalid argument type $unavailable_id: ' . $unavailable_id);
        }

        $num_rows = $this->db->get_where('ea_appointments', ['id' => $unavailable_id])->num_rows();

        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist.
        }

        $this->db->where('id', $unavailable_id);

        return $this->db->delete('ea_appointments');
    }

    /**
     * Clear google sync IDs from appointment record.
     *
     * @param int $provider_id The appointment provider record id.
     *
     * @throws Exception If $provider_id argument is invalid.
     */
    public function clear_google_sync_ids($provider_id)
    {
        if ( ! is_numeric($provider_id))
        {
            throw new Exception('Invalid argument type $provider_id: ' . $provider_id);
        }

        $this->db->update('ea_appointments', ['id_google_calendar' => NULL],
            ['id_users_provider' => $provider_id]);
    }

    /**
     * Get appointment count for the provided start datetime.
     *
     * @param int $service_id Selected service ID.
     * @param string $selected_date Selected date string.
     * @param string $hour Selected hour string.
     *
     * @return int Returns the appointment number at the selected start time.
     */
    public function appointment_count_for_hour($service_id, $selected_date, $hour)
    {
        return $this->db->get_where('ea_appointments', [
            'id_services' => $service_id,
            'start_datetime' => date('Y-m-d H:i:s', strtotime($selected_date . ' ' . $hour . ':00'))
        ])->num_rows();
    }

    /**
     * Returns the attendants number for selection period.
     *
     * @param DateTime $slot_start When the slot starts
     * @param DateTime $slot_end When the slot ends.
     * @param int $service_id Selected service ID.
     *
     * @return int Returns the number of attendants for selected time period.
     */
    public function get_attendants_number_for_period(DateTime $slot_start, DateTime $slot_end, $service_id)
    {
        return (int)$this->db
            ->select('count(*) AS attendants_number')
            ->from('ea_appointments')
            ->group_start()
            ->where('start_datetime <=', $slot_start->format('Y-m-d H:i:s'))
            ->where('end_datetime >', $slot_start->format('Y-m-d H:i:s'))
            ->group_end()
            ->or_group_start()
            ->where('start_datetime <', $slot_end->format('Y-m-d H:i:s'))
            ->where('end_datetime >=', $slot_end->format('Y-m-d H:i:s'))
            ->group_end()
            ->where('id_services', $service_id)
            ->get()
            ->row()
            ->attendants_number;
    }

    /**
     * Get the aggregates of an appointment.
     *
     * @param array $appointment Appointment data.
     *
     * @return array Returns the appointment with the aggregates.
     */
    private function get_aggregates(array $appointment)
    {
        $appointment['service'] = $this->db->get_where('ea_services',
            ['id' => $appointment['id_services']])->row_array();
        $appointment['provider'] = $this->db->get_where('ea_users',
            ['id' => $appointment['id_users_provider']])->row_array();
        $appointment['customer'] = $this->db->get_where('ea_users',
            ['id' => $appointment['id_users_customer']])->row_array();
        return $appointment;
    }

    public function syncEvents($provider_id) {
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        $provider = $this->providers_model->get_row($provider_id);

        // Check whether the selected provider has google sync enabled.
        $google_sync = $this->providers_model->get_setting('google_sync', $provider['id']);

        if ( ! $google_sync)
        {
            throw new Exception('The selected provider has not the google synchronization setting enabled.');
        }

        $google_token = json_decode($this->providers_model->get_setting('google_token', $provider['id']));
        $this->load->library('google_sync');
        $this->google_sync->refresh_token($google_token->refresh_token);

        // Fetch provider's appointments that belong to the sync time period.
        $sync_past_days = $this->providers_model->get_setting('sync_past_days', $provider['id']);
        $sync_future_days = $this->providers_model->get_setting('sync_future_days', $provider['id']);
        $start = strtotime('-' . $sync_past_days . ' days', strtotime(date('Y-m-d')));
        $end = strtotime('+' . $sync_future_days . ' days', strtotime(date('Y-m-d')));

        $where_clause = [
            'start_datetime >=' => date('Y-m-d H:i:s', $start),
            'end_datetime <=' => date('Y-m-d H:i:s', $end),
            'id_users_provider' => $provider['id']
        ];

        $appointments = $this->appointments_model->get_batch($where_clause);

        $company_settings = [
            'company_name' => $this->settings_model->get_setting('company_name'),
            'company_link' => $this->settings_model->get_setting('company_link'),
            'company_email' => $this->settings_model->get_setting('company_email')
        ];

        // Sync each appointment with Google Calendar by following the project's sync protocol (see documentation).
        foreach ($appointments as $appointment)
        {
            if ($appointment['is_unavailable'] == FALSE)
            {
                $service = $this->services_model->get_row($appointment['id_services']);
                $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            }
            else
            {
                $service = NULL;
                $customer = NULL;
            }

            // If current appointment not synced yet, add to gcal.
            if ($appointment['id_google_calendar'] == NULL)
            {
                $google_event = $this->google_sync->add_appointment($appointment, $provider,
                    $service, $customer, $company_settings);
                $appointment['id_google_calendar'] = $google_event->id;
                $this->appointments_model->add($appointment); // Save gcal id
            }
            else
            {
                // Appointment is synced with google calendar.
                try
                {
                    $google_event = $this->google_sync->get_event($provider, $appointment['id_google_calendar']);

                    if ($google_event->status == 'cancelled')
                    {
                        throw new Exception('Event is cancelled, remove the record from Easy!Appointments.');
                    }

                    // If gcal event is different from e!a appointment then update e!a record.
                    $is_different = FALSE;
                    $appt_start = strtotime($appointment['start_datetime']);
                    $appt_end = strtotime($appointment['end_datetime']);
                    $event_start = strtotime($this->remove_time_offset($google_event->getStart()->getDateTime()));
                    $event_end = strtotime($this->remove_time_offset($google_event->getEnd()->getDateTime()));

                    if ($appt_start != $event_start || $appt_end != $event_end)
                    {
                        $is_different = TRUE;
                    }

                    if ($is_different)
                    {
                        $appointment['start_datetime'] = date('Y-m-d H:i:s', $event_start);
                        $appointment['end_datetime'] = date('Y-m-d H:i:s', $event_end);
                        $this->appointments_model->add($appointment);
                    }

                }
                catch (Exception $exc)
                {
                    // Appointment not found on gcal, delete from e!a.
                    $this->appointments_model->delete($appointment['id']);
                    $appointment['id_google_calendar'] = NULL;
                }
            }
        }

        // :: ADD GCAL EVENTS THAT ARE NOT PRESENT ON E!A
        $google_calendar = $provider['settings']['google_calendar'];
        $events = $this->google_sync->get_sync_events($google_calendar, $start, $end);

        foreach ($events->getItems() as $event)
        {
            $results = $this->appointments_model->get_batch(['id_google_calendar' => $event->getId()]);
            $start_time = $this->remove_time_offset($event->start->getDateTime());
            $end_time = $this->remove_time_offset($event->end->getDateTime());
            if (count($results) == 0)
            {
                // Record doesn't exist in E!A, so add the event now.
                $appointment = [
                    'start_datetime' => date('Y-m-d H:i:s', strtotime($start_time)),
                    'end_datetime' => date('Y-m-d H:i:s', strtotime($end_time)),
                    'is_unavailable' => TRUE,
                    'notes' => $event->getSummary() . ' ' . $event->getDescription(),
                    'id_users_provider' => $provider_id,
                    'id_google_calendar' => $event->getId(),
                    'id_users_customer' => NULL,
                    'id_services' => NULL,
                ];
                $this->appointments_model->add($appointment);
            }
        }
    }

    function remove_time_offset($time) {
        $offset = substr($time, -5);
        return substr($time, -6, 1) === '-' ? $this->addTime($time, $offset) : $this->diff($time, $offset);
    }

    public function addTime($event_time, $time_offset) {
        $t1 = new DateTime($event_time);
        $t2 = new DateTime($time_offset);
        $start_of_time = new DateTime('00:00:00');
        $diff = $start_of_time->diff($t2) ;
        return substr($t1->add($diff)->format(DATE_ATOM), 0, -6);
    }

    public function diff($event_time, $time_offset) {
        $t1 = new DateTime($event_time);
        $t2 = new DateTime($time_offset);
        $start_of_time = new DateTime('00:00:00');
        $diff = $t2->diff($start_of_time) ;
        return substr($t1->add($diff)->format(DATE_ATOM), 0, -6);
    }
}
