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
        
        /* Budget card styles */
        .budget-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .budget-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Color coding for programs */
        .tupad-card {
            border-left-color: #3B82F6;
            background: linear-gradient(to right, #EBF5FF, #EFF6FF);
        }
        
        .gip-card {
            border-left-color: #10B981;
            background: linear-gradient(to right, #ECFDF5, #F0FDF9);
        }
        
        .tev-card {
            border-left-color: #8B5CF6;
            background: linear-gradient(to right, #F5F3FF, #FAF5FF);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen fade-in">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-cog text-blue-600 mr-3"></i>
                    Admin Settings
                </h1>
                <p class="text-gray-600 mt-2">Manage system configuration, budgets, and user roles</p>
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
        <div class="bg-white rounded-xl shadow-lg overflow-hidden fade-in mb-8">
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
                                   value="{{ old('imsd_head', $settings['imsd_head'] ?? '') }}"
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
                                   value="{{ old('accountant_name', $settings['accountant_name'] ?? '') }}"
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
                                   value="{{ old('regional_director', $settings['regional_director'] ?? '') }}"
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
                                   value="{{ old('budget_officer', $settings['budget_officer'] ?? '') }}"
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

                <!-- Budget Allocation Section -->
                <div class="mt-10 pt-8 border-t border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-wallet mr-2 text-green-600"></i>
                            Budget Allocations
                        </h3>
                        <p class="text-gray-600 text-sm">Set annual budget allocations for each program. These values are used for budget monitoring and alerts.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- TUPAD Budget -->
                        <div class="budget-card tupad-card p-5 rounded-lg">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-hands-helping text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">TUPAD</h4>
                                    <p class="text-xs text-gray-500">Tulong Panghanapbuhay</p>
                                </div>
                            </div>
                            <label for="budget_tupad" class="block text-sm font-medium text-gray-700 mb-2">
                                Annual Budget Allocation
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">₱</span>
                                </div>
                                <!-- Changed from type="number" to type="text" -->
                                <input id="budget_tupad" 
                                       type="text" 
                                       name="budget_tupad" 
                                       value="{{ old('budget_tupad', number_format($settings['budget_tupad'] ?? 10000000, 2)) }}"
                                       class="form-input-custom w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                       placeholder="0.00"
                                       data-original="{{ $settings['budget_tupad'] ?? 10000000 }}">
                            </div>
                            <div class="mt-3 text-xs text-gray-500">
                                Current: ₱{{ number_format($settings['budget_tupad'] ?? 10000000, 2) }}
                            </div>
                            @error('budget_tupad') 
                                <div class="mt-1 flex items-center text-red-600 text-sm">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- GIP Budget -->
                        <div class="budget-card gip-card p-5 rounded-lg">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-briefcase text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">GIP</h4>
                                    <p class="text-xs text-gray-500">Government Internship Program</p>
                                </div>
                            </div>
                            <label for="budget_gip" class="block text-sm font-medium text-gray-700 mb-2">
                                Annual Budget Allocation
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">₱</span>
                                </div>
                                <!-- Changed from type="number" to type="text" -->
                                <input id="budget_gip" 
                                       type="text" 
                                       name="budget_gip" 
                                       value="{{ old('budget_gip', number_format($settings['budget_gip'] ?? 5000000, 2)) }}"
                                       class="form-input-custom w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200"
                                       placeholder="0.00"
                                       data-original="{{ $settings['budget_gip'] ?? 5000000 }}">
                            </div>
                            <div class="mt-3 text-xs text-gray-500">
                                Current: ₱{{ number_format($settings['budget_gip'] ?? 5000000, 2) }}
                            </div>
                            @error('budget_gip') 
                                <div class="mt-1 flex items-center text-red-600 text-sm">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- TEV Budget -->
                        <div class="budget-card tev-card p-5 rounded-lg">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-graduation-cap text-purple-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">TEV</h4>
                                    <p class="text-xs text-gray-500">Training for Enterprise Development</p>
                                </div>
                            </div>
                            <label for="budget_tev" class="block text-sm font-medium text-gray-700 mb-2">
                                Annual Budget Allocation
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">₱</span>
                                </div>
                                <!-- Changed from type="number" to type="text" -->
                                <input id="budget_tev" 
                                       type="text" 
                                       name="budget_tev" 
                                       value="{{ old('budget_tev', number_format($settings['budget_tev'] ?? 3000000, 2)) }}"
                                       class="form-input-custom w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition duration-200"
                                       placeholder="0.00"
                                       data-original="{{ $settings['budget_tev'] ?? 3000000 }}">
                            </div>
                            <div class="mt-3 text-xs text-gray-500">
                                Current: ₱{{ number_format($settings['budget_tev'] ?? 3000000, 2) }}
                            </div>
                            @error('budget_tev') 
                                <div class="mt-1 flex items-center text-red-600 text-sm">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Budget Summary -->
                    <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-calculator text-gray-500 mr-2"></i>
                                <span class="text-sm font-medium text-gray-700">Total Annual Budget Allocation:</span>
                            </div>
                            <div class="text-lg font-bold text-gray-800" id="totalBudget">
                                ₱{{ number_format(($settings['budget_tupad'] ?? 0) + ($settings['budget_gip'] ?? 0) + ($settings['budget_tev'] ?? 0), 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-10 pt-8 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center">
                    <div class="mb-4 sm:mb-0 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Changes will be applied immediately. Budget changes affect all monitoring and alerts.
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" onclick="resetBudgets()"
                                class="px-5 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition duration-200 flex items-center">
                            <i class="fas fa-redo mr-2"></i>
                            Reset Budgets
                        </button>
                        <button type="submit" 
                                class="px-5 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition duration-200 flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Save All Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Information Panel -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden fade-in">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                    About These Settings
                </h2>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-user-tie text-blue-500 mr-2"></i>
                            Personnel Settings
                        </h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Used for approval workflows and authorization processes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Displayed on document signatures and certifications</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Required for system notifications and alerts</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                <span>Included in report headers and official documentation</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-wallet text-green-500 mr-2"></i>
                            Budget Settings
                        </h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-chart-line text-blue-500 mt-1 mr-2"></i>
                                <span>Used for real-time budget monitoring and utilization tracking</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-bell text-amber-500 mt-1 mr-2"></i>
                                <span>Triggers alerts when utilization reaches critical thresholds (75%, 90%, 100%)</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-ban text-red-500 mt-1 mr-2"></i>
                                <span>Automatically blocks new DV requests when budget is exceeded</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-file-contract text-purple-500 mt-1 mr-2"></i>
                                <span>Provides budget analysis for financial reporting and planning</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-lightbulb text-blue-500 text-xl mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">Budget Alert Thresholds</h4>
                            <div class="mt-2 grid grid-cols-1 md:grid-cols-4 gap-2 text-sm">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                    <span class="text-gray-700">Healthy: &lt; 75%</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                                    <span class="text-gray-700">Warning: ≥ 75%</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-orange-500 mr-2"></div>
                                    <span class="text-gray-700">Critical: ≥ 90%</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                                    <span class="text-gray-700">Exceeded: ≥ 100%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for enhanced interactions -->
    <script>
        // Add focus and interaction effects
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize budget inputs formatting
            initializeBudgetInputs();
            
            // Update total budget initially
            updateTotalBudget();
            
            // Add focus styling for all inputs
            const allInputs = document.querySelectorAll('input[type="text"]');
            allInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-opacity-50');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-opacity-50');
                });
            });
        });
        
        // Initialize budget input formatting
        function initializeBudgetInputs() {
            const budgetInputs = document.querySelectorAll('input[name^="budget_"]');
            
            budgetInputs.forEach(input => {
                // Format on focus out
                input.addEventListener('blur', function() {
                    formatBudgetInput(this);
                    updateTotalBudget();
                });
                
                // Remove commas on focus for editing
                input.addEventListener('focus', function() {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                });
                
                // Allow only numbers and decimal point
                input.addEventListener('input', function(e) {
                    // Remove any non-numeric characters except decimal point
                    this.value = this.value.replace(/[^0-9.]/g, '');
                    
                    // Ensure only one decimal point
                    const parts = this.value.split('.');
                    if (parts.length > 2) {
                        this.value = parts[0] + '.' + parts.slice(1).join('');
                    }
                    
                    // Update total as user types
                    updateTotalBudget();
                });
            });
        }
        
        // Format a budget input with commas
        function formatBudgetInput(inputElement) {
            let value = inputElement.value;
            
            // Remove any non-numeric characters
            value = value.replace(/[^0-9.]/g, '');
            
            // If empty, set to 0
            if (value === '' || isNaN(parseFloat(value))) {
                value = '0';
            }
            
            // Parse to number
            const numValue = parseFloat(value);
            
            // Format with commas and 2 decimal places
            inputElement.value = numValue.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Get numeric value from formatted input
        function getNumericValue(inputElement) {
            if (!inputElement.value) return 0;
            
            // Remove commas and parse
            const value = inputElement.value.replace(/[^0-9.]/g, '');
            return parseFloat(value) || 0;
        }
        
        // Update total budget calculation
        function updateTotalBudget() {
            const tupad = getNumericValue(document.getElementById('budget_tupad'));
            const gip = getNumericValue(document.getElementById('budget_gip'));
            const tev = getNumericValue(document.getElementById('budget_tev'));
            
            const total = tupad + gip + tev;
            document.getElementById('totalBudget').textContent = 
                '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        // Reset budget fields to default values
        function resetBudgets() {
            if (confirm('Are you sure you want to reset all budget allocations to their default values?')) {
                const defaults = {
                    'budget_tupad': 0,
                    'budget_gip': 0,
                    'budget_tev': 0
                };
                
                Object.keys(defaults).forEach(id => {
                    const input = document.getElementById(id);
                    input.value = defaults[id].toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                });
                
                updateTotalBudget();
            }
        }
        
        // Before form submission, ensure values are formatted correctly
        document.querySelector('form').addEventListener('submit', function(e) {
            const budgetInputs = document.querySelectorAll('input[name^="budget_"]');
            budgetInputs.forEach(input => {
                // Ensure the value sent to server is a plain number without commas
                input.value = getNumericValue(input);
            });
        });
    </script>
</body>
</html>