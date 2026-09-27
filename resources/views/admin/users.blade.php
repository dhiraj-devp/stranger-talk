@extends('layouts.admin')

@section('content')
    <h1>Users</h1>
    <form class="filters" method="GET" action="{{ route('admin.users') }}">
        <div>
            <label for="q">Search</label>
            <input id="q" name="q" value="{{ $q }}" placeholder="Name or email">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Any</option>
                @foreach (['active', 'offline', 'searching', 'in_call', 'banned'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit">Filter</button>
    </form>
    <section class="panel">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Country</th><th>Status</th><th>Seen</th></tr></thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->country_code ?: '—' }}</td>
                    <td><span @class(['badge', 'bad' => $user->status === 'banned'])>{{ $user->status }}</span></td>
                    <td>{{ $user->last_seen_at?->diffForHumans() ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="pager">{{ $users->links() }}</div>
    </section>
@endsection
