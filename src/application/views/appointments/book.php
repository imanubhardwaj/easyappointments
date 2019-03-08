<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#35A768">

    <title><?= lang('page_title') . ' ' .  $company_name ?></title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-qtip/jquery.qtip.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/cookieconsent/cookieconsent.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/frontend.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">
    <script src="<?= asset_url('assets/ext/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.js') ?>"></script>
    <script src="../../../assets/ext/moment/moment.min.js"></script>
    <?php
    $serviceId          = $_GET['serviceId'];
    $providerId         = $_GET['providerId'];
    $available_services = array_values(array_filter($available_services, function ($e) use ($serviceId) {
        return $e['id'] === $serviceId;
    }));

    $available_providers = array_values(array_filter($available_providers, function ($e) use ($providerId) {
        return $e['id'] === $providerId;
    }));

    if ($serviceId === null || $providerId === null || count($available_services) === 0 || count($available_providers) === 0) {
        $url = sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            $_SERVER['REQUEST_URI']
        ). 'user/login';
        header('Location: '.$url);
    }
    ?>
    <script type="text/javascript">
        function getParameterByName(name) {
            var val = (location.search.split(name + '=')[1] || '').split('&')[0];

            if (val.indexOf('?') >= 0) {
                val = val.split('?')[0];
            }

            return val;
        }

        var user_id = getParameterByName('uid');
        var video_id = getParameterByName('vid');
        var email_id = getParameterByName('eid');
    </script>
    <script type="text/javascript">
        var geoData = {};
        $.ajax({
            type: 'GET',
            url: 'https://geoip-devslane.herokuapp.com/json/',
            dataType: 'jsonp',
            crossDomain: true,
            async: false,
            success: function (res) {
                geoData = res;
            }
        });
    </script>
</head>

