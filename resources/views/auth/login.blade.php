<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Accudocs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-b from-indigo-500 to-indigo-900">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-gray-800">Welcome!</h2>
    <p class="text-gray-500 text-center mb-6">Sign in to continue</p>

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" 
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-indigo-300" placeholder="Enter your email"
                       required autofocus>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" id="password" 
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-indigo-300" placeholder="Enter your password"
                       required>
            </div>

            <div class="flex items-center justify-between mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="remember" class="form-checkbox text-indigo-600">
                    <span class="ml-2 text-gray-600 text-sm">Remember Me</span>
                </label>

                <a href="{{route('focalperson.register.form')}}" class="text-indigo-600 hover:underline">Register</a>
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
                Login
            </button>
        </form>
    </div>

</body>
</html>
