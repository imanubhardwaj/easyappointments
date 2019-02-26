<?php

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

namespace EA\Engine\Notifications;

use DateTime;
use \EA\Engine\Types\Text;
use \EA\Engine\Types\NonEmptyText;
use \EA\Engine\Types\Url;
use \EA\Engine\Types\Email as EmailAddress;

/**
 * Email Notifications Class
 *
 * This library handles all the notification email deliveries on the system.
 *
 * Important: The email configuration settings are located at: /application/config/email.php
 */
class Email {
    /**
     * Framework Instance
     *
     * @var CI_Controller
     */
    protected $framework;
    protected $timezones;

    /**
     * Contains email configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Class Constructor
     *
     * @param \CI_Controller $framework
     * @param array $config Contains the email configuration to be used.
     */
    public function __construct(\CI_Controller $framework, array $config)
    {
        $this->loadTimezones();
        $this->framework = $framework;
        $this->config = $config;
    }

    /**
     * Replace the email template variables.
     *
     * This method finds and replaces the html variables of an email template. It is used to
     * generate dynamic HTML emails that are send as notifications to the system users.
     *
     * @param array $replaceArray Array that contains the variables to be replaced.
     * @param string $templateHtml The email template HTML.
     *
     * @return string Returns the new email html that contain the variables of the $replaceArray.
     */
    protected function _replaceTemplateVariables(array $replaceArray, $templateHtml)
    {
        foreach ($replaceArray as $name => $value)
        {
            $templateHtml = str_replace($name, $value, $templateHtml);
        }

        return $templateHtml;
    }

    /**
     * Send an email with the appointment details.
     *
     * This email template also needs an email title and an email text in order to complete
     * the appointment details.
     *
     * @param array $appointment Contains the appointment data.
     * @param array $provider Contains the provider data.
     * @param array $service Contains the service data.
     * @param array $customer Contains the customer data.
     * @param array $company Contains settings of the company. By the time the
     * "company_name", "company_link" and "company_email" values are required in the array.
     * @param \EA\Engine\Types\Text $title The email title may vary depending the receiver.
     * @param \EA\Engine\Types\Text $message The email message may vary depending the receiver.
     * @param \EA\Engine\Types\Url $appointmentLink This link is going to enable the receiver to make changes
     * to the appointment record.
     * @param \EA\Engine\Types\Email $recipientEmail The recipient email address.
     * @param \EA\Engine\Types\Text $icsStream Stream contents of the ICS file.
     */
    public function sendAppointmentDetails(
        array $appointment,
        array $provider,
        array $service,
        array $customer,
        array $company,
        Text $title,
        Text $message,
        Url $appointmentLink,
        EmailAddress $recipientEmail,
        Text $icsStream,
        $isCustomer = true
    ) {
        switch ($company['date_format'])
        {
            case 'DMY':
                $date_format = 'd/m/Y';
                break;
            case 'MDY':
                $date_format = 'm/d/Y';
                break;
            case 'YMD':
                $date_format = 'Y/m/d';
                break;
            default:
                throw new \Exception('Invalid date_format value: ' . $company['date_format']);
        }

        switch ($company['time_format'])
        {
            case 'military':
                $timeFormat = 'H:i';
                break;
            case 'regular':
                $timeFormat = 'g:i A';
                break;
            default:
                throw new \Exception('Invalid time_format value: ' . $company['time_format']);
        }
        $providerTimezone = $this->getTimezoneDetails($provider['settings']['timezone']);
        // Prepare template replace array.
        $replaceArray = [
            '$email_title' => $title->get(),
            '$email_message' => $message->get(),
            '$appointment_service' => $service['name'],
            '$appointment_service_type' => $service['type'],
            '$logo_display' => $service['logo'] ? '': 'none',
            '$logo_url' => $service['logo'],
            '$user_type' => $isCustomer ? 'Your' : 'Customer',
            '$appointment_provider' => $provider['first_name'] . ' ' . $provider['last_name'],
            '$appointment_start_date' => date($date_format . ' ' . $timeFormat, strtotime($this->manage_timezone($appointment['start_datetime'], $providerTimezone['offset']))) . ' ('. $providerTimezone['abbr'] . ')',
            '$appointment_end_date' => date($date_format . ' ' . $timeFormat, strtotime($this->manage_timezone($appointment['end_datetime'], $providerTimezone['offset']))) . ' ('. $providerTimezone['abbr'] . ')',
            '$appointment_link' => $appointmentLink->get(),
            '$company_link' => $company['company_link'],
            '$company_name' => $company['company_name'],
            '$user_name' => $isCustomer ? $customer['first_name'] . ' ' . $customer['last_name'] : $provider['first_name'] . ' ' . $provider['last_name'],
            '$customer_name' => $customer['first_name'] . ' ' . $customer['last_name'],
            '$customer_email' => $customer['email'],
            '$customer_phone' => $customer['phone_number'],
            '$customer_address' => $customer['address'],
            '$outlook_url' => 'https://outlook.live.com/owa/?path=/calendar/action/compose&rru=addevent&startdt='.$this->getFormattedTime($appointment['start_datetime']).'&enddt='.$this->getFormattedTime($appointment['end_datetime']).'&subject='.str_replace(' ', '+', $service['name']).'&location='.str_replace(' ', '+', $service['location']),
            '$google_url' => 'https://calendar.google.com/calendar/render?action=TEMPLATE&text='.str_replace(' ', '+', $service['name']).'&dates='.$this->getFormattedTime($appointment['start_datetime']).'Z'.'/'.$this->getFormattedTime($appointment['end_datetime']).'Z'.'&location='.str_replace(' ', '+', $service['location']),
            '$yahoo_url' => 'https://calendar.yahoo.com/?v=60&st='.$this->getFormattedTime($appointment['start_datetime']).'Z'.'&et='.$this->getFormattedTime($appointment['end_datetime']).'Z'.'&title='.str_replace(' ', '+', $service['name']).'&in_loc='.str_replace(' ', '+', $service['location']),

            // Translations
            'Appointment Details' => $this->framework->lang->line('appointment_details_title'),
            'Service' => $this->framework->lang->line('service'),
            'Provider' => $this->framework->lang->line('provider'),
            'Start' => $this->framework->lang->line('start'),
            'End' => $this->framework->lang->line('end'),
            'Customer Details' => $this->framework->lang->line('customer_details_title'),
            'Name' => $this->framework->lang->line('name'),
            'Email' => $this->framework->lang->line('email'),
            'Phone' => $this->framework->lang->line('phone'),
            'Address' => $this->framework->lang->line('address'),
            'Appointment Link' => $this->framework->lang->line('appointment_link_title')
        ];

        $html = file_get_contents(__DIR__ . '/../../application/views/emails/appointment_details.php');
        $html = $this->_replaceTemplateVariables($replaceArray, $html);

        $mailer = $this->_createMailer();

        $mailer->From = $company['company_email'];
        $mailer->FromName = 'Vaetas Calendar';
        $mailer->AddAddress($recipientEmail->get());
        $mailer->Subject = $title->get();
        $mailer->Body = $html;

        $mailer->addStringAttachment($icsStream->get(), 'invitation.ics');

        if ( ! $mailer->Send())
        {
            throw new \RuntimeException('Email could not been sent. Mailer Error (Line ' . __LINE__ . '): '
                . $mailer->ErrorInfo);
        }
    }

