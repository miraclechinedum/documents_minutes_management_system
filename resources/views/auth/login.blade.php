@extends('layouts.guest')

@section('content')
<div class="login-card">
    <!-- Login Icon -->
    <div class="login-icon">
        <i class="bi bi-box-arrow-in-right"></i>
    </div>

    <!-- Title and Subtitle -->
    <h1 class="login-title">Sign Into Your Account</h1>
    <p class="login-subtitle">&nbsp;</p>

    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email" autofocus>

            @error('email')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="password-input-wrapper">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                    name="password" placeholder="Enter your password" required autocomplete="current-password">
                <button type="button" class="password-toggle">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            @error('password')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror
        </div>

        <!-- Forgot Password Link -->
        @if (Route::has('password.request'))
        <div class="forgot-password">
            <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>
        @endif

        <!-- Sign In Button -->
        <button type="submit" class="btn btn-signin">
            Sign In
        </button>

        <!-- Remember Me (Hidden but functional) -->
        <input type="hidden" name="remember" value="1">
    </form>
</div>
@endsection