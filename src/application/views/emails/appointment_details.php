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
            <td colspan="3" style="padding: 20px">$email_title</td>
        </tr>
        <tr>
            <td colspan="3">
                <img style="max-width: 100%; max-height: 150px;" src="slack.jpg">
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <h4><b> Hello $customer_name</b></h4>
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
            <td width="33%" valign="top">
                <h4>Service</h4>
                <p>$appointment_service</p>
            </td>
            <td width="33%" valign="top">
                <h4>Start Time</h4>
                <p>$appointment_start_date</p>
            </td>
            <td width="33%" valign="top">
                <h4>End Time</h4>
                <p>$appointment_end_date</p>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <h2>Your Information</h2>
                <span style="display:block; width: 70%; margin: auto ;border-bottom: 2px solid #E53D3D"></span>
            </td>
        </tr>
        <tr>
            <td style="margin: 0" colspan="3">
                <h3 style="margin-bottom: 0">Your Name</h3>
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
