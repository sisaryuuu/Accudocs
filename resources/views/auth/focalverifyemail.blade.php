<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email Address</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-green-600 text-white text-center py-6">
                <h1 class="text-xl font-bold">{{ __('Verify Your Email Address') }}</h1>
            </div>

            <div class="p-6 sm:p-8">
                <!-- Success Alerts -->
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex justify-between items-center">
                        <span>{{ session('success') }}</span>
                        <button type="button" class="text-green-700 hover:text-green-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if (session('status') == 'verification-link-sent')
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex justify-between items-center">
                        <span>{{ __('✅ A fresh verification link has been sent to your email address.') }}</span>
                        <button type="button" class="text-green-700 hover:text-green-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                <!-- Email Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-blue-800">
                    <p class="mb-3 flex items-start">
                        <i class="fas fa-envelope mr-2 mt-1"></i>
                        <span>We've sent a verification link to your email:</span>
                    </p>
                    <p class="text-green-600 font-bold text-center text-lg mb-3">
                        {{ $email ?? auth()->guard('focal')->user()->email ?? 'your email' }}
                    </p>
                    <p class="mb-2">Before proceeding, please check your inbox (and spam folder) for the verification link.</p>
                    <p>If you didn't receive the email, click below to request another one.</p>
                </div>

                <!-- Resend Verification Button -->
                <form method="POST" action="{{ route('focal.verification.send') }}" id="resend-form" class="mb-6">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg flex items-center justify-center" id="resend-button">
                        <span id="button-text">{{ __('Resend Verification Email') }}</span>
                        <div id="loading-spinner" class="hidden ml-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </form>

                <!-- Logout -->
                @if(auth()->guard('focal')->check())
                <div class="text-center">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-800 font-semibold transition-colors duration-200">
                            {{ __('← Return to Login') }}
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resendForm = document.getElementById('resend-form');
            const resendButton = document.getElementById('resend-button');
            const buttonText = document.getElementById('button-text');
            const loadingSpinner = document.getElementById('loading-spinner');

            // Close alert buttons
            document.querySelectorAll('button .fa-times').forEach(button => {
                button.closest('button').addEventListener('click', function() {
                    this.closest('.bg-green-100').style.display = 'none';
                });
            });

            if (resendForm && resendButton) {
                resendForm.addEventListener('submit', function(e) {
                    if (resendButton.disabled) {
                        e.preventDefault();
                        return;
                    }

                    // Disable button and show loading
                    resendButton.disabled = true;
                    resendButton.classList.remove('hover:bg-green-700', 'hover:-translate-y-1', 'hover:shadow-lg');
                    buttonText.textContent = 'Sending...';
                    loadingSpinner.classList.remove('hidden');
                    
                    // Re-enable after 30 seconds to prevent spam
                    setTimeout(() => {
                        resendButton.disabled = false;
                        resendButton.classList.add('hover:bg-green-700', 'hover:-translate-y-1', 'hover:shadow-lg');
                        buttonText.textContent = 'Resend Verification Email';
                        loadingSpinner.classList.add('hidden');
                    }, 30000);
                });
            }
        });
    </script>
</body>
</html>