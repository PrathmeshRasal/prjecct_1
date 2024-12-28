<!DOCTYPE html>
<html>
<head>
    <title>Registration Confirmation</title>
</head>
<body>
    <h1>Hello, {{ $data['name'] }}!</h1>
    <p>{{ $data['message'] }}</p>
    <ul>
        <li>name: {{ $data['name'] }}</li>
        <li>Email: {{ $data['email'] }}</li>
        <li>Mobile: {{ $data['mobile'] }}</li>
        <li>Address: {{ $data['address'] }}</li>
    </ul>
</body>
</html>
