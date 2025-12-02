<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Create Accounting Unit</title>

  <style>
    body {
      background: linear-gradient(135deg, #e0e7ff, #f8fafc);
    }
    
    .role-badge {
      display: inline-flex;
      align-items: center;
      background: #e0e7ff;
      color: #3730a3;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.875rem;
      margin: 0.25rem;
    }
    
    .role-badge.primary {
      background: #3730a3;
      color: white;
    }
    
    .role-badge .remove-btn {
      margin-left: 0.5rem;
      cursor: pointer;
      opacity: 0.7;
    }
    
    .role-badge .remove-btn:hover {
      opacity: 1;
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center">

  <div class="w-full max-w-3xl bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-8 border border-gray-100">
    
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800">Create Accounting Unit</h1>
      <p class="text-gray-500 mt-2">Fill in the details below to add a new accounting unit</p>
    </div>

    <form action="{{ route('admin.accountingunits.store') }}" method="POST" class="space-y-8" id="userForm">
      @csrf

      <!-- Name Fields -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
          <input type="text" name="fname" value="{{ old('fname') }}" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
          @error('fname') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Middle Initial</label>
          <input type="text" name="mi" value="{{ old('mi') }}" maxlength="1"
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
          <input type="text" name="lname" value="{{ old('lname') }}" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
          @error('lname') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
      </div>

      <!-- Contact Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
          @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Phone Number</label>
          <input type="number" name="phone" value="{{ old('phone') }}"
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
          @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
      </div>

      <!-- Password Fields -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
          <input type="password" name="password" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
          @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
          <input type="password" name="password_confirmation" required
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
        </div>
      </div>
      <!-- Multi-Role Selection -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Roles *</label>
        <select name="roles[]" id="roles" multiple 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none"
                size="4" required>
          @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ in_array($role->name, old('roles', [])) ? 'selected' : '' }}>
              {{ $role->name }}
            </option>
          @endforeach
        </select>
        <p class="mt-1 text-sm text-gray-500">Hold Ctrl/Cmd to select multiple roles</p>
        @error('roles') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        
        <!-- Selected Roles Display -->
        <div id="selectedRoles" class="mt-3 flex flex-wrap gap-2 hidden">
          <span class="text-sm font-medium text-gray-700">Selected Roles:</span>
        </div>
      </div>

      <!-- Primary Role Selection -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Primary Role *</label>
        <select name="primary_role" id="primary_role" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
          <option value="">-- Select Primary Role --</option>
          @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ old('primary_role') == $role->name ? 'selected' : '' }}>
              {{ $role->name }}
            </option>
          @endforeach
        </select>
        <p class="mt-1 text-sm text-gray-500">This will be the default role when user logs in</p>
        @error('primary_role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
      </div>

      <!-- Buttons -->
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <a href="{{ route('admin.accountingunits.index') }}"
           class="px-5 py-2 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 transition font-medium">
          Cancel
        </a>
        <button type="submit"
                class="px-6 py-2 rounded-lg bg-indigo-600 text-white font-semibold shadow-md hover:bg-indigo-700 transition">
          Create User
        </button>
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
            badge.className = `role-badge ${isPrimary ? 'primary' : ''}`;
            badge.innerHTML = `
              ${role} ${isPrimary ? '★' : ''}
              <span class="remove-btn" data-role="${role}">×</span>
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

        // If only one role is selected, auto-set as primary
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
        if (e.target.classList.contains('remove-btn')) {
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

      // Initialize on page load
      updatePrimaryRoleOptions();

      // Show selected roles from old input if exists
      const oldRoles = @json(old('roles', []));
      if (oldRoles.length > 0) {
        Array.from(rolesSelect.options).forEach(option => {
          if (oldRoles.includes(option.value)) {
            option.selected = true;
          }
        });
        rolesSelect.dispatchEvent(new Event('change'));
      }
    });
  </script>

</body>
</html>