    /**
     * Send an email notification to both provider and customer on appointment removal.
     *
     * Whenever an appointment is cancelled or removed, both the provider and customer
     * need to be informed. This method sends the same email twice.
     *
     * <strong>IMPORTANT!</strong> This method's arguments should be taken
     * from database before the appointment record is deleted.
     *
     * @param array $appointment The record data of the removed appointment.
     * @param array $provider The record data of the appointment provider.
     * @param array $service The record data of the appointment service.
     * @param array $customer The record data of the appointment customer.
     * @param array $company Some settings that are required for this function. By now this array must contain
     * the following values: "company_link", "company_name", "company_email".
     * @param \EA\Engine\Types\Email $recipientEmail The email address of the email recipient.
     * @param \EA\Engine\Types\Text $reason The reason why the appointment is deleted.
     */
    public function sendDeleteAppointment(
        array $appointment,
        array $provider,
        array $service,
        array $customer,
        array $company,
        EmailAddress $recipientEmail,
        Text $reason
    ) {
        switch ($company['date_format'])
        {
            case 'DMY':
                $date_format = 'd/m/Y';
                break;
            case 'MDY':
                $date_format = 'm/d/Y';
                break;
            case 'YMD':
                $date_format = 'Y/m/d';
                break;
            default:
                throw new \Exception('Invalid date_format value: ' . $company['date_format']);
        }

        switch ($company['time_format'])
        {
            case 'military':
                $timeFormat = 'H:i';
                break;
            case 'regular':
                $timeFormat = 'g:i A';
                break;
            default:
                throw new \Exception('Invalid time_format value: ' . $company['time_format']);
        }
        $providerTimezone = $this->getTimezoneDetails($provider['settings']['timezone']);

        // Prepare email template data.
        $replaceArray = [
            '$email_title' => $this->framework->lang->line('appointment_cancelled_title'),
            '$email_message' => $this->framework->lang->line('appointment_removed_from_schedule'),
            '$appointment_service' => $service['name'],
            '$appointment_provider' => $provider['first_name'] . ' ' . $provider['last_name'],
            '$appointment_date' => date($date_format . ' ' . $timeFormat, strtotime($this->manage_timezone($appointment['start_datetime'], $providerTimezone['offset']))) . ' ('. $providerTimezone['abbr'] . ')',
            '$appointment_duration' => $service['duration'] . ' ' . $this->framework->lang->line('minutes'),
            '$company_link' => $company['company_link'],
            '$company_name' => $company['company_name'],
            '$customer_name' => $customer['first_name'] . ' ' . $customer['last_name'],
            '$customer_email' => $customer['email'],
            '$customer_phone' => $customer['phone_number'],
            '$customer_address' => $customer['address'],
            '$reason' => $reason->get(),

            // Translations
            'Appointment Details' => $this->framework->lang->line('appointment_details_title'),
            'Service' => $this->framework->lang->line('service'),
            'Provider' => $this->framework->lang->line('provider'),
            'Date' => $this->framework->lang->line('start'),
            'Duration' => $this->framework->lang->line('duration'),
            'Customer Details' => $this->framework->lang->line('customer_details_title'),
            'Name' => $this->framework->lang->line('name'),
            'Email' => $this->framework->lang->line('email'),
            'Phone' => $this->framework->lang->line('phone'),
            'Address' => $this->framework->lang->line('address'),
            'Reason' => $this->framework->lang->line('reason')
        ];

        $html = file_get_contents(__DIR__ . '/../../application/views/emails/delete_appointment.php');
        $html = $this->_replaceTemplateVariables($replaceArray, $html);

        $mailer = $this->_createMailer();

        // Send email to recipient.
        $mailer->From = $company['company_email'];
        $mailer->FromName = 'Vaetas Calendar';
        $mailer->AddAddress($recipientEmail->get()); // "Name" argument crushes the phpmailer class.
        $mailer->Subject = $this->framework->lang->line('appointment_cancelled_title');
        $mailer->Body = $html;

        if ( ! $mailer->Send())
        {
            throw new \RuntimeException('Email could not been sent. Mailer Error (Line ' . __LINE__ . '): '
                . $mailer->ErrorInfo);
        }
    }

