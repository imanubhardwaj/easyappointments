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

window.FrontendBookApi = window.FrontendBookApi || {};

/**
 * Frontend Book API
 *
 * This module serves as the API consumer for the booking wizard of the app.
 *
 * @module FrontendBookApi
 */
(function (exports) {

    'use strict';

    var unavailableDatesBackup;
    var selectedDateStringBackup;
    var processingUnavailabilities = false;

    const selectedService = GlobalVariables.availableServices[0];

    const selectedProvider = GlobalVariables.availableProviders[0];

    /**
     * Get Available Hours
     *
     * This function makes an AJAX call and returns the available hours for the selected service,
     * provider and date.
     *
     * @param {String} selDate The selected date of which the available hours we need to receive.
     */
    exports.getAvailableHours = function (selDate) {
        $('#available-hours').empty();

        // Find the selected service duration (it is going to be send within the "postData" object).
        var selServiceDuration = 15; // Default value of duration (in minutes).
        $.each(GlobalVariables.availableServices, function (index, service) {
            if (service.id == selectedService['id']) {
                selServiceDuration = service.duration;
            }
        });

        // If the manage mode is true then the appointment's start date should return as available too.
        var appointmentId = FrontendBook.manageMode ? GlobalVariables.appointmentData.id : undefined;

        // Make ajax post request and get the available hours.
        var postUrl = GlobalVariables.baseUrl + '/index.php/appointments/ajax_get_available_slots';
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            service_id: selectedService['id'],
            provider_id: selectedProvider['id'],
            selected_date: selDate,
            service_duration: selServiceDuration,
            manage_mode: FrontendBook.manageMode,
            appointment_id: appointmentId,
            timezone: moment().format('Z'),
            time: moment().format('YYYY-MM-DD HH:mm:ss')
        };

        $.post(postUrl, postData, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            // The response contains the available hours for the selected provider and
            // service. Fill the available hours div with response data.
            if (response.length > 0) {
                $('#time-select').show();
                $('#no-time').hide();
                var timeFormat = GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm';
                var availableHours = {};

                $.each(response, function (index, availableHour) {
                    const hour = moment(availableHour, 'HH:mm').format('HH');
                    const minute = moment(availableHour, 'HH:mm').format('mm');
                    availableHours = {
                        ...availableHours,
                        [+hour]: [
                            ...(availableHours[+hour] ? availableHours[+hour] : []),
                            minute
                        ]
                    };
                });
                setSelectData(availableHours);

                if (FrontendBook.manageMode) {
                    // Set the appointment's start time as the default selection.
                    $('.available-hour').removeClass('selected-hour');
                    $('.available-hour').filter(function () {
                        return $(this).text() === Date.parseExact(
                            GlobalVariables.appointmentData.start_datetime,
                            'yyyy-MM-dd HH:mm:ss').toString(timeFormat);
                    }).addClass('selected-hour');
                } else {
                    // Set the first available hour as the default selection.
                    $('.available-hour:eq(0)').addClass('selected-hour');
                }

                FrontendBook.updateConfirmFrame();

            } else {
                $('#time-select').hide();
                $('#no-time').show();
            }
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    function setSelectData(hours) {
        const select = $('#available-hours');
        select
            .find('option')
            .remove()
            .end();
        Object.keys(hours).forEach(key => select.append(new Option(moment(key, 'HH').format('hh A'), key)));
        select.change(function() {
            setMinutesOptions(select, hours);
        });
        setMinutesOptions(select, hours);
    }

    function setMinutesOptions(select, hours) {
        const value = select.val();
        if(hours[value] && hours[value].length) {
            $('#minutes-select').show();
            $('#no-minutes').hide();
            $('#available-minutes')
                .find('option')
                .remove()
                .end();
            hours[value].forEach(min => {
                $('#available-minutes').append(new Option(min, min));
            });
        } else {
            $('#minutes-select').hide();
            $('#no-minutes').show();
        }
    }

    function syncData() {
        const providerId = new URLSearchParams(location.href).get('providerId');
        var url = GlobalVariables.baseUrl + '/index.php/google/sync/' + providerId;

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json'
        })
            .done(function (response) {
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
            });
    }

    /**
     * Register an appointment to the database.
     *
     * This method will make an ajax call to the appointments controller that will register
     * the appointment to the database.
     */
    exports.registerAppointment = function () {
        var $captchaText = $('.captcha-text');

        if ($captchaText.length > 0) {
            $captchaText.closest('.form-group').removeClass('has-error');
            if ($captchaText.val() === '') {
                $captchaText.closest('.form-group').addClass('has-error');
                return;
            }
        }

        var formData = jQuery.parseJSON($('input[name="post_data"]').val());
        var postData = {
            csrfToken: GlobalVariables.csrfToken,
            post_data: formData
        };

        if ($captchaText.length > 0) {
            postData.captcha = $captchaText.val();
        }

        if (GlobalVariables.manageMode) {
            postData.exclude_appointment_id = GlobalVariables.appointmentData.id;
        }

        var postUrl = GlobalVariables.baseUrl + '/index.php/appointments/ajax_register_appointment';
        var $layer = $('<div/>');

        $.ajax({
            url: postUrl,
            method: 'post',
            data: postData,
            dataType: 'json',
            beforeSend: function (jqxhr, settings) {
                $layer
                    .appendTo('body')
                    .css({
                        background: 'white',
                        position: 'fixed',
                        top: '0',
                        left: '0',
                        height: '100vh',
                        width: '100vw',
                        opacity: '0.5'
                    });
            }
        })
            .done(function (response) {
                $('#final-actions').css({'display': 'block'});
                if (!GeneralFunctions.handleAjaxExceptions(response)) {
                    $('.captcha-title small').trigger('click');
                    $('#final-actions').css({'display': 'block'});
                    return false;
                }

                $('#final-actions').css({'display': 'block'});
                if (response.captcha_verification === false) {
                    $('#captcha-hint')
                        .text(EALang.captcha_is_wrong)
                        .fadeTo(400, 1);

                    setTimeout(function () {
                        $('#captcha-hint').fadeTo(400, 0);
                    }, 3000);

                    $('.captcha-title small').trigger('click');

                    $captchaText.closest('.form-group').addClass('has-error');

                    return false;
                }

                if(user_id) {
                    let data = {};
                    if(postData.post_data.appointment) {
                        const timezone = selectedProvider['timezone'];
                        const timezoneAbbr = GeneralFunctions.timezones.filter(zone => zone.offset === timezone)[0];
                        data = {
                            ...data,
                            'start_datetime': getFormattedTime(postData.post_data.appointment.start_datetime, timezone) + ' (' + timezoneAbbr.abbr + ')',
                            'end_datetime': getFormattedTime(postData.post_data.appointment.end_datetime, timezone) + ' (' + timezoneAbbr.abbr + ')',
                            'notes': postData.post_data.appointment.notes
                        };
                    }
                    const dateTime = new Date().toISOString().replace('T', ' ').replace('Z', '');
                    let event = {
                        'cta_data': {
                            customer: postData.post_data.customer,
                            appointment: data
                        },
                        'video_id': video_id,
                        'viewer_id': postData.post_data.customer.first_name + ' ' + postData.post_data.customer.last_name,
                        'email_id': email_id,
                        'time': dateTime,
                        'cta_type': 8
                    };
                    if(selectedService) {
                        event = {
                            ...event,
                            'cta_title': selectedService['name']
                        };
                    }
                    if(geoData) {
                        event = {
                            ...event,
                            'city': geoData.city,
                            'region': geoData.region_name,
                            'country_code': geoData.country_code
                        };
                    }
                    $.ajax({
                        type: 'POST',
                        url: GlobalVariables.firebase_url + '/video_events/' + user_id + '.json',
                        data: JSON.stringify(event),
                        success: function (res) {
                            $layer.remove();
                            syncData();
                            const url = GlobalVariables.baseUrl + '/index.php/appointments/book_success/' + response.appointment_id;
                            window.location.href = user_id ? url + '?user_id=' + user_id : url;
                        },
                        error: function (error) {
                            $layer.remove();
                        }
                    });
                } else {
                    $layer.remove();
                    syncData();
                    const url = GlobalVariables.baseUrl + '/index.php/appointments/book_success/' + response.appointment_id;
                    window.location.href = user_id ? url + '?user_id=' + user_id : url;
                }
            })
            .fail(function (jqxhr, textStatus, errorThrown) {
                $layer.remove();
                $('.captcha-title small').trigger('click');
                GeneralFunctions.ajaxFailureHandler(jqxhr, textStatus, errorThrown);
                $('#final-actions').css({'display': 'block'});
            });
    };

    /**
     * Get the unavailable dates of a provider.
     *
     * This method will fetch the unavailable dates of the selected provider and service and then it will
     * select the first available date (if any). It uses the "FrontendBookApi.getAvailableHours" method to
     * fetch the appointment* hours of the selected date.
     *
     * @param {Number} providerId The selected provider ID.
     * @param {Number} serviceId The selected service ID.
     * @param {String} selectedDateString Y-m-d value of the selected date.
     */
    exports.getUnavailableDates = function (providerId, serviceId, selectedDateString) {
        if (processingUnavailabilities) {
            return;
        }

        var appointmentId = FrontendBook.manageMode ? GlobalVariables.appointmentData.id : undefined;

        var url = GlobalVariables.baseUrl + '/index.php/appointments/ajax_get_unavailable_dates';
        var data = {
            provider_id: providerId,
            service_id: serviceId,
            selected_date: encodeURIComponent(selectedDateString),
            csrfToken: GlobalVariables.csrfToken,
            manage_mode: FrontendBook.manageMode,
            appointment_id: appointmentId
        };

        $.ajax({
            url: url,
            type: 'GET',
            data: data,
            dataType: 'json'
        })
            .done(function (response) {
                unavailableDatesBackup = response;
                selectedDateStringBackup = selectedDateString;
                _applyUnavailableDates(response, selectedDateString, true);
            })
            .fail(GeneralFunctions.ajaxFailureHandler);
    };

    exports.applyPreviousUnavailableDates = function () {
        _applyUnavailableDates(unavailableDatesBackup, selectedDateStringBackup);
    };

    function _applyUnavailableDates(unavailableDates, selectedDateString, setDate) {
        setDate = setDate || false;

        processingUnavailabilities = true;

        // Select first enabled date.
        var selectedDate = Date.parse(selectedDateString);
        var numberOfDays = new Date(selectedDate.getFullYear(), selectedDate.getMonth() + 1, 0).getDate();

        if (setDate && !GlobalVariables.manageMode) {
            for (var i = 1; i <= numberOfDays; i++) {
                var currentDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), i);
                if (unavailableDates.indexOf(currentDate.toString('yyyy-MM-dd')) === -1) {
                    $('#select-date').datepicker('setDate', currentDate);
                    FrontendBookApi.getAvailableHours(moment(currentDate.toString('yyyy-MM-dd'), 'YYYY-MM-DD').format('YYYY-MM-DD') >= moment().format('YYYY-MM-DD') ? currentDate.toString('yyyy-MM-dd') : moment().format('YYYY-MM-DD'));
                    break;
                }
            }
        }

        // If all the days are unavailable then hide the appointments hours.
        if (unavailableDates.length === numberOfDays) {
            $('#available-hours').text(EALang.no_available_hours);
        }

        // Grey out unavailable dates.
        $('#select-date .ui-datepicker-calendar td:not(.ui-datepicker-other-month)').each(function (index, td) {
            selectedDate.set({day: index + 1});
            if ($.inArray(selectedDate.toString('yyyy-MM-dd'), unavailableDates) != -1) {
                $(td).addClass('ui-datepicker-unselectable ui-state-disabled');
            }
        });

        processingUnavailabilities = false;
    }

    /**
     * Save the user's consent.
     *
     * @param {Object} consent Contains user's consents.
     */
    exports.saveConsent = function (consent) {
        var url = GlobalVariables.baseUrl + '/index.php/consents/ajax_save_consent';
        var data = {
            csrfToken: GlobalVariables.csrfToken,
            consent: consent
        };

        $.post(url, data, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    /**
     * Delete personal information.
     *
     * @param {Number} customerToken Customer unique token.
     */
    exports.deletePersonalInformation = function (customerToken) {
        var url = GlobalVariables.baseUrl + '/index.php/privacy/ajax_delete_personal_information';
        var data = {
            csrfToken: GlobalVariables.csrfToken,
            customer_token: customerToken
        };

        $.post(url, data, function (response) {
            if (!GeneralFunctions.handleAjaxExceptions(response)) {
                return;
            }

            location.href = GlobalVariables.baseUrl;
        }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
    };

    function getFormattedTime(date, timezone) {
        const zone = timezone.substring(1);
        if(timezone.charAt(0) === '+') {
            return addTimeToDate(date, zone);
        } else {
            return subtractTimeFromDate(date, zone);
        }
    }

    function addTimeToDate(dateString, timezone) {
        return moment.utc(dateString).add(timezone.split(':')[0], 'hours')
            .add(timezone.split(':')[1], 'minutes').format('YYYY-MM-DD HH:mm:ss');
    }

    function subtractTimeFromDate(dateString, timezone) {
        return moment.utc(dateString).subtract(timezone.split(':')[0], 'hours')
            .subtract(timezone.split(':')[1], 'minutes').format('YYYY-MM-DD HH:mm:ss');
    }

})(window.FrontendBookApi);
