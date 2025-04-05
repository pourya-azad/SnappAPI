<!-- resources/views/test-pusher.blade.php -->
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست Pusher</title>
    <!-- لود فایل‌های کامپایل‌شده توسط Vite -->
    @vite(['resources/js/app.js'])
    <!-- توکن CSRF برای احراز هویت -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<h1>تست اتصال به Pusher</h1>
<div id="messages"></div>

<script type="module">
    Echo.private('drivers.8')
        .listen('.ride.request', (e) => {
            console.log('رویداد دریافت شد: ', e);
            document.getElementById('messages').innerHTML += '<p>پیام: ' + JSON.stringify(e) + '</p>';
        })
        .subscribed(() => {
            console.log('با موفقیت به private-drivers.8 وصل شدم');
            document.getElementById('messages').innerHTML += '<p>وصل شدم به کانال</p>';
        })
        .error((error) => {
            console.log('خطا توی سابسکرایب: ', error);
            document.getElementById('messages').innerHTML += '<p>خطای سابسکرایب: ' + error + '</p>';
        });
</script>
</body>
</html>
