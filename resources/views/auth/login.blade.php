<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CBM Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f2d5c;
        }

        .login-shell {
            width: 100%;
            max-width: 430px;
            padding: 24px;
        }

        .card {
            background: #ffffff;
            border-radius: 18px;
            padding: 34px;
            box-shadow: 0 15px 45px rgba(15, 45, 92, 0.10);
            border: 1px solid #e8edf3;
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .brand p {
            margin: 8px 0 0;
            color: #718096;
            font-size: 14px;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d6dde7;
            border-radius: 10px;
            outline: none;
            font-size: 14px;
        }

        input:focus {
            border-color: #0f2d5c;
            box-shadow: 0 0 0 3px rgba(15, 45, 92, 0.08);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 2px 0 20px;
            font-size: 13px;
            color: #718096;
        }

        .remember input {
            accent-color: #0f2d5c;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 12px;
            background: #0f2d5c;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #0b2348;
        }

        .error {
            margin-bottom: 18px;
            padding: 11px 12px;
            border-radius: 10px;
            background: #fff1f2;
            color: #b42318;
            font-size: 13px;
        }

        .footer {
            text-align: center;
            color: #98a2b3;
            font-size: 12px;
            margin-top: 18px;
        }
    </style>
</head>

<body>
    <main class="login-shell">
        <section class="card">

            <div class="brand">
                <h1>CBM Dashboard</h1>
                <p>Condition Based Maintenance Monitoring</p>
            </div>

            @if ($errors->any())
                <div class="error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="field">
                    <label for="email">Username</label>

                    <input
                        id="email"
                        type="text"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        placeholder="Masukkan username"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                <label class="remember">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                    >
                    Ingat saya
                </label>

                <button type="submit">
                    Login
                </button>
            </form>

        </section>

        <div class="footer">
            CBM Dashboard
        </div>
    </main>
</body>
</html>