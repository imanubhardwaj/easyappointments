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

use \EA\Engine\Types\Text;
use \EA\Engine\Types\Email;
use \EA\Engine\Types\Url;

/**
 * Appointments Controller
 *
 * @package Controllers
 */
class Appointments extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper('installation');

        // Set user's selected language.
        if ($this->session->userdata('language'))
        {
            $this->config->set_item('language', $this->session->userdata('language'));
            $this->lang->load('translations', $this->session->userdata('language'));
        }
        else
        {
            $this->lang->load('translations', $this->config->item('language')); // default
        }

        // Common helpers
        $this->load->helper('google_analytics');
    }

    /**
     * Default callback method of the application.
     *
     * This method creates the appointment book wizard. If an appointment hash
     * is provided then it means that the customer followed the appointment
     * manage link that was send with the book success email.
     *
     * @param string $appointment_hash DB appointment hash of an existing record (default '').
     */
    public function index($appointment_hash = '')
    {
        if ( ! is_ea_installed())
        {
            redirect('installation/index');
            return;
        }

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        try
        {
            $available_services = $this->services_model->get_available_services();
            $available_providers = $this->providers_model->get_available_providers();
            $company_name = $this->settings_model->get_setting('company_name');
            $date_format = $this->settings_model->get_setting('date_format');
            $time_format = $this->settings_model->get_setting('time_format');
            $display_cookie_notice = $this->settings_model->get_setting('display_cookie_notice');
            $cookie_notice_content = $this->settings_model->get_setting('cookie_notice_content');
            $display_terms_and_conditions = $this->settings_model->get_setting('display_terms_and_conditions');
            $terms_and_conditions_content = $this->settings_model->get_setting('terms_and_conditions_content');
            $display_privacy_policy = $this->settings_model->get_setting('display_privacy_policy');
            $privacy_policy_content = $this->settings_model->get_setting('privacy_policy_content');

            // Remove the data that are not needed inside the $available_providers array.
            foreach ($available_providers as $index => $provider)
            {
                $stripped_data = [
                    'id' => $provider['id'],
                    'first_name' => $provider['first_name'],
                    'last_name' => $provider['last_name'],
                    'services' => $provider['services'],
                    'timezone' => $provider['settings']['timezone']
                ];
                $available_providers[$index] = $stripped_data;
            }

            // If an appointment hash is provided then it means that the customer
            // is trying to edit a registered appointment record.
            if ($appointment_hash !== '')
            {
                // Load the appointments data and enable the manage mode of the page.
                $manage_mode = TRUE;

                $results = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

                if (count($results) === 0)
                {
                    // The requested appointment doesn't exist in the database. Display
                    // a message to the customer.
                    $view = [
                        'message_title' => $this->lang->line('appointment_not_found'),
                        'message_text' => $this->lang->line('appointment_does_not_exist_in_db'),
                        'message_icon' => base_url('assets/img/error.png')
                    ];
                    $this->load->view('appointments/message', $view);
                    return;
                }

                $appointment = $results[0];
                $provider = $this->providers_model->get_row($appointment['id_users_provider']);
                $customer = $this->customers_model->get_row($appointment['id_users_customer']);

                $customer_token = md5(uniqid(mt_rand(), true));

                $this->load->driver('cache', ['adapter' => 'file']);

                $this->cache->save('customer-token-' . $customer_token, $customer['id'], 600); // save for 10 minutes
            }
            else
            {
                // The customer is going to book a new appointment so there is no
                // need for the manage functionality to be initialized.
                $manage_mode = FALSE;
                $customer_token = FALSE;
                $appointment = [];
                $provider = [];
                $customer = [];
            }

            // Load the book appointment view.
            $view = [
                'available_services' => $available_services,
                'available_providers' => $available_providers,
                'company_name' => $company_name,
                'manage_mode' => $manage_mode,
                'customer_token' => $customer_token,
                'date_format' => $date_format,
                'time_format' => $time_format,
                'appointment_data' => $appointment,
                'provider_data' => $provider,
                'customer_data' => $customer,
                'display_cookie_notice' => $display_cookie_notice,
                'cookie_notice_content' => $cookie_notice_content,
                'display_terms_and_conditions' => $display_terms_and_conditions,
                'terms_and_conditions_content' => $terms_and_conditions_content,
                'display_privacy_policy' => $display_privacy_policy,
                'privacy_policy_content' => $privacy_policy_content,
            ];
        }
        catch (Exception $exc)
        {
            $view['exceptions'][] = $exc;
        }

        $this->load->view('appointments/book', $view);
    }

    /**
     * Cancel an existing appointment.
     *
     * This method removes an appointment from the company's schedule. In order for the appointment to be deleted, the
     * hash string must be provided. The customer can only cancel the appointment if the edit time period is not over
     * yet. Provide the $_POST['cancel_reason'] parameter to describe the cancellation reason.
     *
     * @param string $appointment_hash This is used to distinguish the appointment record.
     */
    public function cancel($appointment_hash)
    {
        try
        {
            $this->load->model('appointments_model');
            $this->load->model('providers_model');
            $this->load->model('customers_model');
            $this->load->model('services_model');
            $this->load->model('settings_model');

            // Check whether the appointment hash exists in the database.
            $records = $this->appointments_model->get_batch(['hash' => $appointment_hash]);
            if (count($records) == 0)
            {
                throw new Exception('No record matches the provided hash.');
            }

            $appointment = $records[0];
            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $company_settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            // :: DELETE APPOINTMENT RECORD FROM THE DATABASE.
            if ( ! $this->appointments_model->delete($appointment['id']))
            {
                throw new Exception('Appointment could not be deleted from the database.');
            }

            // :: SYNC APPOINTMENT REMOVAL WITH GOOGLE CALENDAR
            if ($appointment['id_google_calendar'] != NULL)
            {
                try
                {
                    $google_sync = filter_var($this->providers_model
                        ->get_setting('google_sync', $appointment['id_users_provider']), FILTER_VALIDATE_BOOLEAN);

                    if ($google_sync == TRUE)
                    {
                        $google_token = json_decode($this->providers_model
                            ->get_setting('google_token', $provider['id']));
                        $this->load->library('Google_sync');
                        $this->google_sync->refresh_token($google_token->refresh_token);
                        $this->google_sync->delete_appointment($provider, $appointment['id_google_calendar']);
                    }
                }
                catch (Exception $exc)
                {
                    $exceptions[] = $exc;
                }
            }

            // :: SEND NOTIFICATION EMAILS TO CUSTOMER AND PROVIDER
            try
            {
                $this->config->load('email');
                $email = new \EA\Engine\Notifications\Email($this, $this->config->config);

                $send_provider = filter_var($this->providers_model
                    ->get_setting('notifications', $provider['id']), FILTER_VALIDATE_BOOLEAN);

//                if ($send_provider === TRUE)
//                {
                $email->sendDeleteAppointment($appointment, $provider,
                    $service, $customer, $company_settings, new Email($provider['email']),
                    new Text($this->input->post('cancel_reason')), false);
//                }

                $send_customer = filter_var($this->settings_model->get_setting('customer_notifications'),
                    FILTER_VALIDATE_BOOLEAN);

//                if ($send_customer === TRUE)
//                {
                $email->sendDeleteAppointment($appointment, $provider,
                    $service, $customer, $company_settings, new Email($customer['email']),
                    new Text($this->input->post('cancel_reason')), true);
//                }

            }
            catch (Exception $exc)
            {
                $exceptions[] = $exc;
            }
        }
        catch (Exception $exc)
        {
            // Display the error message to the customer.
            $exceptions[] = $exc;
        }

        $view = [
            'message_title' => $this->lang->line('appointment_cancelled_title'),
            'message_text' => $this->lang->line('appointment_cancelled'),
            'message_icon' => base_url('assets/img/success.png')
        ];

        if (isset($exceptions))
        {
            $view['exceptions'] = $exceptions;
        }

        $this->load->view('appointments/message', $view);
    }

    /**
     * GET an specific appointment book and redirect to the success screen.
     *
     * @param int $appointment_id Contains the ID of the appointment to retrieve.
     */
    public function book_success($appointment_id)
    {
        // If the appointment id doesn't exist or zero redirect to index.
        if ( ! $appointment_id)
        {
            redirect('appointments');
        }
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        //retrieve the data needed in the view
        $appointment = $this->appointments_model->get_row($appointment_id);
        $provider = $this->providers_model->get_row($appointment['id_users_provider']);
        $service = $this->services_model->get_row($appointment['id_services']);
        $company_name = $this->settings_model->get_setting('company_name');
        //get the exceptions
        $exceptions = $this->session->flashdata('book_success');
        // :: LOAD THE BOOK SUCCESS VIEW
        $view = [
            'appointment_data' => $appointment,
            'provider_data' => $provider,
            'service_data' => $service,
            'company_name' => $company_name,
        ];
        if ($exceptions)
        {
            $view['exceptions'] = $exceptions;
        }
        $this->load->view('appointments/book_success', $view);
    }

    /**
     * [AJAX] Get the available appointment hours for the given date.
     *
     * This method answers to an AJAX request. It calculates the available hours for the given service, provider and
     * date.
     *
     * Required POST parameters:
     *
     * - int $_POST['service_id'] Selected service record ID.
     * - int|string $_POST['provider_id'] Selected provider record id, can also be 'any-provider'.
     * - string $_POST['selected_date'] Selected date for availabilities.
     * - int $_POST['service_duration'] Selected service duration in minutes.
     * - string $_POST['manage_mode'] Contains either 'true' or 'false' and determines the if current user
     * is managing an already booked appointment or not.
     *
     * Outputs a JSON string with the availabilities.
     *
     * @deprecated Since v1.3.0, this method will be replaced with a future release.
     */
    public function ajax_get_available_hours()
    {
        $currentDay = $this->input->post('selected_date');
        if($currentDay) {
            try {
                $nextDay     = (new DateTime($currentDay . ' +1 day'))->format('Y-m-d');
                $previousDay = (new DateTime($currentDay . ' -1 day'))->format('Y-m-d');

            } catch (Exception $e) {
            }
        }
        try
        {
            $previous_day_hours = $this->get_available_hours_for_day($previousDay, $this->input->post('provider_id'),
                $this->input->post('manage_mode'), $this->input->post('appointment_id'),
                $this->input->post('service_id'), $this->input->post('service_duration'));

            $available_hours = $this->get_available_hours_for_day($currentDay, $this->input->post('provider_id'),
                $this->input->post('manage_mode'), $this->input->post('appointment_id'),
                $this->input->post('service_id'), $this->input->post('service_duration'));

            $next_day_hours = $this->get_available_hours_for_day($nextDay, $this->input->post('provider_id'),
                $this->input->post('manage_mode'), $this->input->post('appointment_id'),
                $this->input->post('service_id'), $this->input->post('service_duration'));

            $hours = $this->process_hours($previousDay, $previous_day_hours, $currentDay, $available_hours,
                $nextDay, $next_day_hours, $this->input->post('timezone'), (new DateTime($currentDay))->format('d'));

            $available_hours = [];
            foreach ($hours as $hour) {
                if(new DateTime($currentDay.$hour.':00') > new DateTime($this->input->post('time'))) {
                    array_push($available_hours, $hour);
                }
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($available_hours));
        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'exceptions' => [exceptionToJavaScript($exc)]
                ]));
        }
    }

    public function ajax_get_available_slots() {
        $this->load->model('services_model');

        $providerId = $this->input->post('provider_id');
        $serviceId = $this->input->post('service_id');
        $userTimezone = $this->input->post('timezone');

        $selectedDate = $this->input->post('selected_date');
        $service = $this->services_model->get_row($serviceId);
        if($selectedDate) {
            try {
                $free_periods = $this->_get_available_periods($providerId, $selectedDate);
                $available_hours = [];

                foreach ($free_periods as $period)
                {
                    $start_hour = new DateTime(date('Y-m-d H:i:s', $period['start']));
                    $end_hour = new DateTime(date('Y-m-d H:i:s', $period['end']));
                    $interval = 5;

                    $current_hour = $start_hour;
                    $diff = $current_hour->diff($end_hour);

                    while (($diff->h * 60 + $diff->i) >= intval($service['duration']))
                    {
                        $available_hours[] = $current_hour->format('Y-m-d H:i:s');
                        $current_hour->add(new DateInterval('PT' . $interval . 'M'));
                        $diff = $current_hour->diff($end_hour);
                    }
                }

                $hours = [];
                $selectedDay = (new DateTime($selectedDate))->format('d');
                foreach ($available_hours as $hour) {
                    $localTime = $this->remove_time_offset($hour, $userTimezone, 0);
                    $day = (new DateTime($localTime))->format('d');
                    $formattedHours = (new DateTime($localTime))->format('H:i');
                    if($day == $selectedDay) {
                        array_push($hours, $formattedHours);
                    }
                }

                if (date('Y-m-d', strtotime($selectedDate)) === date('Y-m-d'))
                {
                    $book_advance_timeout = 30;

                    foreach ($hours as $index => $value)
                    {
                        $available_hour = strtotime($value);
                        $current_hour = strtotime('+' . $book_advance_timeout . ' minutes', strtotime('now'));
                        $current_hour = strtotime($this->remove_time_offset(date('H:i', $current_hour),
                            $userTimezone, 0));
                        if ($available_hour <= $current_hour)
                        {
                            unset($hours[$index]);
                        }
                    }
                }

                $hours = array_values($hours);
                sort($hours, SORT_STRING);

                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($hours));
            } catch (Exception $e) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($e));
            }
        }
    }

    public function _get_available_periods($providerId, $selectedDate) {
        $this->load->model('providers_model');
        $this->load->model('appointments_model');

        $day_start_timestamp    = strtotime($selectedDate . ' 00:00:00');
        $day_end_timestamp      = strtotime($selectedDate . ' 23:59:59');
        $user_current_timestamp = strtotime((new DateTime())->format('Y-m-d H:i:s'));
        $periods                = [];
        $previousDate           = (new DateTime($selectedDate . ' -1 day'))->format('Y-m-d');
        $nextDate               = (new DateTime($selectedDate . ' +1 day'))->format('Y-m-d');

        $provider = $this->providers_model->get_row($providerId);
        $timezone = $provider['settings']['timezone'];

        $working_plan = json_decode($this->providers_model->get_setting('working_plan', $providerId), TRUE);

        $previous_date_working_plan = $working_plan[strtolower(date('l', strtotime($previousDate)))];
        $selected_date_working_plan = $working_plan[strtolower(date('l', strtotime($selectedDate)))];
        $next_date_working_plan     = $working_plan[strtolower(date('l', strtotime($nextDate)))];

        // Get the service, provider's appointments.
        $provider_appointments = $this->appointments_model->get_batch([
            'id_users_provider' => $providerId,
        ]);

        if ($user_current_timestamp > $day_end_timestamp) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
        }

        if ($previous_date_working_plan) {
            $start        = $this->get_utc_timestamp($previousDate . ' ' . $previous_date_working_plan['start'] . ':00 ' . $timezone);
            $start_of_day = strtotime($previousDate . ' 00:00:00');
            array_push($periods, [
                'start' => $start_of_day,
                'end'   => $start > $start_of_day ? $start : $start_of_day
            ]);

            if (isset($previous_date_working_plan['breaks'])) {
                foreach ($previous_date_working_plan['breaks'] as $index => $break) {
                    $break_start = $this->get_utc_timestamp($previousDate . ' ' . $break['start'] . ':00 ' . $timezone);
                    $break_end   = $this->get_utc_timestamp($previousDate . ' ' . $break['end'] . ':00 ' . $timezone);

                    array_push($periods, [
                        'start' => $break_start,
                        'end'   => $break_end
                    ]);
                }
            }

            if ($selected_date_working_plan) {
                $start = $this->get_utc_timestamp($previousDate . ' ' . $previous_date_working_plan['end'] . ':00 ' . $timezone);
                $end   = $this->get_utc_timestamp($selectedDate . ' ' . $selected_date_working_plan['start'] . ':00 ' . $timezone);
                array_push($periods, [
                    'start' => $start,
                    'end'   => $end
                ]);

                if (isset($selected_date_working_plan['breaks'])) {
                    foreach ($selected_date_working_plan['breaks'] as $index => $break) {
                        $break_start = $this->get_utc_timestamp($selectedDate . ' ' . $break['start'] . ':00 ' . $timezone);
                        $break_end   = $this->get_utc_timestamp($selectedDate . ' ' . $break['end'] . ':00 ' . $timezone);

                        array_push($periods, [
                            'start' => $break_start,
                            'end'   => $break_end
                        ]);
                    }
                }
            }

            if ($next_date_working_plan) {
                if ($selected_date_working_plan) {
                    $start = $this->get_utc_timestamp($selectedDate . ' ' . $selected_date_working_plan['end'] . ':00 ' . $timezone);
                    $end   = $this->get_utc_timestamp($nextDate . ' ' . $next_date_working_plan['start'] . ':00 ' . $timezone);
                    array_push($periods, [
                        'start' => $start,
                        'end'   => $end
                    ]);
                } else {
                    $start = $this->get_utc_timestamp($previousDate . ' ' . $previous_date_working_plan['end'] . ':00 ' . $timezone);
                    $end   = $this->get_utc_timestamp($nextDate . ' ' . $next_date_working_plan['start'] . ':00 ' . $timezone);
                    array_push($periods, [
                        'start' => $start,
                        'end'   => $end
                    ]);
                }

                $start      = $this->get_utc_timestamp($nextDate . ' ' . $next_date_working_plan['end'] . ':00 ' .
                    $timezone
                );
                $end_of_day = strtotime($nextDate . ' 23:59:59');
                array_push($periods, [
                    'start' => $start < $end_of_day ? $start : $end_of_day,
                    'end'   => $end_of_day
                ]);

                if (isset($next_date_working_plan['breaks'])) {
                    foreach ($next_date_working_plan['breaks'] as $index => $break) {
                        $break_start = $this->get_utc_timestamp($nextDate . ' ' . $break['start'] . ':00 ' . $timezone);
                        $break_end   = $this->get_utc_timestamp($nextDate . ' ' . $break['end'] . ':00 ' . $timezone);

                        array_push($periods, [
                            'start' => $break_start,
                            'end'   => $break_end
                        ]);
                    }
                }
            } else {
                if ($selected_date_working_plan) {
                    $start = $this->get_utc_timestamp($selectedDate . ' ' . $selected_date_working_plan['end'] . ':00 ' . $timezone);
                    $end   = strtotime($selectedDate . ' 23:59:59');
                    array_push($periods, [
                        'start' => $start < $end ? $start : $end,
                        'end'   => $end
                    ]);
                } else {
                    $start = $this->get_utc_timestamp($previousDate . ' ' . $previous_date_working_plan['end'] . ':00 ' . $timezone);
                    $end   = strtotime($previousDate . ' 23:59:59');
                    array_push($periods, [
                        'start' => $start < $end ? $start : $end,
                        'end'   => $end
                    ]);
                }
            }
        } else {
            if ($selected_date_working_plan) {
                $start        = $this->get_utc_timestamp(
                    $selectedDate . ' ' . $selected_date_working_plan['start'] . ':00 ' .
                    $timezone
                );
                $start_of_day = strtotime($selectedDate . ' 00:00:00');
                array_push($periods, [
                    'start' => $start_of_day,
                    'end'   => $start > $start_of_day ? $start : $start_of_day
                ]);

                if (isset($selected_date_working_plan['breaks'])) {
                    foreach ($selected_date_working_plan['breaks'] as $index => $break) {
                        $break_start = $this->get_utc_timestamp($selectedDate . ' ' . $break['start'] . ':00 ' . $timezone);
                        $break_end   = $this->get_utc_timestamp($selectedDate . ' ' . $break['end'] . ':00 ' . $timezone);

                        array_push($periods, [
                            'start' => $break_start,
                            'end'   => $break_end
                        ]);
                    }
                }

                if ($next_date_working_plan) {
                    $start = $this->get_utc_timestamp(
                        $selectedDate . ' ' . $selected_date_working_plan['end'] . ':00 ' .
                        $timezone
                    );
                    $end   = $this->get_utc_timestamp(
                        $nextDate . ' ' . $next_date_working_plan['start'] . ':00 ' .
                        $timezone
                    );
                    array_push($periods, [
                        'start' => $start,
                        'end'   => $end
                    ]);

                    $start      = $this->get_utc_timestamp(
                        $nextDate . ' ' . $next_date_working_plan['end'] . ':00 ' .
                        $timezone
                    );
                    $end_of_day = strtotime($nextDate . ' 23:59:59');
                    array_push($periods, [
                        'start' => $start < $end_of_day ? $start : $end_of_day,
                        'end'   => $end_of_day
                    ]);

                    if (isset($next_date_working_plan['breaks'])) {
                        foreach ($next_date_working_plan['breaks'] as $index => $break) {
                            $break_start = $this->get_utc_timestamp(
                                $nextDate . ' ' . $break['start'] . ':00 ' .
                                $timezone
                            );
                            $break_end   = $this->get_utc_timestamp(
                                $nextDate . ' ' . $break['end'] . ':00 ' .
                                $timezone
                            );

                            array_push($periods, [
                                'start' => $break_start,
                                'end'   => $break_end
                            ]);
                        }
                    }
                } else {
                    $start      = $this->get_utc_timestamp(
                        $selectedDate . ' ' . $selected_date_working_plan['end'] . ':00' .
                        $timezone
                    );
                    $end_of_day = strtotime($selectedDate . ' 23:59:59');
                    array_push($periods, [
                        'start' => $start < $end_of_day ? $start : $end_of_day,
                        'end'   => $end_of_day
                    ]);

                }
            } else {
                if ($next_date_working_plan) {
                    $start        = $this->get_utc_timestamp(
                        $nextDate . ' ' . $next_date_working_plan['start'] . ':00 ' .
                        $timezone
                    );
                    $start_of_day = $nextDate . ' 00:00:00';
                    array_push($periods, [
                        'start' => $start_of_day,
                        'end'   => $start > $start_of_day ? $start : $start_of_day
                    ]);

                    $start      = $this->get_utc_timestamp(
                        $nextDate . ' ' . $next_date_working_plan['end'] . ':00 ' .
                        $timezone
                    );
                    $end_of_day = strtotime($nextDate . ' 23:59:59');
                    array_push($periods, [
                        'start' => $start < $end_of_day ? $start : $end_of_day,
                        'end'   => $end_of_day
                    ]);

                    if (isset($next_date_working_plan['breaks'])) {
                        foreach ($next_date_working_plan['breaks'] as $index => $break) {
                            $break_start = $this->get_utc_timestamp(
                                $nextDate . ' ' . $break['start'] . ':00 ' .
                                $timezone
                            );
                            $break_end   = $this->get_utc_timestamp(
                                $nextDate . ' ' . $break['end'] . ':00 ' .
                                $timezone
                            );

                            array_push($periods, [
                                'start' => $break_start,
                                'end'   => $break_end
                            ]);
                        }
                    }
                }
            }
        }

        foreach ($provider_appointments as $provider_appointment) {
            $appt_date = (new DateTime($provider_appointment['start_datetime']))->format('Y-m-d');
            if ($appt_date == $previousDate || $appt_date == $selectedDate || $appt_date == $nextDate) {
                array_push($periods, [
                    'start' => strtotime($provider_appointment['start_datetime']),
                    'end'   => strtotime($provider_appointment['end_datetime'])
                ]);
            }
        }

        $sorted_periods = array();
        foreach ($periods as $key => $row) {
            $sorted_periods[$key] = $row['start'];
        }
        array_multisort($sorted_periods, SORT_ASC, $periods);

        $free_periods = [];

        // Find free periods by adjacent comparisons
        for ($i = 0; $i < count($periods); $i++) {
            if (($i + 1) < count($periods) && $periods[$i]['end'] < $periods[$i + 1]['start']) {
                array_push($free_periods, [
                    'start' => $periods[$i]['end'],
                    'end'   => $periods[$i + 1]['start']
                ]);
            }
        }

        return $free_periods;
    }

    private function get_utc_timestamp($time) {
        if($time) {
            $date = new DateTime($time);
            return $date->getTimestamp();
        }
        return null;
    }

    private function get_available_hours_for_day($date, $providerId, $manageMode, $apptId, $serviceId, $service_duration) {
        $this->load->model('providers_model');
        $this->load->model('appointments_model');
        $this->load->model('settings_model');
        $this->load->model('services_model');

        // Do not continue if there was no provider selected (more likely there is no provider in the system).
        if (empty($providerId))
        {
            return [];
        }

        // If manage mode is TRUE then the following we should not consider the selected
        // appointment when calculating the available time periods of the provider.
        $exclude_appointments = ($manageMode === 'true')
            ? [$apptId]
            : [];

        // If the user has selected the "any-provider" option then we will need to search
        // for an available provider that will provide the requested service.
        if ($providerId === ANY_PROVIDER)
        {
            $_POST['provider_id'] = $this->_search_any_provider($serviceId,
                $date);
            if ($providerId === NULL)
            {
                return [];
            }
        }

        $service = $this->services_model->get_row($serviceId);
        $provider = $this->providers_model->get_row($_POST['provider_id']);

        $empty_periods = $this->_get_provider_available_time_periods($providerId,
            $serviceId,
            $date, $exclude_appointments);

        $available_hours = $this->_calculate_available_hours($empty_periods, $date,
            $service_duration,
            filter_var($manageMode, FILTER_VALIDATE_BOOLEAN),
            $service['availabilities_type']);

        if ($service['attendants_number'] > 1)
        {
            $available_hours = $this->_get_multiple_attendants_hours($date, $service,
                $provider);
        }

        // If the selected date is today, remove past hours. It is important  include the timeout before
        // booking that is set in the back-office the system. Normally we might want the customer to book
        // an appointment that is at least half or one hour from now. The setting is stored in minutes.
        if (date('Y-m-d', strtotime($date)) === date('Y-m-d'))
        {
            $book_advance_timeout = $this->settings_model->get_setting('book_advance_timeout');

            foreach ($available_hours as $index => $value)
            {
                $available_hour = strtotime($value);
                $current_hour = strtotime('+' . $book_advance_timeout . ' minutes', strtotime('now'));
                if ($available_hour <= $current_hour)
                {
                    unset($available_hours[$index]);
                }
            }
        }

        $available_hours = array_values($available_hours);
        sort($available_hours, SORT_STRING);
        return array_values($available_hours);
    }

    private function process_hours($prevDay, $previous_day_hours, $currentDate, $available_hours, $nextDay, $next_day_hours,
                                   $timezone, $currentDay) {
        $hours = [];
        foreach ($available_hours as $hour) {
            $localTime = $this->remove_time_offset($currentDate.' '.$hour.':00', $timezone);
            $time = (new DateTime($localTime))->format('H:i');
            $day = (new DateTime($localTime))->format('d');
            if($day === $currentDay) {
                array_push($hours, $time);
            }
        }
        foreach ($previous_day_hours as $hour) {
            $localTime = $this->remove_time_offset($prevDay.' '.$hour.':00', $timezone);
            $time = (new DateTime($localTime))->format('H:i');
            $day = (new DateTime($localTime))->format('d');
            if($day === $currentDay) {
                array_push($hours, $time);
            }
        }
        foreach ($next_day_hours as $hour) {
            $localTime = $this->remove_time_offset($nextDay.' '.$hour.':00', $timezone);
            $time = (new DateTime($localTime))->format('H:i');
            $day = (new DateTime($localTime))->format('d');
            if($day === $currentDay) {
                array_push($hours, $time);
            }
        }

        return array_unique($hours);
    }

    function remove_time_offset($time, $timezone, $reverse = 1) {
        $offset = substr($timezone, -5);
        if(!$reverse) {
            return substr($timezone, -6, 1) === '+' ? $this->addTime($time, $offset) : $this->diff($time, $offset);
        }
        return substr($timezone, -6, 1) === '+' ? $this->diff($time, $offset) : $this->addTime($time, $offset);
    }

    public function addTime($event_time, $time_offset) {
        $t1 = new DateTime($event_time);
        $t2 = new DateTime($time_offset);
        $start_of_time = new DateTime('00:00:00');
        $diff = $start_of_time->diff($t2) ;
        return str_replace('T', ' ', substr($t1->add($diff)->format(DATE_ATOM), 0, -6));
    }

    public function diff($event_time, $time_offset) {
        $t1 = new DateTime($event_time);
        $t2 = new DateTime($time_offset);
        $start_of_time = new DateTime('00:00:00');
        $diff = $t2->diff($start_of_time) ;
        return str_replace('T', ' ', substr($t1->add($diff)->format(DATE_ATOM), 0, -6));
    }

    /**
     * [AJAX] Register the appointment to the database.
     *
     * Outputs a JSON string with the appointment ID.
     */
    public function ajax_register_appointment()
    {
        try
        {
            $post_data = $this->input->post('post_data'); // alias
            $post_data['manage_mode'] = filter_var($post_data['manage_mode'], FILTER_VALIDATE_BOOLEAN);

            $this->load->model('appointments_model');
            $this->load->model('providers_model');
            $this->load->model('services_model');
            $this->load->model('customers_model');
            $this->load->model('settings_model');

            // Validate the CAPTCHA string.
            if ($this->settings_model->get_setting('require_captcha') === '1'
                && $this->session->userdata('captcha_phrase') !== $this->input->post('captcha'))
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'captcha_verification' => FALSE
                    ]));
                return;
            }

