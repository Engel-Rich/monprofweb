<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <title>@yield('title', 'Administration') · MONPROF</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('login-style')
    @yield('register-style')
</head>
<body>
    @yield('nav')
    @yield('login')
    @yield('register')

    @livewireScripts
    @yield('update_classe_scripte')
</body>
</html>
