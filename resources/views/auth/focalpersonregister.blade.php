<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Focal Person Registration Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
<div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded-xl shadow">
    <h2 class="text-2xl font-bold mb-6 text-gray-700">Register Focal Person</h2>

    <form  action="{{ route('focalperson.register') }}" method="POST" class="space-y-4">
        @csrf
        <!-- First Name -->
        <div>
            <label class="block text-gray-600">First Name</label>
            <input type="text" name="fname" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300" required>
        </div>

        <!-- Middle Initial -->
        <div>
            <label class="block text-gray-600">Middle Initial</label>
            <input type="text" name="mi" class="w-full border rounded-lg p-2">
        </div>
    
        <!-- Last Name -->
        <div>
            <label class="block text-gray-600">Last Name</label>
            <input type="text" name="lname" class="w-full border rounded-lg p-2" required>
        </div>
        <!-- TIN Number -->
        <div>
            <label class="block text-gray-600">TIN Number</label>
            <input type="text" name="TIN_number" class="w-full border rounded-lg p-2" required>

        </div>
        <!-- Address -->
        <div>
            <label class="block text-gray-600">Address</label>
            <input type="text" name="Address" class="w-full border rounded-lg p-2">
        </div>
        <!-- Email -->
        <div>
            <label class="block text-gray-600">Email</label>
            <input type="email" name="email" class="w-full border rounded-lg p-2" required>
        </div>

        <!-- Contact Number -->
        <div>
            <label class="block text-gray-600">Contact Number</label>
            <input type="text" name="contactnumber" class="w-full border rounded-lg p-2" required>
        </div>

       <!-- Program Dropdown -->
        <div>
            <label class="block text-gray-600">Program</label>
            <select name="program_id" id="program_id" class="w-full border rounded-lg p-2">
                <option value="">-- Select Program --</option>
              @foreach($programs as $program)
    <option value="{{ $program->id }}">{{ $program->prcode }} - {{ $program->description }}</option>
              @endforeach
            </select>
        </div>

            <!-- Responsibility Center Dropdown -->
        <div>
            <label class="block text-gray-600">Responsibility Center</label>
            <select name="responcenter_id" id="responcenter_id" class="w-full border rounded-lg p-2">
                <option value="">-- Select Respo Center --</option>
               @foreach($responCenters as $respo)
            <option value="{{ $respo->id }}">{{ $respo->rccode }} - {{ $respo->description }}</option>
                @endforeach
            </select>
        </div>
        <!-- Password -->
        <div>
            <label class="block text-gray-600">Password</label>
            <input type="password" name="password" class="w-full border rounded-lg p-2" required>
        </div>

        <!-- Confirm Password -->
        <div>
            <label class="block text-gray-600">Confirm Password</label>
            <input type="password" name="password_confirmation" class="w-full border rounded-lg p-2" required>
        </div>

        <!-- Submit -->
        <div>
            <button type="submit" class="w-full bg-blue-600 text-white rounded-lg p-2 hover:bg-blue-700">
                Register
            </button>
        </div>
    </form>
</div>
</body>
</html>