<?php
/**
 * Created by PhpStorm.
 * User: manubhardwaj
 * Date: 2019-01-11
 * Time: 18:07
 */

class UserServices extends CI_Controller {
    public function getUserServices() {
        $this->load->model('services_model');
        $this->load->library('session');
        $allServices = $this->services_model->get_available_services();
        $services = [];
        foreach ($allServices as $service) {
            if($service['user_id'] === $this->session->userdata('id')) {
                array_push($services, $service);
            }
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($services));

    }
}