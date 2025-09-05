<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Accudocs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block mb-1 font-medium">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" 
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required autofocus>
            </div>

            <div class="mb-6">
                <label for="password" class="block mb-1 font-medium">Password</label>
                <input type="password" name="password" id="password" 
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       required>
            </div>

            <div class="flex items-center justify-between mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="remember" class="form-checkbox">
                    <span class="ml-2 text-sm text-gray-700">Remember Me</span>
                </label>

                <a href="{{route('focalperson.register.form')}}" class="text-sm text-blue-500 hover:underline">No Account?</a>
            </div>

            <button type="submit" 
                    class="w-full bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-600 transition">
                Login
            </button>
        </form>
    </div>

</body>
</html>
