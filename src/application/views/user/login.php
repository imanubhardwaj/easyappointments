<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#35A768">
    <title><?= lang('login') . ' - ' . $company_name ?></title>

    <script src="<?= asset_url('assets/ext/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/bootstrap/js/bootstrap.min.js') ?>"></script>
    <script src="<?= asset_url('assets/ext/datejs/date.js') ?>"></script>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/jquery-ui/jquery-ui.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
	<link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">

    <style>
        body {
            width: 100vw;
            height: 100vh;
            display: table-cell;
            vertical-align: middle;
            background-color: #CAEDF3;
        }

        #login-frame {
            width: 630px;
            margin: auto;
            background: #FFF;
            border: 1px solid #DDDADA;
            padding: 70px;
        }

        @media(max-width: 640px) {
            #login-frame {
                width: 100%;
                padding: 20px;
            }
        }
    </style>

    <script>
        var GlobalVariables = {
            'csrfToken': <?= json_encode($this->security->get_csrf_hash()) ?>,
            'baseUrl': <?= json_encode($base_url) ?>,
            'destUrl': <?= json_encode($dest_url) ?>,
            'AJAX_SUCCESS': 'SUCCESS',
            'AJAX_FAILURE': 'FAILURE'
        };

        var EALang = <?= json_encode($this->lang->language) ?>;
        var availableLanguages = <?= json_encode($this->config->item('available_languages')) ?>;

        const code = window.location.href.split('code=')[1];
        if(code) {
            const url = 'https://easy.dev/index.php/user/ajax_login_by_code';
            $.ajax({
                type: 'POST',
                url: url,
                data: {'code': code},
                async: false
            }).done(function (response) {
                if (response && response === 'SUCCESS') {
                    // TODO use global variables
                    window.location.href = 'https://easy.dev/index.php/backend';
                }
            });
        }

        $(document).ready(function() {

            /**
             * Event: Login Button "Click"
             *
             * Make an ajax call to the server and check whether the user's credentials are right.
             * If yes then redirect him to his desired page, otherwise display a message.
             */
            $('#login-form').submit(function(event) {
                event.preventDefault();

                var postUrl = GlobalVariables.baseUrl + '/index.php/user/ajax_check_login';
                var postData = {
                    'csrfToken': GlobalVariables.csrfToken,
                    'username': $('#username').val(),
                    'password': $('#password').val()
                };

                $('.alert').addClass('hidden');
                $.ajax({
                    type: 'POST',
                    url: postUrl,
                    data: postData,
                    success: function (response) {
                        if (response === 'SUCCESS') {
                            window.location.href = 'https://easy.dev/index.php/backend';
                        } else {
                            $('.alert').text(EALang['login_failed']);
                            $('.alert')
                                .removeClass('hidden alert-danger alert-success')
                                .addClass('alert-danger');
                        }
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div id="login-frame" class="frame-container">
        <h2><?= lang('backend_section') ?></h2>
        <p><?= lang('you_need_to_login') ?></p>
        <hr>
        <div class="alert hidden"></div>
        <form id="login-form">
            <div class="form-group">
                <label for="username"><?= lang('email') ?></label>
                <input type="text" id="username"
                		placeholder="Enter Email Here"
                		class="form-control" />
            </div>
            <div class="form-group">
                <label for="password"><?= lang('password') ?></label>
                <input type="password" id="password"
                		placeholder="<?= lang('enter_password_here') ?>"
                		class="form-control" />
            </div>
            <br>

            <button type="submit" id="login" class="btn btn-primary">
            	<?= lang('login') ?>
            </button>

            <br><br>
        </form>
    </div>
</body>
</html>