    /**
     * This method sends an email with the new password of a user.
     *
     * @param \EA\Engine\Types\NonEmptyText $password Contains the new password.
     * @param \EA\Engine\Types\Email $recipientEmail The receiver's email address.
     * @param array $company The company settings to be included in the email.
     */
    public function sendPassword(NonEmptyText $password, EmailAddress $recipientEmail, array $company)
    {
        $replaceArray = [
            '$email_title' => $this->framework->lang->line('new_account_password'),
            '$email_message' => $this->framework->lang->line('new_password_is'),
            '$company_name' => $company['company_name'],
            '$company_email' => $company['company_email'],
            '$company_link' => $company['company_link'],
            '$password' => '<strong>' . $password->get() . '</strong>'
        ];

        $html = file_get_contents(__DIR__ . '/../../application/views/emails/new_password.php');
        $html = $this->_replaceTemplateVariables($replaceArray, $html);

        $mailer = $this->_createMailer();

        $mailer->From = $company['company_email'];
        $mailer->FromName = 'Vaetas Calendar';
        $mailer->AddAddress($recipientEmail->get()); // "Name" argument crushes the phpmailer class.
        $mailer->Subject = $this->framework->lang->line('new_account_password');
        $mailer->Body = $html;

        if ( ! $mailer->Send())
        {
            throw new \RuntimeException('Email could not been sent. Mailer Error (Line ' . __LINE__ . '): '
                . $mailer->ErrorInfo);
        }
    }

    /**
     * Create PHP Mailer Instance
     *
     * @return \PHPMailer
     */
    protected function _createMailer()
    {
        $mailer = new \PHPMailer;

        if ($this->config['protocol'] === 'smtp')
        {
            $mailer->isSMTP();
            $mailer->Host = $this->config['smtp_host'];
            $mailer->SMTPAuth = TRUE;
            $mailer->Username = $this->config['smtp_user'];
            $mailer->Password = $this->config['smtp_pass'];
            $mailer->SMTPSecure = $this->config['smtp_crypto'];
            $mailer->Port = $this->config['smtp_port'];
        }

        $mailer->IsHTML($this->config['mailtype'] === 'html');
        $mailer->CharSet = $this->config['charset'];

        return $mailer;
    }

