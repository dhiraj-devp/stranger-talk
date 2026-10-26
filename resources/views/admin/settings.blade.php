@extends('layouts.admin')

@section('crumb', 'Settings')

@section('content')
    <h1>Platform settings</h1>
    <p class="lead">These controls change live matching and request limits. Saving writes an audit row.</p>
    <form class="panel stack" method="POST" action="{{ route('admin.settings.update') }}" data-dirty data-confirm="Save matching rules and rate limits?">
        @csrf
        <div class="group" id="matching">
            <h2>Matching rules</h2>
            <p class="hint">Off by default. When on, both people must accept each other's gender and country. Anyone and any country stay unrestricted. Blocks and bans still apply.</p>
            <label class="check"><input type="checkbox" name="preference_matching" value="1" @checked($settings->preference_matching)> Use gender and country preferences when pairing</label>
        </div>
        <div class="group" id="limits">
            <h2>Rate limits per minute</h2>
            @foreach ([
                'login' => 'Login',
                'register' => 'Registration',
                'password_reset' => 'Password reset',
                'matchmaking' => 'Matchmaking',
                'next' => 'Next',
                'reports' => 'Reports',
                'blocks' => 'Blocks',
                'signal' => 'Signaling',
                'google' => 'Google sign-in',
            ] as $key => $label)
                @if (array_key_exists($key, $limits))
                    <label for="limit-{{ $key }}">{{ $label }}
                        <input id="limit-{{ $key }}" name="limits[{{ $key }}]" type="number" min="1" max="10000" value="{{ $limits[$key] }}">
                    </label>
                @endif
            @endforeach
        </div>
        <button type="submit">Save changes</button>
    </form>
@endsection
