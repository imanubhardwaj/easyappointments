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

use EA\Engine\Types\Email;
use EA\Engine\Types\Text;
use EA\Engine\Types\Url;

/**
 * User Controller
 *
 * @package Controllers
 */
class User extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('user_model');

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
    }

    /**
     * Default Method
     *
     * The default method will redirect the browser to the user/login URL.
     */
    public function index()
    {
        header('Location: ' . site_url('user/login'));
    }

    /**
     * Display the login page.
     */
    public function login()
    {
        $this->load->model('settings_model');

        $view['base_url'] = $this->config->item('base_url');
        $view['dest_url'] = $this->session->userdata('dest_url');

        if ( ! $view['dest_url'])
        {
            $view['dest_url'] = site_url('backend');
        }

        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $this->load->view('user/login', $view);
    }

    /**
     * Display the logout page.
     */
    public function logout()
    {
        $this->load->model('settings_model');

        $this->session->unset_userdata('user_id');
        $this->session->unset_userdata('user_email');
        $this->session->unset_userdata('role_slug');
        $this->session->unset_userdata('username');
        $this->session->unset_userdata('dest_url');

        $view['base_url'] = $this->config->item('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $this->load->view('user/logout', $view);
    }

    /**
     * Display the "forgot password" page.
     */
    public function forgot_password()
    {
        $this->load->model('settings_model');
        $view['base_url'] = $this->config->item('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $this->load->view('user/forgot_password', $view);
    }

    /**
     * Display the "not authorized" page.
     */
    public function no_privileges()
    {
        $this->load->model('settings_model');
        $view['base_url'] = $this->config->item('base_url');
        $view['company_name'] = $this->settings_model->get_setting('company_name');
        $this->load->view('user/no_privileges', $view);
    }

    /**
     * [AJAX] Check whether the user has entered the correct login credentials.
     *
     * The session data of a logged in user are the following:
     *   - 'user_id'
     *   - 'user_email'
     *   - 'role_slug'
     *   - 'dest_url'
     */
    public function ajax_check_login()
    {
        try
        {
            if ( ! $this->input->post('username') || ! $this->input->post('password'))
            {
                throw new Exception('Invalid credentials given!');
            }

            $this->load->model('user_model');
            $user_data = $this->user_model->check_login($this->input->post('username'), $this->input->post('password'));

            if ($user_data)
            {
                $this->session->set_userdata($user_data); // Save data on user's session.
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode('SUCCESS'));
            }
            else
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode('FAILURE'));
            }

        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['exceptions' => [exceptionToJavaScript($exc)]]));
        }
    }

    public function ajax_login_by_code()
    {
        try
        {
            $this->load->model('user_model');
            $user_data = $this->user_model->login_by_code($this->input->post('code'));
            if ($user_data)
            {
                $this->session->set_userdata($user_data); // Save data on user's session.
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(AJAX_SUCCESS));
            }
            else
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(AJAX_FAILURE));
            }

        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['exceptions' => [exceptionToJavaScript($exc)]]));
        }
    }

    /**
     * Regenerate a new password for the current user, only if the username and
     * email address given correspond to an existing user in db.
     *
     * Required POST Parameters:
     *
     * - string $_POST['username'] Username to be validated.
     * - string $_POST['email'] Email to be validated.
     */
    public function ajax_forgot_password()
    {
        try
        {
            if ( ! $this->input->post('username') || ! $this->input->post('email'))
            {
                throw new Exception('You must enter a valid username and email address in '
                    . 'order to get a new password!');
            }

            $this->load->model('user_model');
            $this->load->model('settings_model');

            $new_password = $this->user_model->regenerate_password($this->input->post('username'),
                $this->input->post('email'));

            if ($new_password != FALSE)
            {
                $this->config->load('email');
                $email = new \EA\Engine\Notifications\Email($this, $this->config->config);
                $company_settings = [
                    'company_name' => $this->settings_model->get_setting('company_name'),
                    'company_link' => $this->settings_model->get_setting('company_link'),
                    'company_email' => $this->settings_model->get_setting('company_email')
                ];

                $email->sendPassword(new NonEmptyText($new_password), new Email($this->input->post('email')),
                    $company_settings);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($new_password != FALSE ? AJAX_SUCCESS : AJAX_FAILURE));
        }
        catch (Exception $exc)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['exceptions' => [exceptionToJavaScript($exc)]]));
        }
    }

    public function syncUsersToCalendar() {
        $this->load->model('appointments_model');
        try
        {
            foreach ($this->user_model->get_user_ids() as $user_id) {
                if($user_id && $user_id->id) {
                    $this->appointments_model->syncEvents((int) $user_id->id);
                }
            }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(AJAX_SUCCESS));
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
        }
    }

    public function clearLoginCodes() {
        $this->user_model->remove_previous_login_codes();
    }

    public function gcd($provider_id) {
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        $provider = $this->providers_model->get_row($provider_id);

        $google_sync = $this->providers_model->get_setting('google_sync', $provider['id']);

        if ( ! $google_sync)
        {
            throw new Exception('The selected provider has not the google synchronization setting enabled.');
        }

        $google_token = json_decode($this->providers_model->get_setting('google_token', $provider['id']));
        $this->load->library('google_sync');
        $this->google_sync->refresh_token($google_token->refresh_token);

        $google_past_days = Config::SYNC_GOOGLE_PAST_DAYS;
        $google_future_days = Config::SYNC_GOOGLE_FUTURE_DAYS;
        $google_start = strtotime('-' . $google_past_days . ' days', strtotime(date('Y-m-d')));
        $google_end = strtotime('+' . $google_future_days . ' days', strtotime(date('Y-m-d')));

        $abc = [];

        $google_calendar = $provider['settings']['google_calendar'];
        $events = $this->google_sync->get_sync_events($google_calendar, $google_start, $google_end);

        foreach ($events->getItems() as $event) {
            $start_time  = $this->remove_time_offset($event->start->getDateTime());
            $end_time    = $this->remove_time_offset($event->end->getDateTime());
            $appointment = [
                'start_datetime'     => date('Y-m-d H:i:s', strtotime($start_time)),
                'end_datetime'       => date('Y-m-d H:i:s', strtotime($end_time)),
                'is_unavailable'     => TRUE,
                'notes'              => $event->getSummary() . ' ' . $event->getDescription(),
                'id_users_provider'  => $provider_id,
                'id_google_calendar' => $event->getId(),
                'id_users_customer'  => NULL,
                'id_services'        => NULL,
            ];
            $abc[]       = $appointment;
        }
        var_dump($abc);
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

    public function sendApptMail() {
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        $where_clause = 'start_datetime > ' . $this->db->escape((new DateTime())->format('Y-m-d H:i:s')) .
            ' AND start_datetime < ' . $this->db->escape((new DateTime('now +1 hour'))->format('Y-m-d H:i:s')) .
            ' AND mail_pending = 1';

        $response['appointments'] = $this->appointments_model->get_batch($where_clause);

        $company_settings = [
            'company_name' => $this->settings_model->get_setting('company_name'),
            'company_link' => $this->settings_model->get_setting('company_link'),
            'company_email' => $this->settings_model->get_setting('company_email'),
            'date_format' => $this->settings_model->get_setting('date_format'),
            'time_format' => $this->settings_model->get_setting('time_format')
        ];

        try {
            $this->config->load('email');
            $this->load->library('ics_file');
            $email = new \EA\Engine\Notifications\Email($this, $this->config->config);
            foreach ($response['appointments'] as $appt) {
                if ($appt && $appt['id_services']) {
                    // :: SEND NOTIFICATION EMAILS TO BOTH CUSTOMER AND PROVIDER

                    $provider = $this->providers_model->get_row($appt['id_users_provider']);
                    $customer = $this->customers_model->get_row($appt['id_users_customer']);
                    $service = $this->services_model->get_row($appt['id_services']);

                    $time_left = (new DateTime())->diff(new DateTime($appt['start_datetime']))->i;
                    $customer_title   = new Text('Upcoming Appointment');
                    $customer_message = new Text('Just a reminder, Your appointment with ' . $provider['first_name'] . ' ' .
                        $provider['last_name'] . ' is due');
                    $provider_title   = new Text('Upcoming Appointment');
                    $provider_message = new Text('Just a reminder, Your appointment with ' . $customer['first_name'] . ' ' .
                        $customer['last_name'] . ' is due');

                    $customer_link = new Url(site_url('appointments/index/' . $appt['hash']));
                    $provider_link = new Url(site_url('backend/index/' . $appt['hash']));

                    $this->load->library('ics_file');
                    $ics_stream = $this->ics_file->get_stream($appt, $service, $provider, $customer);

                    $email->sendUpcomingApptMail($appt, $provider,
                        $service, $customer, $company_settings, $customer_title,
                        $customer_message, $customer_link, new Email($customer['email']), new Text($ics_stream), true);

                    $email->sendUpcomingApptMail($appt, $provider,
                        $service, $customer, $company_settings, $provider_title,
                        $provider_message, $provider_link, new Email($provider['email']), new Text($ics_stream), false);

                    $this->db->where('id', $appt['id'])->update('ea_appointments', [
                        'mail_pending' => 0
                    ]);
                }
            }
        } catch (Exception $exc) {
            log_message('error', $exc->getMessage());
            log_message('error', $exc->getTraceAsString());
        }
    }
}