    function manage_timezone($time, $timezone) {
        $offset = substr($timezone, 1);
        return substr($timezone, 0, 1) === '+' ? $this->addTime($time, $offset) : $this->diff($time, $offset);
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

    function loadTimezones() {
        $this->timezones = [['abbr'=>"ACDT", 'name'=>"Australian Central Daylight Savings Time", 'offset'=>"+10:30"], [
            'abbr'=>"ACST",
            'name'=>"Australian Central Standard Time",
            'offset'=>"+09:30"
        ], ['abbr'=>"ACT", 'name'=>"Acre Time", 'offset'=>"−05:00"], [
            'abbr'=>"ACWST",
            'name'=>"Australian Central Western Standard Time",
            'offset'=>"+08:45"
        ], ['abbr'=>"ADT", 'name'=>"Atlantic  Daylight Time", 'offset'=>"−03:00"], [
            'abbr'=>"AEDT",
            'name'=>"Australian Eastern Daylight Savings Time",
            'offset'=>"+11:00"
        ], ['abbr'=>"AEST", 'name'=>"Australian Eastern Standard Time", 'offset'=>"+10:00"], [
            'abbr'=>"AFT",
            'name'=>"Afghanistan Time",
            'offset'=>"+04:30"
        ], ['abbr'=>"AKDT", 'name'=>"Alaska Daylight  Time", 'offset'=>"−08:00"], [
            'abbr'=>"AKST",
            'name'=>"Alaska Standard  Time",
            'offset'=>"−09:00"
        ], ['abbr'=>"AMST", 'name'=>"Amazon Summer Time", 'offset'=>"−03:00"], [
            'abbr'=>"AMT",
            'name'=>"Amazon Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"AMT", 'name'=>"Armenia Time", 'offset'=>"+04:00"], [
            'abbr'=>"ART",
            'name'=>"Argentina Time",
            'offset'=>"−03:00"
        ], ['abbr'=>"AST", 'name'=>"Arabia Standard  Time", 'offset'=>"+03:00"], [
            'abbr'=>"AST",
            'name'=>"Atlantic  Standard Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"AWST", 'name'=>"Australian Western Standard Time", 'offset'=>"+08:00"], [
            'abbr'=>"AZOST",
            'name'=>"Azores Summer Time",
            'offset'=>"+00:00"
        ], ['abbr'=>"AZOT", 'name'=>"Azores Standard Time", 'offset'=>"−01:00"], [
            'abbr'=>"AZT",
            'name'=>"Azerbaijan Time",
            'offset'=>"+04:00"
        ], ['abbr'=>"BDT", 'name'=>"Brunei Time", 'offset'=>"+08:00"], [
            'abbr'=>"BIOT",
            'name'=>"British  Indian Ocean Time",
            'offset'=>"+06:00"
        ], ['abbr'=>"BIT", 'name'=>"Baker Island Time", 'offset'=>"−12:00"], [
            'abbr'=>"BOT",
            'name'=>"Bolivia Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"BRST", 'name'=>"Brasília  Summer Time", 'offset'=>"−02:00"], [
            'abbr'=>"BRT",
            'name'=>"Brasilia Time",
            'offset'=>"−03:00"
        ], ['abbr'=>"BST", 'name'=>"Bangladesh Standard Time", 'offset'=>"+06:00"], [
            'abbr'=>"BST",
            'name'=>"Bougainville  Standard Time",
            'offset'=>"+11:00"
        ], ['abbr'=>"BST", 'name'=>"British Summer Time", 'offset'=>"+01:00"], [
            'abbr'=>"BTT",
            'name'=>"Bhutan Time",
            'offset'=>"+06:00"
        ], ['abbr'=>"CAT", 'name'=>"Central Africa Time", 'offset'=>"+02:00"], [
            'abbr'=>"CCT",
            'name'=>"Cocos Islands Time",
            'offset'=>"+06:30"
        ], ['abbr'=>"CDT", 'name'=>"Central Daylight  Time", 'offset'=>"−05:00"], [
            'abbr'=>"CDT",
            'name'=>"Cuba Daylight Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"CEST", 'name'=>"Central European  Summer Time", 'offset'=>"+02:00"], [
            'abbr'=>"CET",
            'name'=>"Central European Time",
            'offset'=>"+01:00"
        ], ['abbr'=>"CHADT", 'name'=>"Chatham Daylight  Time", 'offset'=>"+13:45"], [
            'abbr'=>"CHAST",
            'name'=>"Chatham Standard  Time",
            'offset'=>"+12:45"
        ], ['abbr'=>"CHOT", 'name'=>"Choibalsan  Standard Time", 'offset'=>"+08:00"], [
            'abbr'=>"CHOST",
            'name'=>"Choibalsan  Summer Time",
            'offset'=>"+09:00"
        ], ['abbr'=>"CHST", 'name'=>"Chamorro  Standard Time", 'offset'=>"+10:00"], [
            'abbr'=>"CHUT",
            'name'=>"Chuuk Time",
            'offset'=>"+10:00"
        ], ['abbr'=>"CIST", 'name'=>"Clipperton Island Standard Time", 'offset'=>"−08:00"], [
            'abbr'=>"CIT",
            'name'=>"Central  Indonesia Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"CKT", 'name'=>"Cook Island Time", 'offset'=>"−10:00"], [
            'abbr'=>"CLST",
            'name'=>"Chile Summer Time",
            'offset'=>"−03:00"
        ], ['abbr'=>"CLT", 'name'=>"Chile Standard  Time", 'offset'=>"−04:00"], [
            'abbr'=>"COST",
            'name'=>"Colombia Summer  Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"COT", 'name'=>"Colombia Time", 'offset'=>"−05:00"], [
            'abbr'=>"CST",
            'name'=>"Central Standard  Time",
            'offset'=>"−06:00"
        ], ['abbr'=>"CST", 'name'=>"China Standard  Time", 'offset'=>"+08:00"], [
            'abbr'=>"CST",
            'name'=>"Cuba Standard Time",
            'offset'=>"−05:00"
        ], ['abbr'=>"CT", 'name'=>"China Time", 'offset'=>"+08:00"], [
            'abbr'=>"CVT",
            'name'=>"Cape Verde Time",
            'offset'=>"−01:00"
        ], ['abbr'=>"CWST", 'name'=>"Central  Western Standard Timel", 'offset'=>"+08:45"], [
            'abbr'=>"CXT",
            'name'=>"Christmas Island  Time",
            'offset'=>"+07:00"
        ], ['abbr'=>"DAVT", 'name'=>"Davis Time", 'offset'=>"+07:00"], [
            'abbr'=>"DDUT",
            'name'=>"Dumont d'Urville Time",
            'offset'=>"+10:00"
        ], [
            'abbr'=>"DFT",
            'name'=>"AIX-specific equivalent of Central  European Time[NB 1]",
            'offset'=>"+01:00"
        ], ['abbr'=>"EASST", 'name'=>"Easter  Island Summer Time", 'offset'=>"−05:00"], [
            'abbr'=>"EAST",
            'name'=>"Easter  Island Standard Time",
            'offset'=>"−06:00"
        ], ['abbr'=>"EAT", 'name'=>"East Africa Time", 'offset'=>"+03:00"], [
            'abbr'=>"ECT",
            'name'=>"Eastern  Caribbean Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"ECT", 'name'=>"Ecuador Time", 'offset'=>"−05:00"], [
            'abbr'=>"EDT",
            'name'=>"Eastern Daylight  Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"EEST", 'name'=>"Eastern European  Summer Time", 'offset'=>"+03:00"], [
            'abbr'=>"EET",
            'name'=>"Eastern European Time",
            'offset'=>"+02:00"
        ], ['abbr'=>"EGST", 'name'=>"Eastern  Greenland Summer Time", 'offset'=>"+00:00"], [
            'abbr'=>"EGT",
            'name'=>"Eastern  Greenland Time",
            'offset'=>"−01:00"
        ], ['abbr'=>"EIT", 'name'=>"Eastern  Indonesian Time", 'offset'=>"+09:00"], [
            'abbr'=>"EST",
            'name'=>"Eastern Standard Time",
            'offset'=>"−05:00"
        ], ['abbr'=>"FET", 'name'=>"Further-eastern  European Time", 'offset'=>"+03:00"], [
            'abbr'=>"FJT",
            'name'=>"Fiji Time",
            'offset'=>"+12:00"
        ], ['abbr'=>"FKST", 'name'=>"Falkland  Islands Summer Time", 'offset'=>"−03:00"], [
            'abbr'=>"FKT",
            'name'=>"Falkland Islands  Time",
            'offset'=>"−04:00"
        ], ['abbr'=>"FNT", 'name'=>"Fernando  de Noronha Time", 'offset'=>"−02:00"], [
            'abbr'=>"GALT",
            'name'=>"Galápagos Time",
            'offset'=>"−06:00"
        ], ['abbr'=>"GAMT", 'name'=>"Gambier Islands  Time", 'offset'=>"−09:00"], [
            'abbr'=>"GET",
            'name'=>"Georgia Standard  Time",
            'offset'=>"+04:00"
        ], ['abbr'=>"GFT", 'name'=>"French Guiana Time", 'offset'=>"−03:00"], [
            'abbr'=>"GILT",
            'name'=>"Gilbert Island  Time",
            'offset'=>"+12:00"
        ], ['abbr'=>"GIT", 'name'=>"Gambier Island  Time", 'offset'=>"−09:00"], [
            'abbr'=>"GMT",
            'name'=>"Greenwich Mean Time",
            'offset'=>"+00:00"
        ], ['abbr'=>"GST", 'name'=>"South Georgia and the South Sandwich  Islands Time", 'offset'=>"−02:00"], [
            'abbr'=>"GST",
            'name'=>"Gulf Standard Time",
            'offset'=>"+04:00"
        ], ['abbr'=>"GYT", 'name'=>"Guyana Time", 'offset'=>"−04:00"], [
            'abbr'=>"HDT",
            'name'=>"Hawaii–Aleutian Daylight Time",
            'offset'=>"−09:00"
        ], [
            'abbr'=>"HAEC",
            'name'=>"Heure Avancée d'Europe Centrale French-language 'name'  for CEST",
            'offset'=>"+02:00"
        ], ['abbr'=>"HST", 'name'=>"Hawaii–Aleutian Standard Time", 'offset'=>"−10:00"], [
            'abbr'=>"HKT",
            'name'=>"Hong Kong Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"HMT", 'name'=>"Heard  and McDonald Islands Time", 'offset'=>"+05:00"], [
            'abbr'=>"HOVST",
            'name'=>"Khovd Summer Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"HOVT", 'name'=>"Khovd Standard Time", 'offset'=>"+07:00"], [
            'abbr'=>"ICT",
            'name'=>"Indochina Time",
            'offset'=>"+07:00"
        ], ['abbr'=>"IDLW", 'name'=>"International Day Line West time zone", 'offset'=>"−12:00"], [
            'abbr'=>"IDT",
            'name'=>"Israel Daylight  Time",
            'offset'=>"+03:00"
        ], ['abbr'=>"IOT", 'name'=>"Indian Ocean Time", 'offset'=>"+03:00"], [
            'abbr'=>"IRDT",
            'name'=>"Iran Daylight Time",
            'offset'=>"+04:30"
        ], ['abbr'=>"IRKT", 'name'=>"Irkutsk Time", 'offset'=>"+08:00"], [
            'abbr'=>"IRST",
            'name'=>"Iran Standard Time",
            'offset'=>"+03:30"
        ], ['abbr'=>"IST", 'name'=>"Indian Standard Time", 'offset'=>"+05:30"], [
            'abbr'=>"IST",
            'name'=>"Irish Standard  Time",
            'offset'=>"+01:00"
        ], ['abbr'=>"IST", 'name'=>"Israel Standard Time", 'offset'=>"+02:00"], [
            'abbr'=>"JST",
            'name'=>"Japan Standard Time",
            'offset'=>"+09:00"
        ], ['abbr'=>"KALT", 'name'=>"Kaliningrad Time", 'offset'=>"+02:00"], [
            'abbr'=>"KGT",
            'name'=>"Kyrgyzstan Time",
            'offset'=>"+06:00"
        ], ['abbr'=>"KOST", 'name'=>"Kosrae Time", 'offset'=>"+11:00"], [
            'abbr'=>"KRAT",
            'name'=>"Krasnoyarsk Time",
            'offset'=>"+07:00"
        ], ['abbr'=>"KST", 'name'=>"Korea Standard  Time", 'offset'=>"+09:00"], [
            'abbr'=>"LHST",
            'name'=>"Lord Howe Standard Time",
            'offset'=>"+10:30"
        ], ['abbr'=>"LHST", 'name'=>"Lord Howe Summer Time", 'offset'=>"+11:00"], [
            'abbr'=>"LINT",
            'name'=>"Line Islands Time",
            'offset'=>"+14:00"
        ], ['abbr'=>"MAGT", 'name'=>"Magadan Time", 'offset'=>"+12:00"], [
            'abbr'=>"MART",
            'name'=>"Marquesas  Islands Time",
            'offset'=>"−09:30"
        ], ['abbr'=>"MAWT", 'name'=>"Mawson Station Time", 'offset'=>"+05:00"], [
            'abbr'=>"MDT",
            'name'=>"Mountain  Daylight Time",
            'offset'=>"−06:00"
        ], ['abbr'=>"MET", 'name'=>"Middle European  Time Same zone as CET", 'offset'=>"+01:00"], [
            'abbr'=>"MEST",
            'name'=>"Middle  European Summer Time Same zone as CEST",
            'offset'=>"+02:00"
        ], ['abbr'=>"MHT", 'name'=>"Marshall Islands  Time", 'offset'=>"+12:00"], [
            'abbr'=>"MIST",
            'name'=>"Macquarie Island Station Time",
            'offset'=>"+11:00"
        ], ['abbr'=>"MIT", 'name'=>"Marquesas  Islands Time", 'offset'=>"−09:30"], [
            'abbr'=>"MMT",
            'name'=>"Myanmar Standard Time",
            'offset'=>"+06:30"
        ], ['abbr'=>"MSK", 'name'=>"Moscow Time", 'offset'=>"+03:00"], [
            'abbr'=>"MST",
            'name'=>"Malaysia  Standard Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"MST", 'name'=>"Mountain  Standard Time", 'offset'=>"−07:00"], [
            'abbr'=>"MUT",
            'name'=>"Mauritius Time",
            'offset'=>"+04:00"
        ], ['abbr'=>"MVT", 'name'=>"Maldives Time", 'offset'=>"+05:00"], [
            'abbr'=>"MYT",
            'name'=>"Malaysia Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"NCT", 'name'=>"New Caledonia Time", 'offset'=>"+11:00"], [
            'abbr'=>"NDT",
            'name'=>"Newfoundland  Daylight Time",
            'offset'=>"−02:30"
        ], ['abbr'=>"NFT", 'name'=>"Norfolk Island  Time", 'offset'=>"+11:00"], [
            'abbr'=>"NPT",
            'name'=>"Nepal Time",
            'offset'=>"+05:45"
        ], ['abbr'=>"NST", 'name'=>"Newfoundland  Standard Time", 'offset'=>"−03:30"], [
            'abbr'=>"NT",
            'name'=>"Newfoundland Time",
            'offset'=>"−03:30"
        ], ['abbr'=>"NUT", 'name'=>"Niue Time", 'offset'=>"−11:00"], [
            'abbr'=>"NZDT",
            'name'=>"New  Zealand Daylight Time",
            'offset'=>"+13:00"
        ], ['abbr'=>"NZST", 'name'=>"New  Zealand Standard Time", 'offset'=>"+12:00"], [
            'abbr'=>"OMST",
            'name'=>"Omsk Time",
            'offset'=>"+06:00"
        ], ['abbr'=>"ORAT", 'name'=>"Oral Time", 'offset'=>"+05:00"], [
            'abbr'=>"PDT",
            'name'=>"Pacific Daylight  Time",
            'offset'=>"−07:00"
        ], ['abbr'=>"PET", 'name'=>"Peru Time", 'offset'=>"−05:00"], [
            'abbr'=>"PETT",
            'name'=>"Kamchatka Time",
            'offset'=>"+12:00"
        ], ['abbr'=>"PGT", 'name'=>"Papua New Guinea  Time", 'offset'=>"+10:00"], [
            'abbr'=>"PHOT",
            'name'=>"Phoenix Island  Time",
            'offset'=>"+13:00"
        ], ['abbr'=>"PHT", 'name'=>"Philippine Time", 'offset'=>"+08:00"], [
            'abbr'=>"PKT",
            'name'=>"Pakistan Standard Time",
            'offset'=>"+05:00"
        ], ['abbr'=>"PMDT", 'name'=>"Saint Pierre and Miquelon Daylight Time", 'offset'=>"−02:00"], [
            'abbr'=>"PMST",
            'name'=>"Saint Pierre and Miquelon Standard Time",
            'offset'=>"−03:00"
        ], ['abbr'=>"PONT", 'name'=>"Pohnpei Standard  Time", 'offset'=>"+11:00"], [
            'abbr'=>"PST",
            'name'=>"Pacific Standard  Time",
            'offset'=>"−08:00"
        ], ['abbr'=>"PST", 'name'=>"Philippine Standard Time", 'offset'=>"+08:00"], [
            'abbr'=>"PYST",
            'name'=>"Paraguay Summer  Time",
            'offset'=>"−03:00"
        ], ['abbr'=>"PYT", 'name'=>"Paraguay Time", 'offset'=>"−04:00"], [
            'abbr'=>"RET",
            'name'=>"Réunion Time",
            'offset'=>"+04:00"
        ], ['abbr'=>"ROTT", 'name'=>"Rothera Research Station Time", 'offset'=>"−03:00"], [
            'abbr'=>"SAKT",
            'name'=>"Sakhalin Island  Time",
            'offset'=>"+11:00"
        ], ['abbr'=>"SAMT", 'name'=>"Samara Time", 'offset'=>"+04:00"], [
            'abbr'=>"SAST",
            'name'=>"South African Standard  Time",
            'offset'=>"+02:00"
        ], ['abbr'=>"SBT", 'name'=>"Solomon Islands  Time", 'offset'=>"+11:00"], [
            'abbr'=>"SCT",
            'name'=>"Seychelles Time",
            'offset'=>"+04:00"
        ], ['abbr'=>"SDT", 'name'=>"Samoa Daylight  Time", 'offset'=>"−10:00"], [
            'abbr'=>"SGT",
            'name'=>"Singapore Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"SLST", 'name'=>"Sri Lanka Standard Time", 'offset'=>"+05:30"], [
            'abbr'=>"SRET",
            'name'=>"Srednekolymsk Time",
            'offset'=>"+11:00"
        ], ['abbr'=>"SRT", 'name'=>"Suri'name' Time", 'offset'=>"−03:00"], [
            'abbr'=>"SST",
            'name'=>"Samoa Standard  Time",
            'offset'=>"−11:00"
        ], ['abbr'=>"SST", 'name'=>"Singapore Standard Time", 'offset'=>"+08:00"], [
            'abbr'=>"SYOT",
            'name'=>"Showa Station Time",
            'offset'=>"+03:00"
        ], ['abbr'=>"TAHT", 'name'=>"Tahiti Time", 'offset'=>"−10:00"], [
            'abbr'=>"THA",
            'name'=>"Thailand  Standard Time",
            'offset'=>"+07:00"
        ], ['abbr'=>"TFT", 'name'=>"French Southern and Antarctic Time", 'offset'=>"+05:00"], [
            'abbr'=>"TJT",
            'name'=>"Tajikistan Time",
            'offset'=>"+05:00"
        ], ['abbr'=>"TKT", 'name'=>"Tokelau Time", 'offset'=>"+13:00"], [
            'abbr'=>"TLT",
            'name'=>"Timor Leste Time",
            'offset'=>"+09:00"
        ], ['abbr'=>"TMT", 'name'=>"Turkmenistan Time", 'offset'=>"+05:00"], [
            'abbr'=>"TRT",
            'name'=>"Turkey Time",
            'offset'=>"+03:00"
        ], ['abbr'=>"TOT", 'name'=>"Tonga Time", 'offset'=>"+13:00"], [
            'abbr'=>"TVT",
            'name'=>"Tuvalu Time",
            'offset'=>"+12:00"
        ], ['abbr'=>"ULAST", 'name'=>"Ulaanbaatar  Summer Time", 'offset'=>"+09:00"], [
            'abbr'=>"ULAT",
            'name'=>"Ulaanbaatar  Standard Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"UTC", 'name'=>"Coordinated Universal  Time", 'offset'=>"+00:00"], [
            'abbr'=>"UYST",
            'name'=>"Uruguay Summer  Time",
            'offset'=>"−02:00"
        ], ['abbr'=>"UYT", 'name'=>"Uruguay Standard  Time", 'offset'=>"−03:00"], [
            'abbr'=>"UZT",
            'name'=>"Uzbekistan Time",
            'offset'=>"+05:00"
        ], ['abbr'=>"VET", 'name'=>"Venezuelan  Standard Time", 'offset'=>"−04:00"], [
            'abbr'=>"VLAT",
            'name'=>"Vladivostok Time",
            'offset'=>"+10:00"
        ], ['abbr'=>"VOLT", 'name'=>"Volgograd Time", 'offset'=>"+04:00"], [
            'abbr'=>"VOST",
            'name'=>"Vostok Station Time",
            'offset'=>"+06:00"
        ], ['abbr'=>"VUT", 'name'=>"Vanuatu Time", 'offset'=>"+11:00"], [
            'abbr'=>"WAKT",
            'name'=>"Wake Island Time",
            'offset'=>"+12:00"
        ], ['abbr'=>"WAST", 'name'=>"West Africa  Summer Time", 'offset'=>"+02:00"], [
            'abbr'=>"WAT",
            'name'=>"West Africa Time",
            'offset'=>"+01:00"
        ], ['abbr'=>"WEST", 'name'=>"Western European  Summer Time", 'offset'=>"+01:00"], [
            'abbr'=>"WET",
            'name'=>"Western European Time",
            'offset'=>"+00:00"
        ], ['abbr'=>"WIT", 'name'=>"Western  Indonesian Time", 'offset'=>"+07:00"], [
            'abbr'=>"WST",
            'name'=>"Western Standard  Time",
            'offset'=>"+08:00"
        ], ['abbr'=>"YAKT", 'name'=>"Yakutsk Time", 'offset'=>"+09:00"], [
            'abbr'=>"YEKT",
            'name'=>"Yekaterinburg Time",
            'offset'=>"+05:00"
        ]];
    }

    function getTimezoneDetails($timezoneOffset) {
        foreach ($this->timezones as $zone) {
            if($zone['offset'] === $timezoneOffset) {
                return $zone;
            }
        }
    }

    function getFormattedTime($dateTime) {
        return str_replace(' ', 'T', str_replace(':', '', str_replace('-', '', $dateTime)));
    }
}
