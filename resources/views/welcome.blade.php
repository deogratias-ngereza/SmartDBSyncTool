<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Welcome to SmartDBSync</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Styles -->
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #FDFDFC;
            color: #1b1b18;
            font-family: 'instrument-sans', sans-serif;
        }
    </style>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] flex justify-center items-center min-h-screen flex-col p-8">
    <header class="w-full lg:max-w-4xl text-sm mb-6">
        <h1 class="text-2xl font-semibold mb-4">Welcome to SmartDBSync!</h1>
        <p class="mb-2">Synchronize Multiple Databases Seamlessly</p>
        <p class="text-gray-700">SmartDBSync is designed to streamline the synchronization of various databases across different platforms. Stay connected, ensure data integrity, and manage transitions smoothly.</p>
        
        <div class="mt-4">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                Get Started
            </a>
            <a href="{{ route('login') }}" class="ml-2 px-4 py-2 border border-blue-500 text-blue-500 rounded hover:bg-blue-500 hover:text-white transition">
                Log In
            </a>
            <a href="{{ route('register') }}" class="ml-2 px-4 py-2 border border-blue-500 text-blue-500 rounded hover:bg-blue-500 hover:text-white transition">
                Register
            </a>
        </div>
    </header>
</body>
</html>