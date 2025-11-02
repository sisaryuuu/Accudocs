<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Accudocs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 via-indigo-700 to-indigo-900 p-4">

    <div class="bg-white/90 rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row w-full max-w-5xl">
   <!-- Left: Login Form -->
<div class="w-full md:w-1/2 p-8 flex flex-col justify-center bg-white relative">

    <!-- ✅ Accudocs Logo (Smaller + at the top) -->
   <!-- ✅ Accudocs Logo (Top + Medium Size) -->
<div class="absolute top-6 left-1/2 transform -translate-x-1/2">
    <img src="{{ asset('storage/logo/accudocslogo.png') }}" 
         alt="Accudocs Logo" 
         class="w-50 h-auto drop-shadow-md">
</div>

<br><br><br>
    <div class="mt-20"> <!-- pushes form content below the logo -->
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Welcome!</h2>
        <p class="text-gray-500 text-center mb-6">Sign in to continue</p>

        <!-- QR Redirect Message -->
        @if(session('qr_redirect_token') || request()->has('qr_redirect'))
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    <span class="text-blue-800 text-sm">QR code scanned! Please log in to access the DV status update.</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            @if(session('qr_redirect_token'))
                <input type="hidden" name="qr_redirect_token" value="{{ session('qr_redirect_token') }}">
            @endif

            @if(request()->has('qr_redirect'))
                <input type="hidden" name="qr_redirect_token" value="{{ request('qr_redirect') }}">
            @endif

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" 
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400" 
                       placeholder="Enter your email" required autofocus>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" id="password" 
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400" 
                       placeholder="Enter your password" required>
            </div>

            <div class="flex items-center justify-between mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="remember" class="form-checkbox text-indigo-600">
                    <span class="ml-2 text-gray-600 text-sm">Remember Me</span>
                </label>

                <a href="{{ route('focalperson.register.form') }}" class="text-indigo-600 hover:underline text-sm font-medium">
                    Register
                </a>
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition font-semibold">
                Login
            </button>

            @if(session('qr_redirect_token') || request()->has('qr_redirect'))
                <div class="mt-3 text-center">
                    <p class="text-xs text-gray-600">
                        <strong>Note:</strong> QR code access requires <strong>Cashier</strong> or <strong>Regional Director</strong> role.
                    </p>
                </div>
            @endif
        </form>
    </div>
</div>


        <!-- Right: DOLE Logo Card (White Background) -->
        <div class="hidden md:flex w-1/2 bg-white items-center justify-center relative">
            <div class="relative z-10 flex flex-col items-center text-center px-8">
                <img src="{{ asset('storage/logo/DOLE.png') }}" alt="DOLE Logo" class="w-36 mb-4 drop-shadow-md">
                <h3 class="text-gray-800 text-2xl font-bold mb-2 tracking-wide">Department of Labor and Employment</h3>
                <p class="text-gray-600 text-sm leading-relaxed max-w-sm">
                    Empowering people. Securing livelihoods.  
                    Streamlining document processing with <span class="font-semibold text-indigo-600">Accudocs</span>.
                </p>
            </div>

            <!-- Subtle divider line -->
            <div class="absolute left-0 top-0 bottom-0 w-[1px] bg-gray-200"></div>
        </div>
    </div>

</body>
</html>
