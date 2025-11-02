<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Settings</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:20px}
        .form-group{margin-bottom:12px}
        label{display:block;font-weight:600;margin-bottom:4px}
        input[type=text]{width:100%;padding:8px;border:1px solid #ccc;border-radius:4px}
        .btn{padding:8px 12px;border:none;border-radius:4px;cursor:pointer}
        .btn-save{background:#2563eb;color:#fff}
        .alert-success{color:green;margin-bottom:12px}
        .error{color:#b91c1c;font-size:13px}
    </style>
</head>
<body>

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf

    <div class="form-group">
        <label for="imsd_head">IMSD Head</label>
        <input id="imsd_head" type="text" name="imsd_head" value="{{ old('imsd_head', $imsd ?? '') }}">
        @error('imsd_head') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="accountant_name">Accountant</label>
        <input id="accountant_name" type="text" name="accountant_name" value="{{ old('accountant_name', $accountant ?? '') }}">
        @error('accountant_name') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="regional_director">Regional Director</label>
        <input id="regional_director" type="text" name="regional_director" value="{{ old('regional_director', $regional ?? '') }}">
        @error('regional_director') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="budget_officer">Budget Officer</label>
        <input id="budget_officer" type="text" name="budget_officer" value="{{ old('budget_officer', $budgetofficer ?? '') }}">
        @error('budget_officer') <div class="error">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn btn-save">Save</button>
    <a href="{{ route('Admin.dashboard') }}" style="margin-left:8px;color:#374151">Back</a>
</form>

</body>
</html>