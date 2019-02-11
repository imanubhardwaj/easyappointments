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

/**
 * Backend Calendar Timezone Modal
 *
 * This module implements the timezone modal functionality.
 *
 * @module BackendCalendarTimezoneModal
 */
window.BackendCalendarTimezoneModal = window.BackendCalendarTimezoneModal || {};

(function (exports) {

    'use strict';

    function _bindEventHandlers() {
        /**
         * Event: Manage Timezone Dialog Save Button "Click"
         *
         * Stores the timezone changes.
         */
        $('#select-timezone #save-timezone').click(function () {
            const $dialog = $('#select-timezone');
            $dialog.find('.has-error').removeClass('has-error');
            const timezone = $('#timezone').val();

            const successCallback = function (response) {
                if (response.exceptions) {
                    response.exceptions = GeneralFunctions.parseExceptions(response.exceptions);
                    GeneralFunctions.displayMessageBox(GeneralFunctions.EXCEPTIONS_TITLE,
                        GeneralFunctions.EXCEPTIONS_MESSAGE);
                    $('#message_box').append(GeneralFunctions.exceptionsToHtml(response.exceptions));

                    $dialog.find('.modal-message')
                        .text('Unexpected Issues Occurred')
                        .addClass('alert-danger')
                        .removeClass('hidden');

                    return;
                }

                if (response.warnings) {
                    response.warnings = GeneralFunctions.parseExceptions(response.warnings);
                    GeneralFunctions.displayMessageBox(GeneralFunctions.WARNINGS_TITLE,
                        GeneralFunctions.WARNINGS_MESSAGE);
                    $('#message_box').append(GeneralFunctions.exceptionsToHtml(response.warnings));
                }

                // Display success message to the user.
                $dialog.find('.modal-message')
                    .text('Timezone Saved')
                    .addClass('alert-success')
                    .removeClass('alert-danger hidden');

                // Close the modal dialog and refresh the calendar appointments after one second.
                setTimeout(function () {
                    $dialog.find('.alert').addClass('hidden');
                    $dialog.modal('hide');
                    $('#select-filter-item').trigger('change');
                    window.location.reload(true);
                }, 2000);
            };

            const errorCallback = function (jqXHR, textStatus, errorThrown) {
                GeneralFunctions.displayMessageBox('Communication Error', 'Unfortunately ' +
                    'the operation could not complete due to server communication errors.');

                $dialog.find('.modal-message').text('Server Communication Error');
                $dialog.find('.modal-message').addClass('alert-danger').removeClass('hidden');
            };

            BackendCalendarApi.saveTimezone(timezone, $('#select-filter-item').val(), successCallback, errorCallback);
        });

        /**
         * Event: Manage Timezone Dialog Cancel Button "Click"
         *
         * Closes the dialog without saving any changes to the database.
         */
        $('#select-timezone #cancel-timezone').click(function () {
            $('#select-timezone').modal('hide');
        });
    }

    exports.initialize = function () {
        _timezoneModalVisibilityCheck();
        _bindEventHandlers();
    };

    function _timezoneModalVisibilityCheck() {
        const successCallback = function (response) {
            if (response === 'SUCCESS') {
                loadTimezones();
                $('#select-timezone').modal('show');
            }
        };

        const errorCallback = function (jqXHR, textStatus, errorThrown) {
        };

        BackendCalendarApi.checkIfTimezoneSet($('#select-filter-item').val(), successCallback, errorCallback);
    }

    function loadTimezones() {
        const timezones = [{abbr: "ACDT", name: "Australian Central Daylight Savings Time", offset: "+10:30"}, {
            abbr: "ACST",
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


        timezones.forEach(function (timezone) {
            const option = '<option value="' + timezone.offset + '">' + timezone.name + ' (' + timezone.abbr + ')' + '</option>';
            $('#timezone').append(option);
        });
    }

})(window.BackendCalendarTimezoneModal);
