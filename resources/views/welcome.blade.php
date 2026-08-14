<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>CBM System</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="bg-slate-100 min-h-screen">


<div class="max-w-6xl mx-auto px-6 py-12">


    {{-- HEADER --}}
    <div class="relative text-center mb-10">

        <h1 class="text-4xl font-bold text-blue-900">
            CBM System
        </h1>

        <p class="text-gray-500 mt-2 text-lg">
            Condition Based Maintenance Monitoring System
        </p>


        {{-- LOGOUT --}}
        <form
            action="{{ route('logout') }}"
            method="POST"
            class="absolute right-0 top-0"
        >

            @csrf

            <button
                type="submit"
                class="bg-red-600 hover:bg-red-700
                       text-white font-semibold
                       px-4 py-2 rounded-lg
                       shadow-sm transition duration-200"
            >
                Logout
            </button>

        </form>

    </div>



    {{-- MAIN MENU --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">


        {{-- DASHBOARD --}}
        <a
            href="{{ route('dashboard') }}"
            class="bg-white rounded-2xl p-6 shadow-sm
                   hover:shadow-lg hover:-translate-y-1
                   transition duration-200"
        >

            <div class="text-3xl mb-4">
                📊
            </div>

            <h2 class="text-xl font-bold text-blue-900">
                Dashboard
            </h2>

            <p class="text-gray-500 mt-2">
                View overall CBM equipment condition
                and monitoring summary.
            </p>

        </a>



        {{-- EQUIPMENT --}}
        <a
            href="{{ route('equipment.index') }}"
            class="bg-white rounded-2xl p-6 shadow-sm
                   hover:shadow-lg hover:-translate-y-1
                   transition duration-200"
        >

            <div class="text-3xl mb-4">
                ⚙️
            </div>

            <h2 class="text-xl font-bold text-blue-900">
                Equipment
            </h2>

            <p class="text-gray-500 mt-2">
                Manage equipment master data
                and equipment information.
            </p>

        </a>



        {{-- MEASUREMENT POINT --}}
        <a
            href="{{ route('measurement-point.index') }}"
            class="bg-white rounded-2xl p-6 shadow-sm
                   hover:shadow-lg hover:-translate-y-1
                   transition duration-200"
        >

            <div class="text-3xl mb-4">
                📍
            </div>

            <h2 class="text-xl font-bold text-blue-900">
                Measurement Point
            </h2>

            <p class="text-gray-500 mt-2">
                Manage vibration measurement points
                for each equipment.
            </p>

        </a>



        {{-- INSPECTION SESSION --}}
        <a
            href="{{ route('inspection.index') }}"
            class="bg-white rounded-2xl p-6 shadow-sm
                   hover:shadow-lg hover:-translate-y-1
                   transition duration-200"
        >

            <div class="text-3xl mb-4">
                📋
            </div>

            <h2 class="text-xl font-bold text-blue-900">
                Inspection Session
            </h2>

            <p class="text-gray-500 mt-2">
                Create and manage equipment
                inspection sessions.
            </p>

        </a>



        {{-- MEASUREMENT RESULT --}}
        <a
            href="{{ route('measurement-result.index') }}"
            class="bg-white rounded-2xl p-6 shadow-sm
                   hover:shadow-lg hover:-translate-y-1
                   transition duration-200"
        >

            <div class="text-3xl mb-4">
                📈
            </div>

            <h2 class="text-xl font-bold text-blue-900">
                Measurement Result
            </h2>

            <p class="text-gray-500 mt-2">
                View and manage vibration
                measurement results.
            </p>

        </a>



        {{-- ODX IMPORT --}}
        <a
            href="{{ route('odx-import.create') }}"
            class="bg-white rounded-2xl p-6 shadow-sm
                   hover:shadow-lg hover:-translate-y-1
                   transition duration-200"
        >

            <div class="text-3xl mb-4">
                📥
            </div>

            <h2 class="text-xl font-bold text-blue-900">
                ODX Import
            </h2>

            <p class="text-gray-500 mt-2">
                Import vibration measurement data
                from ODX files.
            </p>

        </a>


    </div>



    {{-- FOOTER --}}
    <div class="text-center mt-10">

        <p class="text-sm text-gray-400">
            Condition Based Maintenance System
        </p>

    </div>


</div>


</body>

</html>