<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Customer Contact Notification</title>
</head>

<body>
    <h2>New Contact Notification</h2>
    <p>Hello Admin,</p>
    <p>A new Contact has been submitted. Here are the details:</p>

    <table>
        <tr>
            <th>Name: </th>
            <td>{{ $data['name'] }}</td>
        </tr>
        <tr>
            <th>Email: </th>
            <td>{{ $data['email_id'] }}</td>
        </tr>
        <tr>
            <th>Contact: </th>
            <td> {{ $data['mobile'] }}</td>
        </tr>
        <tr>
            <th>Message: </th>
            <td>{{ $data['meaasge'] }}</td>
        </tr>
    </table>
    <br>
    <br>
    <p>Please take appropriate action as needed.</p>
    <p>Thank you!</p>
</body>

</html>
