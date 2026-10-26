@extends('layouts.admin')

@section('crumb', 'Users')

@section('content')
    <h1>Users</h1>
    <form class="filters" method="GET" action="{{ route('admin.users') }}">
        <div><label for="q">Search</label><input id="q" name="q" value="{{ $q }}" placeholder="Name, email, or ID"></div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Any</option>
                @foreach (['active', 'offline', 'searching', 'in_call', 'banned'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="">Any</option>
                @foreach (['member', 'analyst', 'moderator', 'admin'] as $option)
                    <option value="{{ $option }}" @selected($role === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="country">Country</label><input id="country" name="country" maxlength="2" value="{{ request('country') }}" placeholder="US"></div>
        <div>
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <option value="">Any</option>
                @foreach (['male', 'female', 'other', 'undisclosed'] as $option)
                    <option value="{{ $option }}" @selected(request('gender') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="from">Registered from</label><input id="from" type="date" name="from" value="{{ request('from') }}"></div>
        <button type="submit">Filter</button>
    </form>
    <section class="panel">
        <table>
            <thead>
                <tr>
                    @foreach (['name' => 'Name', 'email' => 'Email', 'status' => 'Status', 'last_seen_at' => 'Seen', 'created_at' => 'Joined'] as $column => $label)
                        <th><a href="{{ route('admin.users', array_merge(request()->query(), ['sort' => $column, 'dir' => $sort === $column && $dir === 'asc' ? 'desc' : 'asc'])) }}">{{ $label }}</a></th>
                    @endforeach
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a><div class="hint">#{{ $user->id }}</div></td>
                    <td>{{ $user->email }}</td>
                    <td><span @class(['badge', 'bad' => $user->status === 'banned', 'ok' => $user->status === 'active'])>{{ $user->status }}</span></td>
                    <td>{{ $user->last_seen_at?->diffForHumans() ?: '—' }}</td>
                    <td>{{ $user->created_at?->toDateString() }}</td>
                    <td><span class="badge">{{ $user->staffRole() }}</span></td>
                </tr>
            @empty
                <tr><td class="empty" colspan="6">No users match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pager">{{ $users->links() }}</div>
    </section>
@endsection
