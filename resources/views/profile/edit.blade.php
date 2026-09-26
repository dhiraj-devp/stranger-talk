@extends('layouts.app')

@section('content')
    <section class="card wide">
        <h1>Profile</h1>
        @if (session('status'))<p class="note">{{ session('status') }}</p>@endif
        @if ($errors->any())
            <div class="alert" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        @if (auth()->user()->avatar)
            <img class="avatar" src="{{ auth()->user()->avatar }}" alt="">
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            <label for="name">Display name</label>
            <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="255">

            <label for="avatar">Photo URL</label>
            <input id="avatar" name="avatar" type="url" value="{{ old('avatar', auth()->user()->avatar) }}" placeholder="https://">

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'undisclosed' => 'Prefer not to say'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', auth()->user()->gender) === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="country_code">Country</label>
            <select id="country_code" name="country_code" required>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" @selected(old('country_code', auth()->user()->country_code) === $code)>{{ \App\Support\Countries::flag($code) }} {{ $name }}</option>
                @endforeach
            </select>

            <h2 id="preferences">Match preferences</h2>
            <p class="fine">Saved for a future option. They do not change who you meet today.</p>
            @unless (auth()->user()->hasFeature('gender_filter'))
                <p class="fine">Premium filters coming soon</p>
            @endunless

            <label for="gender_preference">Gender</label>
            <select id="gender_preference" name="gender_preference">
                @foreach (['anyone' => 'Anyone', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender_preference', $preference->gender_preference) === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="country_preference">Country</label>
            <select id="country_preference" name="country_preference">
                <option value="">Any Country</option>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" @selected(old('country_preference', $preference->country_preference) === $code)>{{ \App\Support\Countries::flag($code) }} {{ $name }}</option>
                @endforeach
            </select>

            <button class="btn btn-primary" type="submit">Save</button>
        </form>
    </section>
@endsection
