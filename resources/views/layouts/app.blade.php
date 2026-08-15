<!DOCTYPE html>
<html lang="en">

<head>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'CBM System')
    </title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="bg-slate-100 min-h-screen">


    {{-- =====================================================
        MAIN NAVIGATION
    ====================================================== --}}

    @if(request()->routeIs('home') === false)

        <div class="px-6 pt-6">

            <a
                href="{{ route('home') }}"
                class="inline-flex items-center gap-2
                       bg-gray-600 hover:bg-gray-700
                       text-white
                       px-5 py-3
                       rounded-xl
                       font-medium
                       transition"
            >

                ← Main Menu

            </a>

        </div>

    @endif


    {{-- =====================================================
        PAGE CONTENT
    ====================================================== --}}

    <main class="px-6 py-6">

        @yield('content')

    </main>


</body>

</html>