//            // Check appointment availability.
            if ( ! $this->_check_datetime_availability())
            {
                throw new Exception($this->lang->line('requested_hour_is_unavailable'));
            }

            $appointment = $_POST['post_data']['appointment'];
            $customer = $_POST['post_data']['customer'];

            if ($this->customers_model->exists($customer))
            {
                $customer['id'] = $this->customers_model->find_record_id($customer);
            }

            $customer_id = $this->customers_model->add($customer);
            $appointment['id_users_customer'] = $customer_id;
            $appointment['is_unavailable'] = (int)$appointment['is_unavailable']; // needs to be type casted
            $appointment['id'] = $this->appointments_model->add($appointment);
            $appointment['hash'] = $this->appointments_model->get_value('hash', $appointment['id']);

            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $company_settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            // :: SYNCHRONIZE APPOINTMENT WITH PROVIDER'S GOOGLE CALENDAR
            // The provider must have previously granted access to his google calendar account
            // in order to sync the appointment.
            try
            {
                $google_sync = filter_var($this->providers_model->get_setting('google_sync',
                    $appointment['id_users_provider']), FILTER_VALIDATE_BOOLEAN);

                if ($google_sync == TRUE)
                {
                    $google_token = json_decode($this->providers_model
                        ->get_setting('google_token', $appointment['id_users_provider']));

                    $this->load->library('google_sync');
                    $this->google_sync->refresh_token($google_token->refresh_token);

                    if ($post_data['manage_mode'] === FALSE)
                    {
                        // Add appointment to Google Calendar.
                        $google_event = $this->google_sync->add_appointment($appointment, $provider,
                            $service, $customer, $company_settings);
                        $appointment['id_google_calendar'] = $google_event->id;
                        $this->appointments_model->add($appointment);
                    }
                    else
                    {
                        // Update appointment to Google Calendar.
                        $appointment['id_google_calendar'] = $this->appointments_model
                            ->get_value('id_google_calendar', $appointment['id']);

                        $this->google_sync->update_appointment($appointment, $provider,
                            $service, $customer, $company_settings);
                    }
                }
            }
            catch (Exception $exc)
            {
                log_message('error', $exc->getMessage());
                log_message('error', $exc->getTraceAsString());
            }

            // :: SEND NOTIFICATION EMAILS TO BOTH CUSTOMER AND PROVIDER
            try
            {
                $this->config->load('email');
                $email = new \EA\Engine\Notifications\Email($this, $this->config->config);

                if ($post_data['manage_mode'] == FALSE)
                {
                    $customer_title = new Text($this->lang->line('appointment_booked'));
                    $customer_message = new Text('Your appointment with ' . $provider['first_name'] . ' ' . $provider['last_name'] .' is confirmed. Please check below for more details.');
                    $provider_title = new Text($this->lang->line('appointment_added_to_your_plan'));
                    $provider_message = new Text($customer['first_name'] . ' ' . $customer['last_name'] . ' has booked an appointment for '. $service['name']. '.');

                }
                else
                {
                    $customer_title = new Text($this->lang->line('appointment_changes_saved'));
                    $customer_message = new Text('');
                    $provider_title = new Text($this->lang->line('appointment_details_changed'));
                    $provider_message = new Text('');
                }

                $customer_link = new Url(site_url('appointments/index/' . $appointment['hash']));
                $provider_link = new Url(site_url('backend/index/' . $appointment['hash']));

                $send_customer = filter_var($this->settings_model->get_setting('customer_notifications'),
                    FILTER_VALIDATE_BOOLEAN);

                $this->load->library('ics_file');
                $ics_stream = $this->ics_file->get_stream($appointment, $service, $provider, $customer);

//                if ($send_customer === TRUE)
//                {
                $email->sendAppointmentDetails($appointment, $provider,
                    $service, $customer, $company_settings, $customer_title,
                    $customer_message, $customer_link, new Email($customer['email']), new Text($ics_stream), true);
//                }

                $send_provider = filter_var($this->providers_model->get_setting('notifications', $provider['id']),
                    FILTER_VALIDATE_BOOLEAN);

//                if ($send_provider === TRUE)
//                {
                $email->sendAppointmentDetails($appointment, $provider,
                    $service, $customer, $company_settings, $provider_title,
                    $provider_message, $provider_link, new Email($provider['email']), new Text($ics_stream), false);
//                }
            }
            catch (Exception $exc)
            {
                log_message('error', $exc->getMessage());
                log_message('error', $exc->getTraceAsString());
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'appointment_id' => $appointment['id']
                ]));
        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'exceptions' => [exceptionToJavaScript($exc)]
                ]));
        }
    }

    /**
     * [AJAX] Get Unavailable Dates
     *
     * Get an array with the available dates of a specific provider, service and month of the year. Provide the
     * "provider_id", "service_id" and "selected_date" as GET parameters to the request. The "selected_date" parameter
     * must have the Y-m-d format.
     *
     * Outputs a JSON string with the unavailable dates. that are unavailable.
     *
     * @deprecated Since v1.3.0, this method will be replaced with a future release.
     */
    public function ajax_get_unavailable_dates()
    {
        try
        {
//            $provider_id = $this->input->get('provider_id');
//            $service_id = $this->input->get('service_id');
//            $selected_date_string = $this->input->get('selected_date');
//            $selected_date = new DateTime($selected_date_string);
//            $number_of_days_in_month = (int)$selected_date->format('t');
//            $unavailable_dates = [];
//            $manage_mode = filter_var($this->input->get('manage_mode'), FILTER_VALIDATE_BOOLEAN);
//
//            $exclude_appointments = ($_REQUEST['manage_mode'] === 'true')
//                ? [$_REQUEST['appointment_id']]
//                : [];
//
//            $provider_list = ($provider_id === ANY_PROVIDER) ? $this->_search_providers_by_service($service_id) : [$provider_id] ;
//
//            $this->load->model('providers_model');
//
//            // Get the service record.
//            $this->load->model('services_model');
//            $service = $this->services_model->get_row($service_id);
//
//            for ($i = 1; $i <= $number_of_days_in_month; $i++)
//            {
//                $current_date = new DateTime($selected_date->format('Y-m') . '-' . $i);
//
//                if ($current_date < new DateTime(date('Y-m-d 00:00:00')))
//                {
//                    // Past dates become immediately unavailable.
//                    $unavailable_dates[] = $current_date->format('Y-m-d');
//                    continue;
//                }
//
//                // Finding at least one slot of availablity
//                foreach ($provider_list as $curr_provider_id)
//                {
//                    // Get the provider record.
//                    $curr_provider = $this->providers_model->get_row($curr_provider_id);
//
//                    $empty_periods = $this->_get_provider_available_time_periods($curr_provider_id,
//                        $service_id,
//                        $current_date->format('Y-m-d'), $exclude_appointments);
//
//                    $available_hours = $this->_calculate_available_hours($empty_periods, $current_date->format('Y-m-d'),
//                        $service['duration'], $manage_mode, $service['availabilities_type']);
//                    if (! empty($available_hours)) break;
//
//                    if ($service['attendants_number'] > 1)
//                    {
//                        $available_hours = $this->_get_multiple_attendants_hours($current_date->format('Y-m-d'), $service,
//                            $curr_provider);
//                        if (! empty($available_hours)) break;
//                    }
//                }
//
//                // No availability amongst all the provider
//                if (empty($available_hours))
//                {
//                    $unavailable_dates[] = $current_date->format('Y-m-d');
//                }
//            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'exceptions' => [exceptionToJavaScript($exc)]
                ]));
        }
    }

    /**
     * Check whether the provider is still available in the selected appointment date.
     *
     * It might be times where two or more customers select the same appointment date and time. This shouldn't be
     * allowed to happen, so one of the two customers will eventually get the preferred date and the other one will have
     * to choose for another date. Use this method just before the customer confirms the appointment details. If the
     * selected date was taken in the mean time, the customer must be prompted to select another time for his
     * appointment.
     *
     * @return bool Returns whether the selected datetime is still available.
     */
    protected function _check_datetime_availability()
    {
        $this->load->model('services_model');
        $this->load->model('appointments_model');

        $appointment = $_POST['post_data']['appointment'];

        $service_duration = $this->services_model->get_value('duration', $appointment['id_services']);

        $exclude_appointments = (isset($appointment['id'])) ? [$appointment['id']] : [];

        $attendants_number = $this->services_model->get_value('attendants_number', $appointment['id_services']);

        if ($attendants_number > 1)
        {
            // Exclude all the appointments that will are currently registered.
            $exclude = $this->appointments_model->get_batch([
                'id_services' => $appointment['id_services'],
                'start_datetime' => $appointment['start_datetime']
            ]);

            if ( ! empty($exclude) && count($exclude) < $attendants_number)
            {
                foreach ($exclude as $entry)
                {
                    $exclude_appointments[] = $entry['id'];
                }
            }
        }

        if ($appointment['id_users_provider'] === ANY_PROVIDER)
        {
            $appointment['id_users_provider'] = $this->_search_any_provider($appointment['id_services'],
                date('Y-m-d', strtotime($appointment['start_datetime'])));
            $_POST['post_data']['appointment']['id_users_provider'] = $appointment['id_users_provider'];
            return TRUE; // The selected provider is always available.
        }

        $available_periods = $this->_get_available_periods(
            $appointment['id_users_provider'],
            date('Y-m-d', strtotime($appointment['start_datetime'])));

        $is_still_available = FALSE;

        foreach ($available_periods as $period)
        {
            $appt_start = new DateTime($appointment['start_datetime']);
            $appt_start = $appt_start->format('H:i');

            $appt_end = new DateTime($appointment['start_datetime']);
            $appt_end->add(new DateInterval('PT' . $service_duration . 'M'));
            $appt_end = $appt_end->format('H:i');

            $period_start = date('H:i', $period['start']);
            $period_end = date('H:i', $period['end']);

            if ($period_start <= $appt_start && $period_end >= $appt_end)
            {
                $is_still_available = TRUE;
                break;
            }
        }

        return $is_still_available;
    }

    /**
     * Get an array containing the free time periods (start - end) of a selected date.
     *
     * This method is very important because there are many cases where the system needs to know when a provider is
     * available for an appointment. This method will return an array that belongs to the selected date and contains
     * values that have the start and the end time of an available time period.
     *
     * @param int $provider_id Provider record ID.
     * @param int $service_id Service record ID.
     * @param string $selected_date Date to be checked (MySQL formatted string).
     * @param array $excluded_appointment_ids Array containing the IDs of the appointments that will not be taken into
     * consideration when the available time periods are calculated.
     *
     * @return array Returns an array with the available time periods of the provider.
     */
    protected function _get_provider_available_time_periods(
        $provider_id,
        $service_id,
        $selected_date,
        $excluded_appointment_ids = []
    ) {
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');

        // Get the service, provider's working plan.
        $working_plan = json_decode($this->providers_model->get_setting('working_plan', $provider_id), TRUE);

        // Get the service, provider's appointments.
        $provider_appointments = $this->appointments_model->get_batch([
            'id_users_provider' => $provider_id,
        ]);

        $periods = [];

        $selected_date_working_plan = $working_plan[strtolower(date('l', strtotime($selected_date)))];

        if(!is_null($selected_date_working_plan['start'])) {
            // Add period before work start
            array_push($periods, [
                'start' => '00:00',
                'end'   => $selected_date_working_plan['start']
            ]);
        }

        if(!is_null($selected_date_working_plan['end'])) {
            if (!is_null($selected_date_working_plan['start']) && ($selected_date_working_plan['start'] > $selected_date_working_plan['end'])) {
                array_push($periods, [
                    'start' => '23:59',
                    'end'   => '23:59'
                ]);
            } else {
                array_push($periods, [
                    'start' => $selected_date_working_plan['end'],
                    'end'   => '23:59'
                ]);
            }
        }

        // Add period for breaks
        if (isset($selected_date_working_plan['breaks'])) {
            foreach ($selected_date_working_plan['breaks'] as $index => $break) {
                $break_start = $break['start'];
                $break_end   = $break['end'];

                array_push($periods, [
                    'start' => $break_start,
                    'end'   => $break_end
                ]);
            }
        }

        // Add period for appointments
        foreach ($provider_appointments as $provider_appointment) {
            $appt_date = (new DateTime($provider_appointment['start_datetime']))->format('Y-m-d');
            if($appt_date == $selected_date) {
                $appointment_start = (new DateTime($provider_appointment['start_datetime']))->format('H:i');
                $appointment_end   = (new DateTime($provider_appointment['end_datetime']))->format('H:i');

                array_push($periods, [
                    'start' => $appointment_start,
                    'end'   => $appointment_end
                ]);
            }
        }

        // Sort periods for further comparisons
        $sorted_periods = array();
        foreach ($periods as $key => $row)
        {
            $sorted_periods[$key] = $row['start'];
        }
        array_multisort($sorted_periods, SORT_ASC, $periods);

        $free_periods = [];

        // Find free periods by adjacent comparisons
        for($i = 0; $i < count($periods); $i++) {
            if(($i + 1) < count($periods) && $periods[$i]['end'] < $periods[$i+1]['start']) {
                array_push($free_periods, [
                    'start' => $periods[$i]['end'],
                    'end' => $periods[$i+1]['start']
                ]);
            }
        }

        return array_values($free_periods);
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the provider with the most available periods.
     *
     * @param int $service_id The requested service ID.
     * @param string $selected_date The date to be searched.
     *
     * @return int Returns the ID of the provider that can provide the service at the selected date.
     */
    protected function _search_any_provider($service_id, $selected_date)
    {
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $available_providers = $this->providers_model->get_available_providers();
        $service = $this->services_model->get_row($service_id);
        $provider_id = NULL;
        $max_hours_count = 0;

        foreach ($available_providers as $provider)
        {
            foreach ($provider['services'] as $provider_service_id)
            {
                if ($provider_service_id == $service_id)
                {
                    // Check if the provider is available for the requested date.
                    $empty_periods = $this->_get_provider_available_time_periods($provider['id'], $service_id,
                        $selected_date);

                    $available_hours = $this->_calculate_available_hours($empty_periods, $selected_date,
                        $service['duration'], FALSE, $service['availabilities_type']);

                    if ($service['attendants_number'] > 1)
                    {
                        $available_hours = $this->_get_multiple_attendants_hours($selected_date, $service,
                            $provider);
                    }

                    if (count($available_hours) > $max_hours_count)
                    {
                        $provider_id = $provider['id'];
                        $max_hours_count = count($available_hours);
                    }
                }
            }
        }

        return $provider_id;
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the providers affected to the requested service.
     *
     * @param numeric $service_id The requested service ID.
     *
     * @return array Returns the ID of the provider that can provide the requested service.
     */
    protected function _search_providers_by_service($service_id)
    {
        $this->load->model('providers_model');
        $available_providers = $this->providers_model->get_available_providers();
        $provider_list = array();

        foreach ($available_providers as $provider)
        {
            foreach ($provider['services'] as $provider_service_id)
            {
                if ($provider_service_id === $service_id)
                {
                    // Check if the provider is affected to the selected service.
                    $provider_list[] = $provider['id'];
                }
            }
        }

        return $provider_list;
    }

    /**
     * Calculate the available appointment hours.
     *
     * Calculate the available appointment hours for the given date. The empty spaces
     * are broken down to 15 min and if the service fit in each quarter then a new
     * available hour is added to the "$available_hours" array.
     *
     * @param array $empty_periods Contains the empty periods as generated by the "_get_provider_available_time_periods"
     * method.
     * @param string $selected_date The selected date to be search (format )
     * @param int $service_duration The service duration is required for the hour calculation.
     * @param bool $manage_mode (optional) Whether we are currently on manage mode (editing an existing appointment).
     * @param string $availabilities_type Optional ('flexible'), the service availabilities type.
     *
     * @return array Returns an array with the available hours for the appointment.
     */
    protected function _calculate_available_hours(
        array $empty_periods,
        $selected_date,
        $service_duration,
        $manage_mode = FALSE,
        $availabilities_type = 'flexible'
    ) {
        $this->load->model('settings_model');

        $available_hours = [];

        foreach ($empty_periods as $period)
        {
            $start_hour = new DateTime($selected_date . ' ' . $period['start']);
            $end_hour = new DateTime($selected_date . ' ' . $period['end']);
            $interval = $availabilities_type === AVAILABILITIES_TYPE_FIXED ? (int)$service_duration : 5;

            $current_hour = $start_hour;
            $diff = $current_hour->diff($end_hour);

            while (($diff->h * 60 + $diff->i) >= intval($service_duration))
            {
                $available_hours[] = $current_hour->format('H:i');
                $current_hour->add(new DateInterval('PT' . $interval . 'M'));
                $diff = $current_hour->diff($end_hour);
            }
        }

        return $available_hours;
    }

    /**
     * Get multiple attendants hours.
     *
     * This method will add the extra appointment hours whenever a service accepts multiple attendants.
     *
     * @param string $selected_date The selected appointment date.
     * @param array $service Selected service data.
     * @param array $provider Selected provider data.
     *
     * @return array Returns the available hours array.
     */
    protected function _get_multiple_attendants_hours(
        $selected_date,
        $service,
        $provider
    ) {
        $this->load->model('appointments_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');

        $unavailabilities = $this->appointments_model->get_batch([
            'is_unavailable' => TRUE,
            'DATE(start_datetime)' => $selected_date,
            'id_users_provider' => $provider['id']
        ]);

        $working_plan = json_decode($provider['settings']['working_plan'], TRUE);
        $working_day = strtolower(date('l', strtotime($selected_date)));
        $working_hours = $working_plan[$working_day];

        $periods = [
            [
                'start' => new DateTime($selected_date . ' ' . $working_hours['start']),
                'end' => new DateTime($selected_date . ' ' . $working_hours['end'])
            ]
        ];

        $periods = $this->remove_breaks($selected_date, $periods, $working_hours['breaks']);
        $periods = $this->remove_unavailabilities($periods, $unavailabilities);

        $hours = [];

        $interval_value = $service['availabilities_type'] == AVAILABILITIES_TYPE_FIXED ? $service['duration'] : '15';
        $interval = new DateInterval('PT' . (int)$interval_value . 'M');
        $duration = new DateInterval('PT' . (int)$service['duration'] . 'M');

        foreach ($periods as $period)
        {
            $slot_start = clone $period['start'];
            $slot_end = clone $slot_start;
            $slot_end->add($duration);

            while ($slot_end <= $period['end'])
            {
                // Check reserved attendants for this time slot and see if current attendants fit.
                $appointment_attendants_number = $this->appointments_model->get_attendants_number_for_period($slot_start,
                    $slot_end, $service['id']);

                if ($appointment_attendants_number < $service['attendants_number'])
                {
                    $hours[] = $slot_start->format('H:i');
                }

                $slot_start->add($interval);
                $slot_end->add($interval);
            }
        }

        return $hours;
    }

    /**
     * Remove breaks from available time periods.
     *
     * @param string $selected_date Selected data (Y-m-d format).
     * @param array $periods Time periods of the current date.
     * @param array $breaks Breaks array for the current date.
     *
     * @return array Returns the available time periods without the breaks.
     */
    public function remove_breaks($selected_date, $periods, $breaks)
    {
        if ( ! $breaks)
        {
            return $periods;
        }

        foreach ($breaks as $break)
        {
            $break_start = new DateTime($selected_date . ' ' . $break['start']);
            $break_end = new DateTime($selected_date . ' ' . $break['end']);

            foreach ($periods as &$period)
            {
                $period_start = $period['start'];
                $period_end = $period['end'];

                if ($break_start <= $period_start && $break_end >= $period_start && $break_end <= $period_end)
                {
                    // left
                    $period['start'] = $break_end;
                    continue;
                }

                if ($break_start >= $period_start && $break_start <= $period_end && $break_end >= $period_start && $break_end <= $period_end)
                {
                    // middle
                    $period['end'] = $break_start;
                    $periods[] = [
                        'start' => $break_end,
                        'end' => $period_end
                    ];
                    continue;
                }

                if ($break_start >= $period_start && $break_start <= $period_end && $break_end >= $period_end)
                {
                    // right
                    $period['end'] = $break_start;
                    continue;
                }

                if ($break_start <= $period_start && $break_end >= $period_end)
                {
                    // break contains period
                    $period['start'] = $break_end;
                    continue;
                }
            }
        }

        return $periods;
    }

    /**
     * Remove the unavailabilities from the available time periods of the selected date.
     *
     * @param array $periods Available time periods.
     * @param array $unavailabilities Unavailabilities of the current date.
     *
     * @return array Returns the available time periods without the unavailabilities.
     */
    public function remove_unavailabilities($periods, $unavailabilities)
    {
        foreach ($unavailabilities as $unavailability)
        {
            $unavailability_start = new DateTime($unavailability['start_datetime']);
            $unavailability_end = new DateTime($unavailability['end_datetime']);

            foreach ($periods as &$period)
            {
                $period_start = $period['start'];
                $period_end = $period['end'];

                if ($unavailability_start <= $period_start && $unavailability_end >= $period_start && $unavailability_end <= $period_end)
                {
                    // left
                    $period['start'] = $unavailability_end;
                    continue;
                }

                if ($unavailability_start >= $period_start && $unavailability_start <= $period_end && $unavailability_end >= $period_start && $unavailability_end <= $period_end)
                {
                    // middle
                    $period['end'] = $unavailability_start;
                    $periods[] = [
                        'start' => $unavailability_end,
                        'end' => $period_end
                    ];
                    continue;
                }

                if ($unavailability_start >= $period_start && $unavailability_start <= $period_end && $unavailability_end >= $period_end)
                {
                    // right
                    $period['end'] = $unavailability_start;
                    continue;
                }

                if ($unavailability_start <= $period_start && $unavailability_end >= $period_end)
                {
                    // Unavaibility contains period
                    $period['start'] = $unavailability_end;
                    continue;
                }
            }
        }

        return $periods;
    }
}
