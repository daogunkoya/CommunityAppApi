@extends('emails.layout')

@section('content')
    <div class="greeting">Hi {{ $first_name }},</div>

    <p>Welcome to MatchGrinder! We're excited to have you join our community.</p>
    <p>Please click the button below to verify your email address and complete your registration.</p>

    <div class="button-container">
        <a href="{{ $verification_url }}" class="button">Verify Email Address</a>
    </div>

    <p>If you did not create an account, no further action is required.</p>

    <div class="signoff">
        <p>Cheers,</p>
        <br>
        <p class="signature-name">Remi Daniel</p>
        <p class="signature-title">Community Manager</p>
        <p class="signature-title">MatchGrinder</p>
    </div>
@endsection