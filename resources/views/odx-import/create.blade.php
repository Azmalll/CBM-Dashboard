@extends('layouts.app')

@section('title', 'Import ODX')

@section('content')

<div class="max-w-4xl mx-auto py-10 px-6">

    <div class="mb-8">

        <h1 class="text-3xl font-bold text-blue-900">
            ODX Data Import
        </h1>

        <p class="text-gray-500 mt-2">
            Import vibration measurement data from Omnitrend ODX file.
        </p>

    </div>

    <div
        id="odx-status"
        class="hidden mb-6 px-5 py-4 rounded-xl"
    ></div>

    <div class="bg-white rounded-2xl shadow-sm p-8">

        <form
            id="odx-import-form"
            method="POST"
        >

            @csrf

            <div class="mb-6">

                <label
                    for="odx_file"
                    class="block text-sm font-semibold
                           text-gray-700 mb-2"
                >
                    ODX File
                </label>

                <input
                    type="file"
                    id="odx_file"
                    name="odx_file"
                    accept=".odx"
                    required
                    class="block w-full border border-gray-300
                           rounded-xl px-4 py-3
                           bg-white text-gray-700"
                >

                <p class="text-xs text-gray-500 mt-2">
                    Select the ODX file exported from Omnitrend.
                    Maximum 50 MB.
                </p>

            </div>

            <div
                id="odx-progress"
                class="hidden mb-6
                       bg-blue-50 border border-blue-200
                       text-blue-800 px-5 py-4
                       rounded-xl font-semibold"
            ></div>

            <div
                class="bg-blue-50 border border-blue-200
                       rounded-xl p-5 mb-6"
            >

                <h2 class="font-semibold text-blue-800 mb-2">
                    Import Rules
                </h2>

                <ul class="text-sm text-blue-700 space-y-1">

                    <li>
                        • Data from Omnitrend will be mapped
                        to the CBM database.
                    </li>

                    <li>
                        • Measurement date and time are stored
                        separately for identification.
                    </li>

                    <li>
                        • Inspector can be assigned individually
                        from Measurement Results.
                    </li>

                    <li>
                        • Inspector can also be assigned in bulk
                        based on measurement date.
                    </li>

                    <li>
                        • Existing measurement data will not be
                        deleted unnecessarily.
                    </li>

                    <li>
                        • ODX files are stored temporarily in
                        Vercel Blob during processing.
                    </li>

                </ul>

            </div>

            <div class="flex items-center gap-3">

                <button
                    id="odx-import-button"
                    type="submit"
                    class="bg-blue-900 hover:bg-blue-950
                           text-white font-semibold
                           px-6 py-3 rounded-xl transition
                           disabled:opacity-50
                           disabled:cursor-not-allowed"
                >
                    Import ODX
                </button>

                <a
                    href="{{ route('home') }}"
                    class="bg-gray-200 hover:bg-gray-300
                           text-gray-700 font-semibold
                           px-6 py-3 rounded-xl transition"
                >
                    Back to Main Menu
                </a>

            </div>

        </form>

    </div>

</div>

@endsection