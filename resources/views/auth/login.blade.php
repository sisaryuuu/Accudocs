<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Accudocs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .verification-pending {
            border-left: 4px solid #f59e0b;
            background-color: #fffbeb;
        }
        .verification-rejected {
            border-left: 4px solid #dc2626;
            background-color: #fef2f2;
        }
        .already-logged-in {
            border-left: 4px solid #10b981;
            background-color: #ecfdf5;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 via-indigo-700 to-indigo-900 p-4">

    <div class="bg-white/90 rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row w-full max-w-5xl">
        <!-- Left: Login Form -->
        <div class="w-full md:w-1/2 p-8 flex flex-col justify-center bg-white relative">

            <!-- Accudocs Logo (Top + Medium Size) -->
            <div class="absolute top-6 left-1/2 transform -translate-x-1/2">
                <img src="{{ asset('storage/logo/accudocslogo.png') }}" 
                     alt="Accudocs Logo" 
                     class="w-50 h-auto drop-shadow-md">
            </div>

            <br><br><br>
            <div class="mt-20"> <!-- pushes form content below the logo -->
                <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Welcome!</h2>
                <p class="text-gray-500 text-center mb-6">Sign in to continue</p>

                <!-- ✅ SUCCESS MESSAGE - UPDATED TO CHECK registration_success -->
                @if(session('registration_success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-4" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h4 class="text-green-800 font-medium">Registration Successful!</h4>
                                <p class="text-green-700 text-sm mt-1">{{ session('registration_success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Also check for regular success messages -->
                @if(session('success') && !session('registration_success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-4" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h4 class="text-green-800 font-medium">Success!</h4>
                                <p class="text-green-700 text-sm mt-1">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Already Logged In Message -->
                @if(session('already_logged_in'))
                    <div class="already-logged-in p-4 rounded mb-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h4 class="text-green-800 font-medium">Already Logged In</h4>
                                <p class="text-green-700 text-sm mt-1">You are already logged in. Redirecting to your dashboard...</p>
                                <div class="mt-2">
                                    <a href="{{ route('login.redirect') }}" class="text-green-600 hover:text-green-800 text-sm font-medium underline">
                                        Click here if not redirected automatically
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        // Auto-redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = "{{ route('login.redirect') }}";
                        }, 2000);
                    </script>
                @endif

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

                @if($errors->any() && !session('already_logged_in'))
                    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Only show login form if user is not already logged in -->
                @if(!session('already_logged_in'))
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
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('email') border-red-500 @enderror" 
                               placeholder="Enter your email" required autofocus>
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                        <input type="password" name="password" id="password" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('password') border-red-500 @enderror" 
                               placeholder="Enter your password" required>
                    </div>

                    <div class="flex items-center justify-between mb-6">
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
                @endif
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