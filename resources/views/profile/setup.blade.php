@extends('layouts.app')

@section('content')
    <section class="card">
        <h1>Let's set up your profile</h1>
        <p class="lead">Two choices, then you can start a chat. We do not guess your gender.</p>

        @if ($errors->any())
            <div class="alert" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('profile.setup.store') }}">
            @csrf
            <fieldset>
                <legend>Gender</legend>
                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'undisclosed' => 'Prefer not to say'] as $value => $label)
                    <label class="choice"><input type="radio" name="gender" value="{{ $value }}" @checked(old('gender') === $value) required> {{ $label }}</label>
                @endforeach
            </fieldset>

            <label for="country_code">Country</label>
            <select id="country_code" name="country_code" required>
                <option value="">Select country</option>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" @selected(old('country_code', $suggested) === $code)>{{ \App\Support\Countries::flag($code) }} {{ $name }}</option>
                @endforeach
            </select>
            <p class="fine">If we recognized your network location, that country is selected. Change it if it is wrong.</p>
            <button class="btn btn-primary" type="submit">Continue</button>
        </form>
    </section>
@endsection
