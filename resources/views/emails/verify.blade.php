@extends('emails.layout')

@section('content')
    <div class="greeting">Hi {{ $first_name }},</div>

    <p>Welcome to MatchGrinder! To ensure the security of your account and get full access to the platform, we just need to
        verify your email address.</p>

    <ul class="bullet-list">
        <li class="bullet-item">
            <span class="bullet-icon">✅</span>
            <span class="bullet-text">Unlock full access to matchmaking and tournaments</span>
        </li>
        <li class="bullet-item">
            <span class="bullet-icon">🔒</span>
            <span class="bullet-text">Secure your account and protect your data</span>
        </li>
        <li class="bullet-item">
            <span class="bullet-icon">🤝</span>
            <span class="bullet-text">Connect with verified players in your area</span>
        </li>
    </ul>

    <p>Please click the button below to verify your email address. This link will expire in 24 hours.</p>

    <div class="button-container">
        <a href="{{ $verification_url }}" class="button">Verify Email Address</a>
    </div>

    <p>If you did not create an account with us, no further action is required.</p>

    <div class="signoff">
        <p>Cheers,</p>
        <br>
        <p class="signature-name">Remi Daniel</p>
        <p class="signature-title">Community Manager</p>
        <p class="signature-title">MatchGrinder</p>
    </div>
@endsection