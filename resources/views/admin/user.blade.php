@extends('layouts.admin')

@section('content')
    <h1>{{ $user->name }}</h1>
    <p class="lead">{{ $user->email }} · {{ $user->country_code ?: 'No country' }} · {{ $user->gender ?: 'No gender' }}</p>
    <p><span @class(['badge', 'bad' => $user->isBanned()])>{{ $user->status }}</span>
        @if ($user->is_admin) <span class="badge">admin</span> @endif
    </p>
    @if ($user->ban_reason)<p>Ban reason: {{ $user->ban_reason }}</p>@endif

    @if (auth()->user()->staffRole() === 'admin')
        <form class="panel stack" method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            <h2>Account</h2>
            <label for="name">Name <input id="name" name="name" value="{{ $user->name }}" required></label>
            <label for="gender">Gender
                <select id="gender" name="gender">
                    <option value="">Unset</option>
                    @foreach (['male', 'female', 'other', 'undisclosed'] as $gender)
                        <option value="{{ $gender }}" @selected($user->gender === $gender)>{{ $gender }}</option>
                    @endforeach
                </select>
            </label>
            <label for="country_code">Country code <input id="country_code" name="country_code" maxlength="2" value="{{ $user->country_code }}"></label>
            <label for="role">Role
                <select id="role" name="role">
                    @foreach (['member', 'analyst', 'moderator', 'admin'] as $role)
                        <option value="{{ $role }}" @selected($user->staffRole() === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </label>
            <label for="status">Status
                <select id="status" name="status">
                    @foreach (['active', 'offline', 'searching', 'in_call', 'banned'] as $status)
                        <option value="{{ $status }}" @selected($user->status === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Save user</button>
        </form>
    @endif

    <div class="split">
        <form class="panel" method="POST" action="{{ route('admin.users.ban', $user) }}">
            @csrf
            <h2>Ban</h2>
            <label for="reason">Reason</label>
            <input id="reason" name="reason" required maxlength="255">
            <label for="hours">Hours</label>
            <input id="hours" name="hours" type="number" min="1" value="24">
            <label class="check"><input type="checkbox" name="permanent" value="1"> Permanent</label>
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" maxlength="1000"></textarea>
            <button type="submit" class="danger">Ban user</button>
        </form>
        <form class="panel" method="POST" action="{{ route('admin.users.unban', $user) }}">
            @csrf
            <h2>Restore access</h2>
            <p class="lead">Clears the ban and lets this person match again.</p>
            <button type="submit" class="secondary">Unban</button>
        </form>
    </div>

    <section class="panel" style="margin-top:16px">
        <h2>Reports against this user</h2>
        @forelse ($reports as $report)
            <p>{{ $report->reason }} · {{ $report->status }}</p>
        @empty
            <p class="empty">None</p>
        @endforelse
        <h2>Matches</h2>
        @forelse ($matches as $match)
            <p>#{{ $match->id }} {{ $match->status }} {{ $match->end_reason }} {{ $match->created_at }}</p>
        @empty
            <p class="empty">None</p>
        @endforelse
        <h2>Audit</h2>
        @forelse ($audit as $row)
            <p>{{ $row->action }} · {{ $row->created_at }}</p>
        @empty
            <p class="empty">None</p>
        @endforelse
    </section>

    <form class="panel" method="POST" action="{{ route('admin.users.destroy', $user) }}" style="margin-top:16px">
        @csrf
        @method('DELETE')
        <h2>Delete account</h2>
        <label for="confirm_email">Type the email to confirm</label>
        <input id="confirm_email" name="confirm_email" type="email" required>
        <button type="submit" class="danger">Delete user</button>
    </form>
@endsection
