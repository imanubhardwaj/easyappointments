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
 * Google Controller
 *
 * This controller handles the Google Calendar synchronization operations.
 *
 * @package Controllers
 */
class Google extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
    }

    /**
     * Authorize Google Calendar API usage for a specific provider.
     *
     * Since it is required to follow the web application flow, in order to retrieve a refresh token from the Google API
     * service, this method is going to authorize the given provider.
     *
     * @param int $provider_id The provider id, for whom the sync authorization is made.
     */
    public function oauth($provider_id)
    {
        // Store the provider id for use on the callback function.
        $_SESSION['oauth_provider_id'] = $provider_id;

        // Redirect browser to google user content page.
        $this->load->library('Google_sync');
        header('Location: ' . $this->google_sync->get_auth_url());
    }

    /**
     * Callback method for the Google Calendar API authorization process.
     *
     * Once the user grants consent with his Google Calendar data usage, the Google OAuth service will redirect him back
     * in this page. Here we are going to store the refresh token, because this is what will be used to generate access
     * tokens in the future.
     *
     * IMPORTANT: Because it is necessary to authorize the application using the web server flow (see official
     * documentation of OAuth), every Easy!Appointments installation should use its own calendar api key. So in every
     * api console account, the "http://path-to-e!a/google/oauth_callback" should be included in an allowed redirect URL.
     */
    public function oauth_callback()
    {
        if ($this->input->get('code'))
        {
            $this->load->library('Google_sync');
            $token = $this->google_sync->authenticate($this->input->get('code'));

            // Store the token into the database for future reference.
            if (isset($_SESSION['oauth_provider_id']))
            {
                $this->load->model('providers_model');
                $this->providers_model->set_setting('google_sync', TRUE, $_SESSION['oauth_provider_id']);
                $this->providers_model->set_setting('google_token', $token, $_SESSION['oauth_provider_id']);
                $this->providers_model->set_setting('google_calendar', 'primary', $_SESSION['oauth_provider_id']);
            }
            else
            {
                $this->output->set_output('<h1>Sync provider id not specified!</h1>');
            }
        }
        else
        {
            $this->output->set_output('<h1>Authorization Failed!</h1>');
        }
    }

    /**
     * Complete synchronization of appointments between Google Calendar and Easy!Appointments.
     *
     * This method will completely sync the appointments of a provider with his Google Calendar account. The sync period
     * needs to be relatively small, because a lot of API calls might be necessary and this will lead to consuming the
     * Google limit for the Calendar API usage.
     *
     * @param int $provider_id Provider record to be synced.
     */
    public function sync($provider_id = NULL)
    {
        try
        {
            if ($provider_id === NULL)
            {
                throw new Exception('Provider id not specified.');
            }

            $this->load->model('appointments_model');

            $this->appointments_model->syncEvents($provider_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(AJAX_SUCCESS));
        }
        catch (Exception $exc)
        {
        }
    }
}
