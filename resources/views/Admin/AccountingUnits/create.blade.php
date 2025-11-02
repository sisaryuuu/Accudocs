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
  </style>
</head>
<body class="min-h-screen flex items-center justify-center">

  <div class="w-full max-w-3xl bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-8 border border-gray-100">
    
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800">Create Accounting Unit</h1>
      <p class="text-gray-500 mt-2">Fill in the details below to add a new accounting unit</p>
    </div>

    <form action="{{ route('admin.accountingunits.store') }}" method="POST" class="space-y-8">
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
          <input type="number" name="phone" value="{{ old('phone') }}" required
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

      <!-- Role -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
        <select name="role" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
          <option value="">-- Select Role --</option>
          @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
              {{ $role->name }}
            </option>
          @endforeach
        </select>
        @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
      </div>

      <!-- Buttons -->
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <a href="{{ route('admin.accountingunits.index') }}"
           class="px-5 py-2 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 transition font-medium">
          Cancel
        </a>
        <button type="submit"
                class="px-6 py-2 rounded-lg bg-indigo-600 text-white font-semibold shadow-md hover:bg-indigo-700 transition">
          Save
        </button>
      </div>
    </form>
  </div>

</body>
</html>
