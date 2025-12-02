<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Focal Person</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tin-input {
            letter-spacing: 1px;
        }
        .tin-input::placeholder {
            letter-spacing: normal;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-2xl bg-white shadow-xl rounded-2xl p-10 border border-gray-100">

        <div class="text-center mb-10">
            <div class="flex justify-center mb-6">
                <img 
                    src="{{ asset('storage/logo/accudocslogo.png') }}" 
                    alt="Accudocs Logo" 
                    class="h-32 w-auto object-contain mx-auto"
                >
            </div>
            <h2 class="text-4xl font-extrabold text-gray-800 tracking-tight">Register Focal Person</h2>
            <p class="text-gray-500 text-base mt-2">Fill out the form below to create your account</p>
        </div>
                    
        <!-- Form -->
        <form method="POST" action="{{ route('focalperson.register') }}" class="space-y-6">
            @csrf
            
            <!-- Display Validation Errors -->
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <strong class="font-medium">Please fix the following errors:</strong>
                    <ul class="list-disc list-inside mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Display Success Message -->
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <strong class="font-medium">Success!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Grid Layout for Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- First Name -->
                <div>
                    <label for="fname" class="block text-gray-700 font-medium mb-1">First Name</label>
                    <input type="text" id="fname" name="fname" value="{{ old('fname') }}" placeholder="Enter your first name"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('fname') border-red-500 @enderror">
                    @error('fname')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Middle Name -->
                <div>
                    <label for="mi" class="block text-gray-700 font-medium mb-1">Middle Name</label>
                    <input type="text" id="mi" name="mi" value="{{ old('mi') }}" placeholder="Enter your middle name"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('mi') border-red-500 @enderror">
                    @error('mi')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Last Name -->
                <div>
                    <label for="lname" class="block text-gray-700 font-medium mb-1">Last Name</label>
                    <input type="text" id="lname" name="lname" value="{{ old('lname') }}" placeholder="Enter your last name"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('lname') border-red-500 @enderror">
                    @error('lname')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- TIN Number -->
                <div>
                    <label for="tin-number" class="block text-gray-700 font-medium mb-1">TIN Number</label>
                    <input type="text" id="tin-number" name="TIN_number" value="{{ old('TIN_number') }}" placeholder="XXX-XXX-XXX-XXXX" 
                        class="tin-input w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('TIN_number') border-red-500 @enderror">
                    <p class="text-gray-400 text-xs mt-1">Format: 3-3-3-4 (e.g., 123-456-789-0123)</p>
                    @error('TIN_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Address -->
            <div>
                <label for="address" class="block text-gray-700 font-medium mb-1">Address</label>
                <input type="text" id="address" name="Address" value="{{ old('Address') }}" placeholder="Enter your address"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('Address') border-red-500 @enderror">
                @error('Address')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contact Number -->
            <div>
                <label for="contactnumber" class="block text-gray-700 font-medium mb-1">Contact Number</label>
                <input type="text" id="contactnumber" name="contactnumber" value="{{ old('contactnumber') }}" placeholder="09XXXXXXXXX" 
                    maxlength="11" pattern="[0-9]{11}"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('contactnumber') border-red-500 @enderror">
                <p class="text-gray-400 text-xs mt-1">Must be exactly 11 digits (e.g., 09123456789)</p>
                @error('contactnumber')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Responsibility Center -->
            <div>
                <label for="respocenter_id" class="block text-gray-700 font-medium mb-1">Responsibility Center</label>
                <select id="respocenter_id" name="respocenter_id"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('respocenter_id') border-red-500 @enderror">
                    <option value="">Select Responsibility Center</option>
                    @foreach ($responCenters as $responCenter)
                        <option value="{{ $responCenter->id }}" {{ old('respocenter_id') == $responCenter->id ? 'selected' : '' }}>
                            {{ $responCenter->rccode }} - {{ $responCenter->description }}
                        </option>
                    @endforeach
                </select>
                @error('respocenter_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Password -->
                <div>
                    <label for="password" class="block text-gray-700 font-medium mb-1">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-gray-700 font-medium mb-1">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        placeholder="Re-enter your password"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-200 transition duration-200 px-4 py-2.5 shadow-sm">
                    <!-- Client-side validation error message -->
                    <p id="password-error" class="text-red-500 text-sm mt-2 hidden">Passwords do not match.</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-center space-x-4 pt-6">
                <button type="submit"
                    class="px-8 py-2.5 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Register
                </button>

                <a href="{{ route('login') }}">
                    <button type="button"
                        class="px-8 py-2.5 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-600 transition transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                </a>
            </div>

            <!-- Login Redirect -->
            <div class="text-center pt-4">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium transition duration-200">
                        Login here
                    </a>
                </p>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Password validation
            const password = document.getElementById('password');
            const confirm = document.getElementById('password_confirmation');
            const errorMsg = document.getElementById('password-error');

            function validatePassword() {
                if (confirm.value && password.value !== confirm.value) {
                    confirm.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
                    confirm.classList.remove('border-gray-300', 'focus:border-blue-400', 'focus:ring-blue-200');
                    errorMsg.classList.remove('hidden');
                } else {
                    confirm.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
                    confirm.classList.add('border-gray-300', 'focus:border-blue-400', 'focus:ring-blue-200');
                    errorMsg.classList.add('hidden');
                }
            } 

            password.addEventListener('input', validatePassword);
            confirm.addEventListener('input', validatePassword);
            
            // TIN Number formatting
            const tinInput = document.getElementById('tin-number');
            
            tinInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                // Format as 3-3-3-4 but don't require all digits
                let formattedValue = '';
                
                if (value.length > 0) {
                    formattedValue = value.substring(0, 3);
                }
                if (value.length > 3) {
                    formattedValue += '-' + value.substring(3, 6);
                }
                if (value.length > 6) {
                    formattedValue += '-' + value.substring(6, 9);
                }
                if (value.length > 9) {
                    formattedValue += '-' + value.substring(9, 13);
                }
                
                e.target.value = formattedValue;
            });
            
            // Allow backspace to work properly
            tinInput.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace') {
                    // Let backspace work normally
                    return true;
                }
            });

            // Remove dashes from TIN before form submission
            document.querySelector('form').addEventListener('submit', function(e) {
                const tinInput = document.getElementById('tin-number');
                if (tinInput.value) {
                    tinInput.value = tinInput.value.replace(/\D/g, '');
                }
            });

            // Auto-remove error styling when user starts typing in fields with errors
            const errorInputs = document.querySelectorAll('.border-red-500');
            errorInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('border-red-500');
                    const errorElement = this.nextElementSibling;
                    if (errorElement && errorElement.classList.contains('text-red-500')) {
                        errorElement.remove();
                    }
                });
            });
        });
    </script>
</body>
</html>