@extends('layouts.app')

@section('content')
    <section class="card wide">
        <h1>{{ $user->name }}</h1>
        <p>{{ $user->email }} · {{ $user->status }}</p>
        @if ($user->ban_reason)<p>Ban: {{ $user->ban_reason }}</p>@endif
        @if (session('status'))<p class="note">{{ session('status') }}</p>@endif

        <form method="POST" action="{{ route('admin.users.ban', $user) }}">
            @csrf
            <label for="reason">Reason</label>
            <input id="reason" name="reason" required maxlength="255">
            <label for="hours">Hours (temporary)</label>
            <input id="hours" name="hours" type="number" min="1" value="24">
            <label class="check"><input type="checkbox" name="permanent" value="1"> Permanent</label>
            <label for="notes">Admin notes</label>
            <textarea id="notes" name="notes" maxlength="1000"></textarea>
            <button type="submit" class="danger">Ban</button>
        </form>
        <form method="POST" action="{{ route('admin.users.unban', $user) }}">
            @csrf
            <button type="submit" class="secondary">Unban</button>
        </form>

        <h2>Reports</h2>
        @forelse ($reports as $report)
            <p>{{ $report->reason }} · {{ $report->status }}</p>
        @empty
            <p>None</p>
        @endforelse

        <h2>Recent matches</h2>
        @forelse ($matches as $match)
            <p>#{{ $match->id }} {{ $match->status }} {{ $match->created_at }}</p>
        @empty
            <p>None</p>
        @endforelse

        <h2>Audit</h2>
        @forelse ($audit as $row)
            <p>{{ $row->action }} · {{ $row->created_at }}</p>
        @empty
            <p>None</p>
        @endforelse

        <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
            @csrf
            @method('DELETE')
            <label for="confirm_email">Type email to delete</label>
            <input id="confirm_email" name="confirm_email" type="email" required>
            <button type="submit" class="danger">Delete user</button>
        </form>
        <p class="switch"><a href="{{ route('admin.users') }}">Back</a></p>
    </section>
@endsection
