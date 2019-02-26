<html>
<head>
    <title>Appointment Details</title>
</head>
<body style="font: 13px arial, helvetica, tahoma;">
<div>
    <table border="0" width="100%" style="text-align: center">
        <tr>
            <td colspan="3" height="50px" bgcolor="#1d1d1d"></td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 20px;font-size: 20px;font-weight: bold">$email_title</td>
        </tr>
        <tr style="display: $logo_display">
            <td colspan="3">
                <img style="max-width: 100%; max-height: 150px;" src="$logo_url">
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <h4><b> Hello $user_name</b></h4>
                <p>$email_message</p>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <h2>Booking Details</h2>
                <span style="display:block; width: 70%; margin: auto ;border-bottom: 2px solid #E53D3D"></span>
            </td>
        </tr>
        <tr>
            <td width="33%" valign="top" style="text-align: right">
                <h4>Service</h4>
                <p>$appointment_service</p>
            </td>
            <td width="33%" valign="top" style="text-align: center">
                <h4>Start Time</h4>
                <p>$appointment_start_date</p>
            </td>
            <td width="33%" valign="top" style="text-align: left">
                <h4>End Time</h4>
                <p>$appointment_end_date</p>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <h2>Calendar Event</h2>
                <span style="display:block; width: 70%; margin: auto ;border-bottom: 2px solid #E53D3D"></span>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <p>If this event is not already in your calendar, you may add it from here:</p>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/48/Outlook.com_icon.svg/2000px-Outlook.com_icon.svg.png" style="width: 30px;height: 30px">
                <p>$outlook_url</p>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <img src="http://www.stickpng.com/assets/images/5847f9cbcef1014c0b5e48c8.png" style="width: 30px;height: 30px">
                <p>$google_url</p>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <img src="https://image.flaticon.com/icons/png/512/14/14288.png" style="width: 30px;height: 30px">
                <p>$yahoo_url</p>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <h2>$user_type Information</h2>
                <span style="display:block; width: 70%; margin: auto ;border-bottom: 2px solid #E53D3D"></span>
            </td>
        </tr>
        <tr>
            <td style="margin: 0" colspan="3">
                <h3 style="margin-bottom: 0">Name</h3>
                <p style="margin: 5px auto">$customer_name</p>
            </td>
        </tr>
        <tr>
            <td style="margin: 0" colspan="3">
                <h3 style="margin-bottom: 0">Email Address</h3>
                <p style="margin: 5px auto">$customer_email</p>
            </td>
        </tr>
        <tr>
            <td style="margin: 0" colspan="3">
                <h3 style="margin-bottom: 0">Phone Number</h3>
                <p style="margin: 5px auto">$customer_phone</p>
            </td>
        </tr>
        <tr>
            <td style="margin: 0" colspan="3">
                <h3 style="margin-bottom: 0">Address</h3>
                <p style="margin: 5px auto">$customer_address</p>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
