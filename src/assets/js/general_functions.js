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

window.GeneralFunctions = window.GeneralFunctions || {};

/**
 * General Functions Module
 *
 * It contains functions that apply both on the front and back end of the application.
 *
 * @module GeneralFunctions
 */
(function (exports) {

    'use strict';

    /**
     * General Functions Constants
     */
    exports.EXCEPTIONS_TITLE = EALang.unexpected_issues;
    exports.EXCEPTIONS_MESSAGE = EALang.unexpected_issues_message;
    exports.WARNINGS_TITLE = EALang.unexpected_warnings;
    exports.WARNINGS_MESSAGE = EALang.unexpected_warnings_message;

    /**
     * This functions displays a message box in the admin array. It is useful when user
     * decisions or verifications are needed.
     *
     * @param {String} title The title of the message box.
     * @param {String} message The message of the dialog.
     * @param {Array} buttons Contains the dialog buttons along with their functions.
     */
    exports.displayMessageBox = function (title, message, buttons) {
        // Check arguments integrity.
        if (title == undefined || title == '') {
            title = '<No Title Given>';
        }

        if (message == undefined || message == '') {
            message = '<No Message Given>';
        }

        if (buttons == undefined) {
            buttons = [
                {
                    text: EALang.close,
                    click: function () {
                        $('#message_box').dialog('close');

                    }
                }
            ];
        }

        // Destroy previous dialog instances.
        $('#message_box').dialog('destroy');
        $('#message_box').remove();

        // Create the html of the message box.
        $('body').append(
            '<div id="message_box" title="' + title + '">' +
            '<p>' + message + '</p>' +
            '</div>'
        );

        $("#message_box").dialog({
            autoOpen: false,
            modal: true,
            resize: 'auto',
            width: 'auto',
            height: 'auto',
            resizable: false,
            buttons: buttons,
            closeOnEscape: true
        });

        $('#message_box').dialog('open');
        $('.ui-dialog .ui-dialog-buttonset button').addClass('btn btn-default');
        $('#message_box .ui-dialog-titlebar-close').hide();
    };

    /**
     * This method centers a DOM element vertically and horizontally on the page.
     *
     * @param {Object} elementHandle The object that is going to be centered.
     */
    exports.centerElementOnPage = function (elementHandle) {
        // Center main frame vertical middle
        $(window).resize(function () {
            var elementLeft = ($(window).width() - elementHandle.outerWidth()) / 2;
            var elementTop = ($(window).height() - elementHandle.outerHeight()) / 2;
            elementTop = (elementTop > 0) ? elementTop : 20;

            elementHandle.css({
                position: 'absolute',
                left: elementLeft,
                top: elementTop
            });
        });
        $(window).resize();
    };

    /**
     * This function retrieves a parameter from a "GET" formed url.
     *
     * {@link http://www.netlobo.com/url_query_string_javascript.html}
     *
     * @param {String} url The selected url.
     * @param {String} name The parameter name.

     * @return {String} Returns the parameter value.
     */
    exports.getUrlParameter = function (url, parameterName) {
        var parsedUrl = url.substr(url.indexOf('?')).slice(1).split('&');

        for (var index in parsedUrl) {
            var parsedValue = parsedUrl[index].split('=');

            if (parsedValue.length === 1 && parsedValue[0] === parameterName) {
                return '';
            }

            if (parsedValue.length === 2 && parsedValue[0] === parameterName) {
                return decodeURIComponent(parsedValue[1]);
            }
        }

        return '';
    };

    /**
     * Convert date to ISO date string.
     *
     * This function creates a RFC 3339 date string. This string is needed by the Google Calendar API
     * in order to pass dates as parameters.
     *
     * @param {Date} date The given date that will be transformed.

     * @return {String} Returns the transformed string.
     */
    exports.ISODateString = function (date) {
        function pad(n) {
            return n < 10 ? '0' + n : n;
        }

        return date.getUTCFullYear() + '-'
            + pad(date.getUTCMonth() + 1) + '-'
            + pad(date.getUTCDate()) + 'T'
            + pad(date.getUTCHours()) + ':'
            + pad(date.getUTCMinutes()) + ':'
            + pad(date.getUTCSeconds()) + 'Z';
    };

    /**
     * Clone JS Object
     *
     * This method creates and returns an exact copy of the provided object. It is very useful whenever
     * changes need to be made to an object without modifying the original data.
     *
     * {@link http://stackoverflow.com/questions/728360/most-elegant-way-to-clone-a-javascript-object}
     *
     * @param {Object} originalObject Object to be copied.

     * @return {Object} Returns an exact copy of the provided element.
     */
    exports.clone = function (originalObject) {
        // Handle the 3 simple types, and null or undefined
        if (null == originalObject || 'object' != typeof originalObject)
            return originalObject;

        // Handle Date
        if (originalObject instanceof Date) {
            var copy = new Date();
            copy.setTime(originalObject.getTime());
            return copy;
        }

        // Handle Array
        if (originalObject instanceof Array) {
            var copy = [];
            for (var i = 0, len = originalObject.length; i < len; i++) {
                copy[i] = GeneralFunctions.clone(originalObject[i]);
            }
            return copy;
        }

        // Handle Object
        if (originalObject instanceof Object) {
            var copy = {};
            for (var attr in originalObject) {
                if (originalObject.hasOwnProperty(attr))
                    copy[attr] = GeneralFunctions.clone(originalObject[attr]);
            }
            return copy;
        }

        throw new Error('Unable to copy obj! Its type isn\'t supported.');
    };

    /**
     * Validate Email Address
     *
     * This method validates an email address. If the address is not on the proper
     * form then the result is FALSE.
     *
     * {@link http://badsyntax.co/post/javascript-email-validation-rfc822}
     *
     * @param {String} email The email address to be checked.

     * @return {Boolean} Returns the validation result.
     */
    exports.validateEmail = function (email) {
        var re = /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*$/;
        return re.test(email);
    };

    /**
     * Convert AJAX exceptions to HTML.
     *
     * This method returns the exception HTML display for javascript ajax calls. It uses the Bootstrap collapse
     * module to show exception messages when the user opens the "Details" collapse component.
     *
     * @param {Array} exceptions Contains the exceptions to be displayed.
     *
     * @return {String} Returns the html markup for the exceptions.
     */
    exports.exceptionsToHtml = function (exceptions) {
        var html =
            '<div class="accordion" id="error-accordion">' +
            '<div class="accordion-group">' +
            '<div class="accordion-heading">' +
            '<button class="accordion-toggle btn btn-default btn-xs" data-toggle="collapse" ' +
            'data-parent="#error-accordion" href="#error-technical">' +
            EALang.details +
            '</button>' +
            '</div>' +
            '<br>';

        $.each(exceptions, function (index, exception) {
            html +=
                '<div id="error-technical" class="accordion-body collapse">' +
                '<div class="accordion-inner">' +
                '<pre>' + exception.message + '</pre>' +
                '</div>' +
                '</div>';
        });

        html += '</div></div>';

        return html;
    };

    /**
     * Parse AJAX Exceptions
     *
     * This method parse the JSON encoded strings that are fetched by AJAX calls.
     *
     * @param {Array} exceptions Exception array returned by an ajax call.
     *
     * @return {Array} Returns the parsed js objects.
     */
    exports.parseExceptions = function (exceptions) {
        var parsedExceptions = new Array();

        $.each(exceptions, function (index, exception) {
            parsedExceptions.push($.parseJSON(exception));
        });

        return parsedExceptions;
    };

    /**
     * Makes the first letter of the string upper case.
     *
     * @param {String} str The string to be converted.
     *
     * @return {String} Returns the capitalized string.
     */
    exports.ucaseFirstLetter = function (str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    };

    /**
     * Handle AJAX Exceptions Callback
     *
     * All backend js code has the same way of dislaying exceptions that are raised on the
     * server during an ajax call.
     *
     * @param {Object} response Contains the server response. If exceptions or warnings are
     * found, user friendly messages are going to be displayed to the user.4
     *
     * @return {Boolean} Returns whether the the ajax callback should continue the execution or
     * stop, due to critical server exceptions.
     */
    exports.handleAjaxExceptions = function (response) {
        if (response.exceptions) {
            response.exceptions = GeneralFunctions.parseExceptions(response.exceptions);
            GeneralFunctions.displayMessageBox(GeneralFunctions.EXCEPTIONS_TITLE, GeneralFunctions.EXCEPTIONS_MESSAGE);
            $('#message_box').append(GeneralFunctions.exceptionsToHtml(response.exceptions));
            return false;
        }

        if (response.warnings) {
            response.warnings = GeneralFunctions.parseExceptions(response.warnings);
            GeneralFunctions.displayMessageBox(GeneralFunctions.WARNINGS_TITLE, GeneralFunctions.WARNINGS_MESSAGE);
            $('#message_box').append(GeneralFunctions.exceptionsToHtml(response.warnings));
        }

        return true;
    };

    /**
     * Enable Language Selection
     *
     * Enables the language selection functionality. Must be called on every page has a
     * language selection button. This method requires the global variable 'availableLanguages'
     * to be initialized before the execution.
     *
     * @param {Object} $element Selected element button for the language selection.
     */
    exports.enableLanguageSelection = function ($element) {
        // Select Language
        var html = '<ul id="language-list">';
        $.each(availableLanguages, function () {
            html += '<li class="language" data-language="' + this + '">'
                + GeneralFunctions.ucaseFirstLetter(this) + '</li>';
        });
        html += '</ul>';

        $element.popover({
            placement: 'top',
            title: 'Select Language',
            content: html,
            html: true,
            container: 'body',
            trigger: 'manual'
        });

        $element.click(function () {
            if ($('#language-list').length === 0) {
                $(this).popover('show');
            } else {
                $(this).popover('hide');
            }

            $(this).toggleClass('active');
        });

        $(document).on('click', 'li.language', function () {
            // Change language with ajax call and refresh page.
            var postUrl = GlobalVariables.baseUrl + '/index.php/backend_api/ajax_change_language';
            var postData = {
                csrfToken: GlobalVariables.csrfToken,
                language: $(this).attr('data-language')
            };
            $.post(postUrl, postData, function (response) {
                if (!GeneralFunctions.handleAjaxExceptions(response)) {
                    return;
                }
                document.location.reload(true);

            }, 'json').fail(GeneralFunctions.ajaxFailureHandler);
        });
    };

    /**
     * AJAX Failure Handler
     *
     * @param {jqXHR} jqxhr
     * @param {String} textStatus
     * @param {Object} errorThrown
     */
    exports.ajaxFailureHandler = function (jqxhr, textStatus, errorThrown) {
        var exceptions = [
            {
                message: 'AJAX Error: ' + errorThrown + $(jqxhr.responseText).text()
            }
        ];
        GeneralFunctions.displayMessageBox(GeneralFunctions.EXCEPTIONS_TITLE, GeneralFunctions.EXCEPTIONS_MESSAGE);
        $('#message_box').append(GeneralFunctions.exceptionsToHtml(exceptions));
    };

    /**
     * Escape JS HTML string values for XSS prevention.
     *
     * @param {String} str String to be escaped.
     *
     * @return {String} Returns the escaped string.
     */
    exports.escapeHtml = function (str) {
        return $('<div/>').text(str).html();
    };

    /**
     * Format a given date according to the date format setting.
     *
     * @param {Date} date The date to be formatted.
     * @param {String} dateFormatSetting The setting provided by PHP must be one of
     * the "DMY", "MDY" or "YMD".
     * @param {Boolean} addHours (optional) Whether to add hours to the result.

     * @return {String} Returns the formatted date string.
     */
    exports.formatDate = function (date, dateFormatSetting, addHours) {
        var timeFormat = GlobalVariables.timeFormat === 'regular' ? 'h:mm tt' : 'HH:mm';
        var hours = addHours ? ' ' + timeFormat : '';
        var result;

        switch (dateFormatSetting) {
            case 'DMY':
                result = Date.parse(date).toString('dd/MM/yyyy' + hours);
                break;
            case 'MDY':
                result = Date.parse(date).toString('MM/dd/yyyy' + hours);
                break;
            case 'YMD':
                result = Date.parse(date).toString('yyyy/MM/dd' + hours);
                break;
            default:
                throw new Error('Invalid date format setting provided!', dateFormatSetting);
        }

        return result;
    };

    exports.timezones = [{
        abbr: "ACDT",
        name: "Australian Central Daylight Savings Time",
        offset: "+10:30"
    }, {abbr: "ACST",
        name: "Australian Central Standard Time",
        offset: "+09:30"
    }, {abbr: "ACT", name: "Acre Time", offset: "−05:00"}, {
        abbr: "ACWST",
        name: "Australian Central Western Standard Time",
        offset: "+08:45"
    }, {abbr: "ADT", name: "Atlantic  Daylight Time", offset: "−03:00"}, {
        abbr: "AEDT",
        name: "Australian Eastern Daylight Savings Time",
        offset: "+11:00"
    }, {abbr: "AEST", name: "Australian Eastern Standard Time", offset: "+10:00"}, {
        abbr: "AFT",
        name: "Afghanistan Time",
        offset: "+04:30"
    }, {abbr: "AKDT", name: "Alaska Daylight  Time", offset: "−08:00"}, {
        abbr: "AKST",
        name: "Alaska Standard  Time",
        offset: "−09:00"
    }, {abbr: "AMST", name: "Amazon Summer Time", offset: "−03:00"}, {
        abbr: "AMT",
        name: "Amazon Time",
        offset: "−04:00"
    }, {abbr: "AMT", name: "Armenia Time", offset: "+04:00"}, {
        abbr: "ART",
        name: "Argentina Time",
        offset: "−03:00"
    }, {abbr: "AST", name: "Arabia Standard  Time", offset: "+03:00"}, {
        abbr: "AST",
        name: "Atlantic  Standard Time",
        offset: "−04:00"
    }, {abbr: "AWST", name: "Australian Western Standard Time", offset: "+08:00"}, {
        abbr: "AZOST",
        name: "Azores Summer Time",
        offset: "+00:00"
    }, {abbr: "AZOT", name: "Azores Standard Time", offset: "−01:00"}, {
        abbr: "AZT",
        name: "Azerbaijan Time",
        offset: "+04:00"
    }, {abbr: "BDT", name: "Brunei Time", offset: "+08:00"}, {
        abbr: "BIOT",
        name: "British  Indian Ocean Time",
        offset: "+06:00"
    }, {abbr: "BIT", name: "Baker Island Time", offset: "−12:00"}, {
        abbr: "BOT",
        name: "Bolivia Time",
        offset: "−04:00"
    }, {abbr: "BRST", name: "Brasília  Summer Time", offset: "−02:00"}, {
        abbr: "BRT",
        name: "Brasilia Time",
        offset: "−03:00"
    }, {abbr: "BST", name: "Bangladesh Standard Time", offset: "+06:00"}, {
        abbr: "BST",
        name: "Bougainville  Standard Time",
        offset: "+11:00"
    }, {abbr: "BST", name: "British Summer Time", offset: "+01:00"}, {
        abbr: "BTT",
        name: "Bhutan Time",
        offset: "+06:00"
    }, {abbr: "CAT", name: "Central Africa Time", offset: "+02:00"}, {
        abbr: "CCT",
        name: "Cocos Islands Time",
        offset: "+06:30"
    }, {abbr: "CDT", name: "Central Daylight  Time", offset: "−05:00"}, {
        abbr: "CDT",
        name: "Cuba Daylight Time",
        offset: "−04:00"
    }, {abbr: "CEST", name: "Central European  Summer Time", offset: "+02:00"}, {
        abbr: "CET",
        name: "Central European Time",
        offset: "+01:00"
    }, {abbr: "CHADT", name: "Chatham Daylight  Time", offset: "+13:45"}, {
        abbr: "CHAST",
        name: "Chatham Standard  Time",
        offset: "+12:45"
    }, {abbr: "CHOT", name: "Choibalsan  Standard Time", offset: "+08:00"}, {
        abbr: "CHOST",
        name: "Choibalsan  Summer Time",
        offset: "+09:00"
    }, {abbr: "CHST", name: "Chamorro  Standard Time", offset: "+10:00"}, {
        abbr: "CHUT",
        name: "Chuuk Time",
        offset: "+10:00"
    }, {abbr: "CIST", name: "Clipperton Island Standard Time", offset: "−08:00"}, {
        abbr: "CIT",
        name: "Central  Indonesia Time",
        offset: "+08:00"
    }, {abbr: "CKT", name: "Cook Island Time", offset: "−10:00"}, {
        abbr: "CLST",
        name: "Chile Summer Time",
        offset: "−03:00"
    }, {abbr: "CLT", name: "Chile Standard  Time", offset: "−04:00"}, {
        abbr: "COST",
        name: "Colombia Summer  Time",
        offset: "−04:00"
    }, {abbr: "COT", name: "Colombia Time", offset: "−05:00"}, {
        abbr: "CST",
        name: "Central Standard  Time",
        offset: "−06:00"
    }, {abbr: "CST", name: "China Standard  Time", offset: "+08:00"}, {
        abbr: "CST",
        name: "Cuba Standard Time",
        offset: "−05:00"
    }, {abbr: "CT", name: "China Time", offset: "+08:00"}, {
        abbr: "CVT",
        name: "Cape Verde Time",
        offset: "−01:00"
    }, {abbr: "CWST", name: "Central  Western Standard Timel", offset: "+08:45"}, {
        abbr: "CXT",
        name: "Christmas Island  Time",
        offset: "+07:00"
    }, {abbr: "DAVT", name: "Davis Time", offset: "+07:00"}, {
        abbr: "DDUT",
        name: "Dumont d'Urville Time",
        offset: "+10:00"
    }, {
        abbr: "DFT",
        name: "AIX-specific equivalent of Central  European Time[NB 1]",
        offset: "+01:00"
    }, {abbr: "EASST", name: "Easter  Island Summer Time", offset: "−05:00"}, {
        abbr: "EAST",
        name: "Easter  Island Standard Time",
        offset: "−06:00"
    }, {abbr: "EAT", name: "East Africa Time", offset: "+03:00"}, {
        abbr: "ECT",
        name: "Eastern  Caribbean Time",
        offset: "−04:00"
    }, {abbr: "ECT", name: "Ecuador Time", offset: "−05:00"}, {
        abbr: "EDT",
        name: "Eastern Daylight  Time",
        offset: "−04:00"
    }, {abbr: "EEST", name: "Eastern European  Summer Time", offset: "+03:00"}, {
        abbr: "EET",
        name: "Eastern European Time",
        offset: "+02:00"
    }, {abbr: "EGST", name: "Eastern  Greenland Summer Time", offset: "+00:00"}, {
        abbr: "EGT",
        name: "Eastern  Greenland Time",
        offset: "−01:00"
    }, {abbr: "EIT", name: "Eastern  Indonesian Time", offset: "+09:00"}, {
        abbr: "EST",
        name: "Eastern Standard Time",
        offset: "−05:00"
    }, {abbr: "FET", name: "Further-eastern  European Time", offset: "+03:00"}, {
        abbr: "FJT",
        name: "Fiji Time",
        offset: "+12:00"
    }, {abbr: "FKST", name: "Falkland  Islands Summer Time", offset: "−03:00"}, {
        abbr: "FKT",
        name: "Falkland Islands  Time",
        offset: "−04:00"
    }, {abbr: "FNT", name: "Fernando  de Noronha Time", offset: "−02:00"}, {
        abbr: "GALT",
        name: "Galápagos Time",
        offset: "−06:00"
    }, {abbr: "GAMT", name: "Gambier Islands  Time", offset: "−09:00"}, {
        abbr: "GET",
        name: "Georgia Standard  Time",
        offset: "+04:00"
    }, {abbr: "GFT", name: "French Guiana Time", offset: "−03:00"}, {
        abbr: "GILT",
        name: "Gilbert Island  Time",
        offset: "+12:00"
    }, {abbr: "GIT", name: "Gambier Island  Time", offset: "−09:00"}, {
        abbr: "GMT",
        name: "Greenwich Mean Time",
        offset: "+00:00"
    }, {abbr: "GST", name: "South Georgia and the South Sandwich  Islands Time", offset: "−02:00"}, {
        abbr: "GST",
        name: "Gulf Standard Time",
        offset: "+04:00"
    }, {abbr: "GYT", name: "Guyana Time", offset: "−04:00"}, {
        abbr: "HDT",
        name: "Hawaii–Aleutian Daylight Time",
        offset: "−09:00"
    }, {
        abbr: "HAEC",
        name: "Heure Avancée d'Europe Centrale French-language name  for CEST",
        offset: "+02:00"
    }, {abbr: "HST", name: "Hawaii–Aleutian Standard Time", offset: "−10:00"}, {
        abbr: "HKT",
        name: "Hong Kong Time",
        offset: "+08:00"
    }, {abbr: "HMT", name: "Heard  and McDonald Islands Time", offset: "+05:00"}, {
        abbr: "HOVST",
        name: "Khovd Summer Time",
        offset: "+08:00"
    }, {abbr: "HOVT", name: "Khovd Standard Time", offset: "+07:00"}, {
        abbr: "ICT",
        name: "Indochina Time",
        offset: "+07:00"
    }, {abbr: "IDLW", name: "International Day Line West time zone", offset: "−12:00"}, {
        abbr: "IDT",
        name: "Israel Daylight  Time",
        offset: "+03:00"
    }, {abbr: "IOT", name: "Indian Ocean Time", offset: "+03:00"}, {
        abbr: "IRDT",
        name: "Iran Daylight Time",
        offset: "+04:30"
    }, {abbr: "IRKT", name: "Irkutsk Time", offset: "+08:00"}, {
        abbr: "IRST",
        name: "Iran Standard Time",
        offset: "+03:30"
    }, {abbr: "IST", name: "Indian Standard Time", offset: "+05:30"}, {
        abbr: "IST",
        name: "Irish Standard  Time",
        offset: "+01:00"
    }, {abbr: "IST", name: "Israel Standard Time", offset: "+02:00"}, {
        abbr: "JST",
        name: "Japan Standard Time",
        offset: "+09:00"
    }, {abbr: "KALT", name: "Kaliningrad Time", offset: "+02:00"}, {
        abbr: "KGT",
        name: "Kyrgyzstan Time",
        offset: "+06:00"
    }, {abbr: "KOST", name: "Kosrae Time", offset: "+11:00"}, {
        abbr: "KRAT",
        name: "Krasnoyarsk Time",
        offset: "+07:00"
    }, {abbr: "KST", name: "Korea Standard  Time", offset: "+09:00"}, {
        abbr: "LHST",
        name: "Lord Howe Standard Time",
        offset: "+10:30"
    }, {abbr: "LHST", name: "Lord Howe Summer Time", offset: "+11:00"}, {
        abbr: "LINT",
        name: "Line Islands Time",
        offset: "+14:00"
    }, {abbr: "MAGT", name: "Magadan Time", offset: "+12:00"}, {
        abbr: "MART",
        name: "Marquesas  Islands Time",
        offset: "−09:30"
    }, {abbr: "MAWT", name: "Mawson Station Time", offset: "+05:00"}, {
        abbr: "MDT",
        name: "Mountain  Daylight Time",
        offset: "−06:00"
    }, {abbr: "MET", name: "Middle European  Time Same zone as CET", offset: "+01:00"}, {
        abbr: "MEST",
        name: "Middle  European Summer Time Same zone as CEST",
        offset: "+02:00"
    }, {abbr: "MHT", name: "Marshall Islands  Time", offset: "+12:00"}, {
        abbr: "MIST",
        name: "Macquarie Island Station Time",
        offset: "+11:00"
    }, {abbr: "MIT", name: "Marquesas  Islands Time", offset: "−09:30"}, {
        abbr: "MMT",
        name: "Myanmar Standard Time",
        offset: "+06:30"
    }, {abbr: "MSK", name: "Moscow Time", offset: "+03:00"}, {
        abbr: "MST",
        name: "Malaysia  Standard Time",
        offset: "+08:00"
    }, {abbr: "MST", name: "Mountain  Standard Time", offset: "−07:00"}, {
        abbr: "MUT",
        name: "Mauritius Time",
        offset: "+04:00"
    }, {abbr: "MVT", name: "Maldives Time", offset: "+05:00"}, {
        abbr: "MYT",
        name: "Malaysia Time",
        offset: "+08:00"
    }, {abbr: "NCT", name: "New Caledonia Time", offset: "+11:00"}, {
        abbr: "NDT",
        name: "Newfoundland  Daylight Time",
        offset: "−02:30"
    }, {abbr: "NFT", name: "Norfolk Island  Time", offset: "+11:00"}, {
        abbr: "NPT",
        name: "Nepal Time",
        offset: "+05:45"
    }, {abbr: "NST", name: "Newfoundland  Standard Time", offset: "−03:30"}, {
        abbr: "NT",
        name: "Newfoundland Time",
        offset: "−03:30"
    }, {abbr: "NUT", name: "Niue Time", offset: "−11:00"}, {
        abbr: "NZDT",
        name: "New  Zealand Daylight Time",
        offset: "+13:00"
    }, {abbr: "NZST", name: "New  Zealand Standard Time", offset: "+12:00"}, {
        abbr: "OMST",
        name: "Omsk Time",
        offset: "+06:00"
    }, {abbr: "ORAT", name: "Oral Time", offset: "+05:00"}, {
        abbr: "PDT",
        name: "Pacific Daylight  Time",
        offset: "−07:00"
    }, {abbr: "PET", name: "Peru Time", offset: "−05:00"}, {
        abbr: "PETT",
        name: "Kamchatka Time",
        offset: "+12:00"
    }, {abbr: "PGT", name: "Papua New Guinea  Time", offset: "+10:00"}, {
        abbr: "PHOT",
        name: "Phoenix Island  Time",
        offset: "+13:00"
    }, {abbr: "PHT", name: "Philippine Time", offset: "+08:00"}, {
        abbr: "PKT",
        name: "Pakistan Standard Time",
        offset: "+05:00"
    }, {abbr: "PMDT", name: "Saint Pierre and Miquelon Daylight Time", offset: "−02:00"}, {
        abbr: "PMST",
        name: "Saint Pierre and Miquelon Standard Time",
        offset: "−03:00"
    }, {abbr: "PONT", name: "Pohnpei Standard  Time", offset: "+11:00"}, {
        abbr: "PST",
        name: "Pacific Standard  Time",
        offset: "−08:00"
    }, {abbr: "PST", name: "Philippine Standard Time", offset: "+08:00"}, {
        abbr: "PYST",
        name: "Paraguay Summer  Time",
        offset: "−03:00"
    }, {abbr: "PYT", name: "Paraguay Time", offset: "−04:00"}, {
        abbr: "RET",
        name: "Réunion Time",
        offset: "+04:00"
    }, {abbr: "ROTT", name: "Rothera Research Station Time", offset: "−03:00"}, {
        abbr: "SAKT",
        name: "Sakhalin Island  Time",
        offset: "+11:00"
    }, {abbr: "SAMT", name: "Samara Time", offset: "+04:00"}, {
        abbr: "SAST",
        name: "South African Standard  Time",
        offset: "+02:00"
    }, {abbr: "SBT", name: "Solomon Islands  Time", offset: "+11:00"}, {
        abbr: "SCT",
        name: "Seychelles Time",
        offset: "+04:00"
    }, {abbr: "SDT", name: "Samoa Daylight  Time", offset: "−10:00"}, {
        abbr: "SGT",
        name: "Singapore Time",
        offset: "+08:00"
    }, {abbr: "SLST", name: "Sri Lanka Standard Time", offset: "+05:30"}, {
        abbr: "SRET",
        name: "Srednekolymsk Time",
        offset: "+11:00"
    }, {abbr: "SRT", name: "Suriname Time", offset: "−03:00"}, {
        abbr: "SST",
        name: "Samoa Standard  Time",
        offset: "−11:00"
    }, {abbr: "SST", name: "Singapore Standard Time", offset: "+08:00"}, {
        abbr: "SYOT",
        name: "Showa Station Time",
        offset: "+03:00"
    }, {abbr: "TAHT", name: "Tahiti Time", offset: "−10:00"}, {
        abbr: "THA",
        name: "Thailand  Standard Time",
        offset: "+07:00"
    }, {abbr: "TFT", name: "French Southern and Antarctic Time", offset: "+05:00"}, {
        abbr: "TJT",
        name: "Tajikistan Time",
        offset: "+05:00"
    }, {abbr: "TKT", name: "Tokelau Time", offset: "+13:00"}, {
        abbr: "TLT",
        name: "Timor Leste Time",
        offset: "+09:00"
    }, {abbr: "TMT", name: "Turkmenistan Time", offset: "+05:00"}, {
        abbr: "TRT",
        name: "Turkey Time",
        offset: "+03:00"
    }, {abbr: "TOT", name: "Tonga Time", offset: "+13:00"}, {
        abbr: "TVT",
        name: "Tuvalu Time",
        offset: "+12:00"
    }, {abbr: "ULAST", name: "Ulaanbaatar  Summer Time", offset: "+09:00"}, {
        abbr: "ULAT",
        name: "Ulaanbaatar  Standard Time",
        offset: "+08:00"
    }, {abbr: "UTC", name: "Coordinated Universal  Time", offset: "+00:00"}, {
        abbr: "UYST",
        name: "Uruguay Summer  Time",
        offset: "−02:00"
    }, {abbr: "UYT", name: "Uruguay Standard  Time", offset: "−03:00"}, {
        abbr: "UZT",
        name: "Uzbekistan Time",
        offset: "+05:00"
    }, {abbr: "VET", name: "Venezuelan  Standard Time", offset: "−04:00"}, {
        abbr: "VLAT",
        name: "Vladivostok Time",
        offset: "+10:00"
    }, {abbr: "VOLT", name: "Volgograd Time", offset: "+04:00"}, {
        abbr: "VOST",
        name: "Vostok Station Time",
        offset: "+06:00"
    }, {abbr: "VUT", name: "Vanuatu Time", offset: "+11:00"}, {
        abbr: "WAKT",
        name: "Wake Island Time",
        offset: "+12:00"
    }, {abbr: "WAST", name: "West Africa  Summer Time", offset: "+02:00"}, {
        abbr: "WAT",
        name: "West Africa Time",
        offset: "+01:00"
    }, {abbr: "WEST", name: "Western European  Summer Time", offset: "+01:00"}, {
        abbr: "WET",
        name: "Western European Time",
        offset: "+00:00"
    }, {abbr: "WIT", name: "Western  Indonesian Time", offset: "+07:00"}, {
        abbr: "WST",
        name: "Western Standard  Time",
        offset: "+08:00"
    }, {abbr: "YAKT", name: "Yakutsk Time", offset: "+09:00"}, {
        abbr: "YEKT",
        name: "Yekaterinburg Time",
        offset: "+05:00"
    }];

})(window.GeneralFunctions);
