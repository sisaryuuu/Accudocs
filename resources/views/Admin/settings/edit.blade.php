<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Settings</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom styles for transitions and animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-down {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from { 
                opacity: 0;
                transform: translateY(-10px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Custom hover effects for inputs */
        .form-input-custom {
            transition: all 0.2s ease;
        }
        
        .form-input-custom:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen fade-in">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-cog text-blue-600 mr-3"></i>
                    Admin Settings
                </h1>
                <p class="text-gray-600 mt-2">Manage system configuration and user roles</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('Admin.dashboard') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-6 slide-down">
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-green-700 font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Settings Form Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden fade-in">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-user-tie mr-2 text-blue-600"></i>
                    Personnel Configuration
                </h2>
                <p class="text-gray-600 text-sm mt-1">Set the personnel responsible for different roles in the system</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="p-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- IMSD Head Field -->
                    <div class="form-group">
                        <label for="imsd_head" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user-cog mr-1 text-blue-500"></i>
                            IMSD Head
                        </label>
                        <div class="relative">
                            <input id="imsd_head" 
                                   type="text" 
                                   name="imsd_head" 
                                   value="{{ old('imsd_head', $imsd ?? '') }}"
                                   class="form-input-custom w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                   placeholder="Enter IMSD Head name">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                        </div>
                        @error('imsd_head') 
                            <div class="mt-1 flex items-center text-red-600 text-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Accountant Field -->
                    <div class="form-group">
                        <label for="accountant_name" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calculator mr-1 text-green-500"></i>
                            Accountant
                        </label>
                        <div class="relative">
                            <input id="accountant_name" 
                                   type="text" 
                                   name="accountant_name" 
                                   value="{{ old('accountant_name', $accountant ?? '') }}"
                                   class="form-input-custom w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200"
                                   placeholder="Enter Accountant name">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-file-invoice-dollar text-gray-400"></i>
                            </div>
                        </div>
                        @error('accountant_name') 
                            <div class="mt-1 flex items-center text-red-600 text-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Regional Director Field -->
                    <div class="form-group">
                        <label for="regional_director" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-map-marked-alt mr-1 text-purple-500"></i>
                            Regional Director
                        </label>
                        <div class="relative">
                            <input id="regional_director" 
                                   type="text" 
                                   name="regional_director" 
                                   value="{{ old('regional_director', $regional ?? '') }}"
                                   class="form-input-custom w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition duration-200"
                                   placeholder="Enter Regional Director name">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-tie text-gray-400"></i>
                            </div>
                        </div>
                        @error('regional_director') 
                            <div class="mt-1 flex items-center text-red-600 text-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Budget Officer Field -->
                    <div class="form-group">
                        <label for="budget_officer" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-chart-pie mr-1 text-amber-500"></i>
                            Budget Officer
                        </label>
                        <div class="relative">
                            <input id="budget_officer" 
                                   type="text" 
                                   name="budget_officer" 
                                   value="{{ old('budget_officer', $budgetofficer ?? '') }}"
                                   class="form-input-custom w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition duration-200"
                                   placeholder="Enter Budget Officer name">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-money-check-alt text-gray-400"></i>
                            </div>
                        </div>
                        @error('budget_officer') 
                            <div class="mt-1 flex items-center text-red-600 text-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center">
                    <div class="mb-4 sm:mb-0 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Changes will be applied immediately after saving.
                    </div>
                    <div class="flex space-x-3">
                        <button type="submit" 
                                class="px-5 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition duration-200 flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Information Panel -->
        <div class="mt-8 bg-blue-50 border border-blue-100 rounded-xl p-5 fade-in">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb text-blue-500 text-xl mt-1"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-blue-800">About These Settings</h3>
                    <div class="mt-2 text-blue-700 text-sm">
                        <p>These personnel settings are used throughout the system for:</p>
                        <ul class="mt-2 ml-5 list-disc space-y-1">
                            <li>Approval workflows and authorization processes</li>
                            <li>Document signatures and certifications</li>
                            <li>System notifications and alerts</li>
                            <li>Report headers and official documentation</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript for enhanced interactions -->
    <script>
        // Add focus and interaction effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"]');
            
            inputs.forEach(input => {
                // Add focus/blur effects
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-opacity-50');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-opacity-50');
                });
                
                // Add validation styling on input
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.classList.remove('border-gray-300');
                        this.classList.add('border-blue-400');
                    } else {
                        this.classList.remove('border-blue-400');
                        this.classList.add('border-gray-300');
                    }
                });
            });
        });
    </script>
</body>
</html>