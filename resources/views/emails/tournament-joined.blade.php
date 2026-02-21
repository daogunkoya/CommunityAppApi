@extends('emails.layout')

@section('content')
    <div class="greeting">Hi {{ $first_name }},</div>

    <p>You're all set! We're excited to confirm your registration for <strong>{{ $tournament_name }}</strong>.</p>

    <ul class="bullet-list">
        <li class="bullet-item">
            <span class="bullet-icon">🏆</span>
            <span class="bullet-text">Get ready to compete against players of your level</span>
        </li>
        <li class="bullet-item">
            <span class="bullet-icon">📅</span>
            <span class="bullet-text">Check the app for match schedules and updates</span>
        </li>
        <li class="bullet-item">
            <span class="bullet-icon">🤝</span>
            <span class="bullet-text">Connect with other participants in the tournament group chat</span>
        </li>
    </ul>

    <p>You can view all the details about the tournament, rules, and participants directly in the MatchGrinder app.</p>

    <div class="button-container">
        <a href="{{ $tournament_url }}" class="button">View Tournament</a>
    </div>

    <p>If you have any questions before the event begins, just drop a message in the tournament group chat and I'll be happy
        to help.</p>

    <div class="signoff">
        <p>Cheers,</p>
        <br>
        <p class="signature-name">Remi Daniel</p>
        <p class="signature-title">Community Manager</p>
        <p class="signature-title">MatchGrinder</p>
    </div>
@endsection