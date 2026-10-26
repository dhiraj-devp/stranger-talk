@extends('layouts.admin')

@section('content')
    <h1>Settings</h1>
    <p class="lead">Limits and the preference-matching switch. Saving writes an audit row.</p>
    <form class="panel stack" method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <label class="check"><input type="checkbox" name="preference_matching" value="1" @checked($settings->preference_matching)> Use gender and country preferences when pairing</label>
        @foreach ($limits as $key => $value)
            <label for="limit-{{ $key }}">{{ str_replace('_', ' ', $key) }} per minute
                <input id="limit-{{ $key }}" name="limits[{{ $key }}]" type="number" min="1" value="{{ $value }}">
            </label>
        @endforeach
        <button type="submit">Save settings</button>
    </form>
@endsection
