@extends('emails.layout')

@section('content')
    <div class="greeting">Hey {{ $first_name }}! 👋</div>

    <p>We're thrilled to have you join MatchGrinder. You're just one step away from connecting with players and scaling your
        game.</p>

    <p>To get started, please verify your email address by clicking the button below:</p>

    <div class="button-container">
        <a href="{{ $verification_url }}" class="button">Verify Email Address</a>
    </div>

    <p>Once verified, you'll be able to:</p>
    <ul class="bullet-list" style="list-style: none; padding-left: 0;">
        <li style="margin-bottom: 10px;">✅ Create and join local tournaments</li>
        <li style="margin-bottom: 10px;">✅ Connect with players in your area</li>
        <li style="margin-bottom: 10px;">✅ Track your skill progress</li>
    </ul>

    <p>If you didn't sign up for MatchGrinder, you can safely ignore this email.</p>

    <div class="signoff">
        <p>See you on the court!</p>
        <p class="signature-name">The MatchGrinder Team</p>
    </div>
@endsection