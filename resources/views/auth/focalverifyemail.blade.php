@extends('layouts.app')

@section('title', 'Verify Email Address')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-success text-white text-center fw-bold fs-5">
                    {{ __('Verify Your Email Address') }}
                </div>

                <div class="card-body text-center">

                    {{-- Success Alerts --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('status') == 'verification-link-sent')
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ __('✅ A fresh verification link has been sent to your email address.') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Email Info --}}
                    <div class="alert alert-info text-start">
                        @auth('focal')
                            <p>📩 We've sent a verification link to your email:</p>
                            <p class="fw-bold text-success">{{ auth()->guard('focal')->user()->email }}</p>
                        @endauth
                        <p>Before proceeding, please check your inbox (and spam folder) for the verification link.</p>
                        <p>If you didn’t receive the email, click below to request another one.</p>
                    </div>

                    {{-- Resend Verification Button --}}
                    <form method="POST" action="{{ route('focal.verification.send') }}" id="resend-form">
                        @csrf
                        <button type="submit" class="btn btn-success px-4 py-2 fw-semibold" id="resend-button">
                            <span id="button-text">{{ __('Resend Verification Email') }}</span>
                            <div id="loading-spinner" class="spinner-border spinner-border-sm d-none ms-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>
                    </form>

                    {{-- Logout --}}
                    @auth('focal')
                    <div class="mt-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link text-danger fw-semibold p-0">
                                {{ __('Logout') }}
                            </button>
                        </form>
                    </div>
                    @endauth

                </div>
            </div>

        </div>
    </div>
</div>
@endsection

<script>
document.getElementById('resend-form')?.addEventListener('submit', function() {
    const button = document.getElementById('resend-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('loading-spinner');
    
    if (button && buttonText && spinner) {
        button.disabled = true;
        buttonText.textContent = 'Sending...';
        spinner.classList.remove('d-none');
    }
});
</script>
<style>
.card {
    border-radius: 12px;
}
.btn-success {
    transition: all 0.3s ease;
}
.btn-success:hover {
    background-color: #198754;
    transform: translateY(-1px);
}
</style>
