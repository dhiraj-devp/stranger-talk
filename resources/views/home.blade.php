@extends('layouts.app')

@section('content')
    <section class="card wide precall">
        <p class="eyebrow">Welcome</p>
        <h1>Welcome, {{ auth()->user()->name }}</h1>
        <p class="lead">Your profile is ready. The camera stays off until you look for someone.</p>

        <dl class="pref-readout">
            <div>
                <dt>Gender</dt>
                <dd>{{ ['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'undisclosed' => 'Prefer not to say'][auth()->user()->gender] ?? 'Not set' }}</dd>
            </div>
            <div>
                <dt>Country</dt>
                <dd>{{ \App\Support\Countries::label(auth()->user()->country_code) }}</dd>
            </div>
            <div>
                <dt>Match preferences</dt>
                <dd>{{ $preference->summary() }}</dd>
            </div>
        </dl>

        @unless (auth()->user()->hasFeature('gender_filter'))
            <p class="fine">Premium filters coming soon. Preferences are saved and do not change matching yet.</p>
        @endunless

        <a class="btn btn-primary" href="{{ route('video') }}">Find Stranger</a>
        <p class="fine">Your browser will ask for the camera and microphone when the call screen opens.</p>
    </section>
@endsection
