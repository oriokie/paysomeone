<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f5f3">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Order — '.$siteName)</title>
    <meta name="description" content="@yield('description', 'Place an order.')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-app font-sans text-slate-900 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2">Skip to the order</a>
    <header class="border-b border-slate-200/70 bg-app/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-[1200px] items-center justify-between px-5">
            <a href="{{ route('home') }}" class="font-display text-lg tracking-tight text-navy">{{ $siteName }}</a>
            <a href="{{ route('pricing') }}" class="text-[13px] font-medium text-slate-500 hover:text-slate-900">Rate card</a>
        </div>
    </header>
    <main id="content">
        @yield('content')
    </main>
</body>
</html>