<body>
    <div id="main" class="container">
        <div class="wrapper row">
            <div id="book-appointment-wizard" class="col-xs-12 col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2">

                <!-- FRAME TOP BAR -->

                <div id="header">
                    <?php if ($available_services[0]['logo']): ?>
                    <img src="<?php echo $available_services[0]['logo'] ?>" class="logo">
                    <?php endif; ?>
                    <?php if (!$available_services[0]['logo']): ?>
                        <div>&nbsp;</div>
                    <?php endif; ?>
                    <span id="company-name" class="hide"><?= $company_name ?></span>

                    <div id="steps">
                        <div id="step-1" class="book-step active-step" title="<?= lang('step_one_title') ?>">
                            <strong>1</strong>
                        </div>

                        <div id="step-2" class="book-step" title="<?= lang('step_two_title') ?>">
                            <strong>2</strong>
                        </div>
                        <div id="step-3" class="book-step" title="<?= lang('step_three_title') ?>">
                            <strong>3</strong>
                        </div>
                        <div id="step-4" class="book-step" title="<?= lang('step_four_title') ?>">
                            <strong>4</strong>
                        </div>
                    </div>
                </div>

                <?php if ($manage_mode): ?>
                <div id="cancel-appointment-frame" class="booking-header-bar row">
                    <div class="col-xs-12 col-sm-10">
                        <p><?= lang('cancel_appointment_hint') ?></p>
                    </div>
                    <div class="col-xs-12 col-sm-2">
                        <form id="cancel-appointment-form" method="post"
                              action="<?= site_url('appointments/cancel/' . $appointment_data['hash']) ?>">
                            <input type="hidden" name="csrfToken" value="<?= $this->security->get_csrf_hash() ?>" />
                            <textarea name="cancel_reason" style="display:none"></textarea>
                            <button id="cancel-appointment" class="btn btn-default btn-sm"><?= lang('cancel') ?></button>
                        </form>
                    </div>
                </div>
                <div class="booking-header-bar row">
                    <div class="col-xs-12 col-sm-10">
                        <p><?= lang('delete_personal_information_hint') ?></p>
                    </div>
                    <div class="col-xs-12 col-sm-2">
                        <button id="delete-personal-information" class="btn btn-danger btn-sm"><?= lang('delete') ?></button>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                    if (isset($exceptions)) {
                        echo '<div style="margin: 10px">';
                        echo '<h4>' . lang('unexpected_issues') . '</h4>';
                        foreach($exceptions as $exception) {
                            echo exceptionToHtml($exception);
                        }
                        echo '</div>';
                    }
                ?>

                <!-- SELECT SERVICE AND PROVIDER -->

                <div id="wizard-frame-1" class="wizard-frame">
                    <div class="frame-container">
                        <h4 class="frame-title">Book Appointment</h4>

                        <div style="display: flex; flex-direction: column">
                            <label for="provider-name" style="margin-bottom: 0"><strong>With</strong></label>
                            <input type="text" id="provider-name" disabled
                                   value="<?php echo $available_providers[0]['first_name'] . ' ' .
                                       $available_providers[0]['last_name'] ?>">
                            <br>
                        </div>

                        <div style="display: flex; flex-direction: column">
                            <label for="service-name" style="margin-bottom: 0"><strong>For</strong></label>
                            <input type="text" id="service-name" disabled
                                   value="<?php echo $available_services[0]['name'] ?>">
                            <br>
                        </div>

                        <?php if ($available_services[0]['description']): ?>
                            <div style="display: flex; flex-direction: column">
                                <label for="service-description" style="margin-bottom: 0">
                                    <strong>Description</strong>
                                </label>
                                <input type="text" id="service-description" disabled
                                       value="<?php echo $available_services[0]['description'] ?>">
                                <br>
                            </div>
                        <?php endif ?>

                        <div style="display: flex; justify-content: center; align-items: center">
                        <?php if ($available_services[0]['type']): ?>
                        <div style="display: flex; flex-direction: column; flex: 1 1 100%">
                            <label for="service-type" style="margin-bottom: 0"><strong>Appointment Type</strong></label>
                            <input type="text" id="service-type" disabled
                                   value="<?php echo ucwords(str_replace('_', ' ', $available_services[0]['type'])) ?>">
                            <br>
                        </div>
                        <?php endif ?>

                        <?php if ($available_services[0]['duration']): ?>
                            <?php if($available_services[0]['type']): ?>
                                <div class="spacer"></div>
                            <?php endif; ?>
                            <div style="display: flex; flex-direction: column; flex: 1 1 100%">
                            <label for="service-duration" style="margin-bottom: 0"><strong>Duration</strong></label>
                            <input type="text" id="service-duration" disabled
                                   value="<?php echo $available_services[0]['duration'] ?> minutes">
                            <br>
                        </div>
                        <?php endif ?>

                        <?php if ($available_services[0]['price'] >= 1): ?>
                            <?php if($available_services[0]['type'] || $available_services[0]['duration']): ?>
                                <div class="spacer"></div>
                            <?php endif; ?>
                            <div style="display: flex; flex-direction: column; flex: 1 1 100%">
                                <label for="service-price" style="margin-bottom: 0">
                                    <strong>Price</strong>
                                </label>
                                <input type="text" id="service-price" disabled
                                       value="$ <?php echo $available_services[0]['price'] ?>">
                                <br>
                            </div>
                        <?php endif ?>
                        </div>
                    </div>

                    <div class="command-buttons">
                        <button type="button" id="button-next-1" class="btn button-next btn-primary"
                                data-step_index="1">
                            <?= lang('next') ?>
                            <span class="glyphicon glyphicon-forward"></span>
                        </button>
                    </div>
                </div>

                <!-- SELECT APPOINTMENT DATE -->

                <div id="wizard-frame-2" class="wizard-frame" style="display:none;">
                    <div class="frame-container">

                        <h4 class="frame-title"><?= lang('step_two_title') ?></h4>

                        <div class="frame-content row">
                            <div class="col-xs-12 col-sm-6">
                                <div id="select-date"></div>
                            </div>

                            <div class="col-xs-12 col-sm-6">
                                <div id="no-time">No time slots available for this day. Please select a different date.</div>
                                <div class="f-row" style="justify-content: space-around" id="time-select">
                                    <div class="f-col">
                                        <label for="available-hours">Select hour</label>
                                        <select id="available-hours"></select>
                                    </div>
                                    <div class="f-col" id="minutes-select">
                                        <label for="available-minutes">Select minute</label>
                                        <select id="available-minutes"></select>
                                    </div>
                                    <div id="no-minutes">No time slots available for this hour. Please select a different hour.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="command-buttons">
                        <button type="button" id="button-back-2" class="btn button-back btn-default"
                                data-step_index="2">
                            <span class="glyphicon glyphicon-backward"></span>
                            <?= lang('back') ?>
                        </button>
                        <button type="button" id="button-next-2" class="btn button-next btn-primary"
                                data-step_index="2">
                            <?= lang('next') ?>
                            <span class="glyphicon glyphicon-forward"></span>
                        </button>
                    </div>
                </div>

                <!-- ENTER CUSTOMER DATA -->

                <div id="wizard-frame-3" class="wizard-frame" style="display:none;">
                    <div class="frame-container">

                        <h3 class="frame-title"><?= lang('step_three_title') ?></h3>

                        <div class="frame-content row">
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label for="first-name" class="control-label"><?= lang('first_name') ?> *</label>
                                    <input type="text" id="first-name" class="required form-control" maxlength="100" />
                                </div>
                                <div class="form-group">
                                    <label for="last-name" class="control-label"><?= lang('last_name') ?> *</label>
                                    <input type="text" id="last-name" class="required form-control" maxlength="120" />
                                </div>
                                <div class="form-group">
                                    <label for="email" class="control-label"><?= lang('email') ?> *</label>
                                    <input type="text" id="email" class="required form-control" maxlength="120" />
                                </div>
                                <div class="form-group">
                                    <label for="phone-number" class="control-label"><?= lang('phone_number') ?> *</label>
                                    <input type="text" id="phone-number" class="required form-control" maxlength="60" />
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label for="address" class="control-label"><?= lang('address') ?></label>
                                    <input type="text" id="address" class="form-control" maxlength="120" />
                                </div>
                                <div class="form-group">
                                    <label for="city" class="control-label"><?= lang('city') ?></label>
                                    <input type="text" id="city" class="form-control" maxlength="120" />
                                </div>
                                <div class="form-group">
                                    <label for="zip-code" class="control-label"><?= lang('zip_code') ?></label>
                                    <input type="text" id="zip-code" class="form-control" maxlength="120" />
                                </div>
                                <div class="form-group">
                                    <label for="notes" class="control-label"><?= lang('notes') ?></label>
                                    <textarea id="notes" maxlength="500" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <?php if ($display_terms_and_conditions): ?>
                            <label>
                                <input type="checkbox" class="required" id="accept-to-terms-and-conditions">
                                <?= strtr(lang('read_and_agree_to_terms_and_conditions'),
                                    [
                                        '{$link}' => '<a href="#" data-toggle="modal" data-target="#terms-and-conditions-modal">',
                                        '{/$link}' => '</a>'
                                    ])
                                ?>
                            </label>
                            <br>
                            <?php endif ?>

                            <?php if ($display_privacy_policy): ?>
                            <label>
                                <input type="checkbox" class="required" id="accept-to-privacy-policy">
                                <?= strtr(lang('read_and_agree_to_privacy_policy'),
                                    [
                                        '{$link}' => '<a href="#" data-toggle="modal" data-target="#privacy-policy-modal">',
                                        '{/$link}' => '</a>'
                                    ])
                                ?>
                            </label>
                            <br>
                            <?php endif ?>

                            <span id="form-message" class="text-danger"><?= lang('fields_are_required') ?></span>
                        </div>
                    </div>

                    <div class="command-buttons">
                        <button type="button" id="button-back-3" class="btn button-back btn-default"
                                data-step_index="3"><span class="glyphicon glyphicon-backward"></span>
                            <?= lang('back') ?>
                        </button>
                        <button type="button" id="button-next-3" class="btn button-next btn-primary"
                                data-step_index="3">
                            <?= lang('next') ?>
                            <span class="glyphicon glyphicon-forward"></span>
                        </button>
                    </div>
                </div>

                <!-- APPOINTMENT DATA CONFIRMATION -->

                <div id="wizard-frame-4" class="wizard-frame" style="display:none;">
                    <div class="frame-container">
                        <h3 class="frame-title"><?= lang('step_four_title') ?></h3>
                        <div class="frame-content row">
                            <div id="appointment-details" class="col-xs-12 col-sm-6"></div>
                            <div id="customer-details" class="col-xs-12 col-sm-6"></div>
                        </div>
                        <?php if ($this->settings_model->get_setting('require_captcha') === '1'): ?>
                        <div class="frame-content row">
                            <div class="col-xs-12 col-sm-6">
                                <h4 class="captcha-title">
                                    CAPTCHA
                                    <small class="glyphicon glyphicon-refresh"></small>
                                </h4>
                                <img class="captcha-image" src="<?= site_url('captcha') ?>">
                                <input class="captcha-text" type="text" value="" />
                                <span id="captcha-hint" class="help-block" style="opacity:0">&nbsp;</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="command-buttons">
                        <button type="button" id="button-back-4" class="btn button-back btn-default"
                                data-step_index="4">
                            <span class="glyphicon glyphicon-backward"></span>
                            <?= lang('back') ?>
                        </button>
                        <form id="book-appointment-form" style="display:inline-block" method="post">
                            <button id="book-appointment-submit" type="button" class="btn button-next btn-primary">
                                <span class="glyphicon glyphicon-ok"></span>
                                <?= !$manage_mode ? lang('confirm') : lang('update') ?>
                            </button>
                            <input type="hidden" name="csrfToken" />
                            <input type="hidden" name="post_data" />
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($display_cookie_notice === '1'): ?>
        <?php require 'cookie_notice_modal.php' ?>
    <?php endif ?>

    <?php if ($display_terms_and_conditions === '1'): ?>
        <?php require 'terms_and_conditions_modal.php' ?>
    <?php endif ?>

    <?php if ($display_privacy_policy === '1'): ?>
        <?php require 'privacy_policy_modal.php' ?>
    <?php endif ?>

    <script>
        var GlobalVariables = {
            availableServices   : <?= json_encode($available_services) ?>,
            availableProviders  : <?= json_encode($available_providers) ?>,
            baseUrl             : <?= json_encode(config('base_url')) ?>,
            manageMode          : <?= $manage_mode ? 'true' : 'false' ?>,
            customerToken       : <?= json_encode($customer_token) ?>,
            dateFormat          : <?= json_encode($date_format) ?>,
            timeFormat          : <?= json_encode($time_format) ?>,
            displayCookieNotice : <?= json_encode($display_cookie_notice === '1') ?>,
            appointmentData     : <?= json_encode($appointment_data) ?>,
            providerData        : <?= json_encode($provider_data) ?>,
            customerData        : <?= json_encode($customer_data) ?>,
            csrfToken           : <?= json_encode($this->security->get_csrf_hash()) ?>,
            firebase_url         : <?= json_encode(Config::FIREBASE_URL) ?>
        };

        var EALang = <?= json_encode($this->lang->language) ?>;
        var availableLanguages = <?= json_encode($this->config->item('available_languages')) ?>;
    </script>

    <script src="<?= asset_url('assets/js/general_functions.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/jquery-qtip/jquery.qtip.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/cookieconsent/cookieconsent.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/bootstrap/js/bootstrap.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/datejs/date.js') ?>"></script>
    <script src="<?= asset_url('assets/js/frontend_book_api.js') ?>"></script>
    <script src="<?= asset_url('assets/js/frontend_book.js') ?>"></script>

    <script>
        $(document).ready(function() {
            FrontendBook.initialize(true, GlobalVariables.manageMode);
            GeneralFunctions.enableLanguageSelection($('#select-language'));
        });
    </script>

    <?php google_analytics_script(); ?>
</body>
</html>
