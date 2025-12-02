<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col space-y-2"></div>

    <!-- Top Navbar -->
    @include('partials.navbar')

    <!-- Spacer to prevent overlap -->
    <div class="h-16"></div>
  
    <!-- Page Layout -->
    <div class="flex">
        <!-- Sidebar -->
        @includeWhen(Auth::check(), 'partials.sidebar')
        
        <!-- Main Content -->
        <main class="flex-1 min-h-screen p-6 @if(Auth::check() && (Auth::user()->hasRole(['Admin', 'Accounting_III']) || Auth::guard('focal')->user())) ml-0 @else ml-0 @endif">
            @yield('content')
        </main>
    </div>
    <!-- Scripts -->
    <script>
        // SweetAlert2 notifications
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "{{ session('error') }}",
            });
        @endif

        @if (session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: "{{ session('warning') }}",
            });
        @endif

        @if (session('info'))
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: "{{ session('info') }}",
                timer: 3000,
                showConfirmButton: true
            });
        @endif

        // Delete confirmation
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    let form = this.closest('form');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This action cannot be undone!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>