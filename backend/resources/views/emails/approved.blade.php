<!DOCTYPE html>
<html>
<head>
    <title>Account Approved</title>
</head>
<body>
    <h1>Hello {{ $user->name }}!</h1>
    <p>Your account has been approved by the administrator.</p>
    <p>You can now log in to the Kandahar University Research Database (KURD).</p>
    <p><a href="http://localhost:8081/auth/login">Click here to log in</a></p>
    <p>Thank you,<br>Vice Chancellery of Research</p>
</body>
</html>