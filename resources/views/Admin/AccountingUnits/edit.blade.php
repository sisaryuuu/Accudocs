<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <title>Edit Accounting Unit</title>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-8">

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Header -->
    <div class="text-center mb-8 animate-fade-in">
      <h1 class="text-3xl font-bold text-gray-800">Edit Accounting Unit</h1>
      <p class="text-gray-500 mt-2">Update user details and role assignments</p>
    </div>

    <!-- User Info Card -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200 animate-fade-in">
      <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div class="w-20 h-20 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-2xl">
            {{ substr($accountingunit->fname, 0, 1) }}{{ substr($accountingunit->lname, 0, 1) }}
          </div>
        </div>
        
        <!-- User Details -->
        <div class="flex-grow text-center md:text-left">
          <h2 class="text-2xl font-bold text-gray-800">{{ $accountingunit->fname }} {{ $accountingunit->mi }} {{ $accountingunit->lname }}</h2>
          <p class="text-gray-600 mt-1">{{ $accountingunit->email }}</p>
          
          <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-3">
            <div class="text-center">
              <div class="text-sm text-gray-500">User ID</div>
              <div class="font-semibold text-gray-800">#{{ $accountingunit->id }}</div>
            </div>
            
            <div class="text-center">
              <div class="text-sm text-gray-500">Status</div>
              <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $accountingunit->hasVerifiedEmail() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                <i class="fas {{ $accountingunit->hasVerifiedEmail() ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-2"></i>
                {{ $accountingunit->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}
              </div>
            </div>
            
            <div class="text-center">
              <div class="text-sm text-gray-500">Roles</div>
              <div class="font-semibold text-gray-800">{{ $accountingunit->roles->count() }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <form action="{{ route('admin.accountingunits.update', $accountingunit) }}" method="POST" class="space-y-6 animate-fade-in" id="userForm">
      @csrf
      @method('PUT')

      <!-- Name Fields -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 transition-all duration-200 hover:shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-user-circle mr-2 text-blue-500"></i>
          Personal Information
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">First Name *</label>
            <input type="text" name="fname" value="{{ old('fname', $accountingunit->fname) }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200">
            @error('fname') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Middle Initial</label>
            <input type="text" name="mi" value="{{ old('mi', $accountingunit->mi) }}" maxlength="1"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200">
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name *</label>
            <input type="text" name="lname" value="{{ old('lname', $accountingunit->lname) }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200">
            @error('lname') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 transition-all duration-200 hover:shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-address-card mr-2 text-green-500"></i>
          Contact Information
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address *</label>
            <input type="email" name="email" value="{{ old('email', $accountingunit->email) }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200">
            @error('email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
            @if($accountingunit->hasVerifiedEmail())
              <p class="text-green-600 text-xs mt-1 flex items-center">
                <i class="fas fa-check-circle mr-1"></i> Email verified
              </p>
            @else
              <p class="text-amber-600 text-xs mt-1 flex items-center">
                <i class="fas fa-exclamation-triangle mr-1"></i> Email not verified
              </p>
            @endif
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Phone Number</label>
            <input type="tel" name="phone" value="{{ old('phone', $accountingunit->phone) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200">
            @error('phone') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
          </div>
        </div>
        
      <!-- Password Update -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 transition-all duration-200 hover:shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-lock mr-2 text-red-500"></i>
          Password Management
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
            <input type="password" name="password" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200"
                   placeholder="Leave blank to keep current">
            @error('password') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200"
                   placeholder="Confirm new password">
          </div>
        </div>
        <p class="text-gray-500 text-xs mt-2">Only fill if you want to change the password</p>
      </div>

      <!-- Multi-Role Management -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 transition-all duration-200 hover:shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-user-tag mr-2 text-purple-500"></i>
          Role Management
        </h3>
        
        <!-- Current Roles Display -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <h4 class="font-semibold text-gray-700 mb-3">Current Role Assignment</h4>
          <div id="currentRoles" class="flex flex-wrap gap-2 mb-3">
            @if($accountingunit->roles->count() > 0)
              @foreach($accountingunit->roles as $role)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border {{ $role->name === $primaryRole ? 'border-2 border-indigo-500' : 'border-indigo-200' }} transition-all duration-200 hover:scale-105">
                  {{ $role->name }}
                  @if($role->name === $primaryRole)
                    <span class="ml-1 text-indigo-600" title="Primary Role">★</span>
                  @endif
                </span>
              @endforeach
            @else
              <span class="text-gray-500 text-sm">No roles assigned</span>
            @endif
          </div>
          
          @if($accountingunit->roles->count() > 0)
            <div class="text-sm text-gray-600">
              <strong>Primary Role:</strong> 
              <span class="text-indigo-600 font-semibold">{{ $primaryRole ?? 'Not set' }}</span>
            </div>
          @endif
        </div>

        <!-- Role Selection -->
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Assign Roles *</label>
            <select name="roles[]" id="roles" multiple 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200"
                    size="4" required>
              @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ in_array($role->name, $userRoles) ? 'selected' : '' }}>
                  {{ $role->name }}
                </option>
              @endforeach
            </select>
            <p class="mt-1 text-sm text-gray-500">Hold Ctrl/Cmd to select multiple roles</p>
            @error('roles') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
            
            <!-- Selected Roles Display -->
            <div id="selectedRoles" class="mt-3 flex flex-wrap gap-2 hidden">
              <span class="text-sm font-medium text-gray-700">Selected Roles:</span>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Primary Role *</label>
            <select name="primary_role" id="primary_role" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-colors duration-200">
              <option value="">-- Select Primary Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->name }}" 
                  {{ old('primary_role', $primaryRole) == $role->name ? 'selected' : '' }}
                  {{ !in_array($role->name, $userRoles) ? 'disabled style=display:none' : '' }}>
                  {{ $role->name }}
                </option>
              @endforeach
            </select>
            <p class="mt-1 text-sm text-gray-500">This will be the default role when user logs in</p>
            @error('primary_role') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
        <div class="flex flex-wrap gap-3">
          <a href="{{ route('admin.accountingunits.index') }}"
             class="px-5 py-2 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all duration-200 font-medium flex items-center hover:scale-105">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
          </a>
          
          @if(!$accountingunit->hasVerifiedEmail())
          <form action="{{ route('admin.accountingunits.resend-verification', $accountingunit) }}" method="POST" class="inline">
            @csrf
            <button type="submit" 
                    class="px-4 py-2 rounded-lg text-amber-600 bg-amber-100 hover:bg-amber-200 transition-all duration-200 font-medium flex items-center hover:scale-105">
              <i class="fas fa-paper-plane mr-2"></i> Resend Verification
            </button>
          </form>
          @endif
        </div>
        
        <div class="flex flex-wrap gap-3">
          <button type="button" onclick="resetForm()"
                  class="px-5 py-2 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all duration-200 font-medium flex items-center hover:scale-105">
            <i class="fas fa-undo mr-2"></i> Reset
          </button>
          <button type="submit"
                  class="px-6 py-2 rounded-lg bg-indigo-600 text-white font-semibold shadow-md hover:bg-indigo-700 transition-all duration-200 flex items-center hover:scale-105">
            <i class="fas fa-save mr-2"></i> Update User
          </button>
        </div>
      </div>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const rolesSelect = document.getElementById('roles');
      const primaryRoleSelect = document.getElementById('primary_role');
      const selectedRolesContainer = document.getElementById('selectedRoles');
      const form = document.getElementById('userForm');

      // Function to update selected roles display
      function updateSelectedRolesDisplay() {
        const selectedRoles = Array.from(rolesSelect.selectedOptions).map(opt => opt.value);
        selectedRolesContainer.innerHTML = '<span class="text-sm font-medium text-gray-700">Selected Roles:</span>';
        
        if (selectedRoles.length > 0) {
          selectedRolesContainer.classList.remove('hidden');
          selectedRoles.forEach(role => {
            const isPrimary = role === primaryRoleSelect.value;
            const badge = document.createElement('span');
            badge.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105 ${
              isPrimary 
                ? 'bg-indigo-100 text-indigo-800 border-2 border-indigo-500' 
                : 'bg-purple-100 text-purple-800 border border-purple-200'
            }`;
            badge.innerHTML = `
              ${role} ${isPrimary ? '<span class="ml-1 text-indigo-600">★</span>' : ''}
              <span class="ml-1 cursor-pointer opacity-70 hover:opacity-100 transition-opacity duration-200" data-role="${role}">×</span>
            `;
            selectedRolesContainer.appendChild(badge);
          });
        } else {
          selectedRolesContainer.classList.add('hidden');
        }
      }

      // Function to update primary role options
      function updatePrimaryRoleOptions() {
        const selectedRoles = Array.from(rolesSelect.selectedOptions).map(opt => opt.value);
        
        // Enable/disable primary role options
        Array.from(primaryRoleSelect.options).forEach(option => {
          if (option.value === '') return;
          
          if (selectedRoles.includes(option.value)) {
            option.disabled = false;
            option.style.display = '';
          } else {
            option.disabled = true;
            option.style.display = 'none';
            if (option.selected) {
              option.selected = false;
              primaryRoleSelect.value = '';
            }
          }
        });

        // Auto-select primary role if only one role is selected
        if (selectedRoles.length === 1 && !primaryRoleSelect.value) {
          primaryRoleSelect.value = selectedRoles[0];
        }

        updateSelectedRolesDisplay();
      }

      // Event listener for role selection changes
      rolesSelect.addEventListener('change', updatePrimaryRoleOptions);

      // Event listener for primary role changes
      primaryRoleSelect.addEventListener('change', updateSelectedRolesDisplay);

      // Event delegation for remove buttons
      selectedRolesContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-btn') || e.target.textContent === '×') {
          const roleToRemove = e.target.getAttribute('data-role');
          const optionToDeselect = Array.from(rolesSelect.options).find(opt => opt.value === roleToRemove);
          
          if (optionToDeselect) {
            optionToDeselect.selected = false;
            rolesSelect.dispatchEvent(new Event('change'));
          }
        }
      });

      // Form validation
      form.addEventListener('submit', function(e) {
        const selectedRoles = Array.from(rolesSelect.selectedOptions).map(opt => opt.value);
        const primaryRole = primaryRoleSelect.value;

        if (selectedRoles.length === 0) {
          e.preventDefault();
          alert('Please select at least one role.');
          return;
        }

        if (!primaryRole) {
          e.preventDefault();
          alert('Please select a primary role.');
          return;
        }

        if (!selectedRoles.includes(primaryRole)) {
          e.preventDefault();
          alert('Primary role must be one of the selected roles.');
          return;
        }
      });

      // Reset form function
      window.resetForm = function() {
        if (confirm('Are you sure you want to reset all changes?')) {
          form.reset();
          // Re-select original roles
          const originalRoles = @json($userRoles);
          Array.from(rolesSelect.options).forEach(option => {
            option.selected = originalRoles.includes(option.value);
          });
          rolesSelect.dispatchEvent(new Event('change'));
          primaryRoleSelect.value = @json($primaryRole);
          updateSelectedRolesDisplay();
        }
      };

      // Initialize on page load
      updatePrimaryRoleOptions();

      // Show selected roles from old input if exists (form validation failed)
      const oldRoles = @json(old('roles', $userRoles));
      if (oldRoles.length > 0) {
        Array.from(rolesSelect.options).forEach(option => {
          option.selected = oldRoles.includes(option.value);
        });
        rolesSelect.dispatchEvent(new Event('change'));
      }

      // Set primary role from old input
      const oldPrimaryRole = @json(old('primary_role', $primaryRole));
      if (oldPrimaryRole) {
        primaryRoleSelect.value = oldPrimaryRole;
        updateSelectedRolesDisplay();
      }
    });
  </script>

  <script>
    // Add fade-in animation to Tailwind
    tailwind.config = {
      theme: {
        extend: {
          animation: {
            'fade-in': 'fadeIn 0.5s ease-in-out',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0', transform: 'translateY(10px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' },
            }
          }
        }
      }
    }
  </script>

</body>
</html>