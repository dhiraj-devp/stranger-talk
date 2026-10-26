@extends('layouts.admin')

@section('crumb', 'Reports')

@section('content')
    <h1>Report queue</h1>
    <p class="lead">Three or more pending reports in 24 hours are marked Needs review. That does not ban anyone.</p>
    <form class="filters" method="GET">
        <input type="hidden" name="status" value="{{ $status }}">
        <label>Reason
            <select name="reason">
                <option value="">Any</option>
                @foreach (['harassment', 'nudity', 'spam', 'abuse', 'suspicious', 'other'] as $option)
                    <option value="{{ $option }}" @selected($reason === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <label>From <input type="date" name="from" value="{{ request('from') }}"></label>
        <label>To <input type="date" name="to" value="{{ request('to') }}"></label>
        <button type="submit">Filter</button>
    </form>
    <nav class="tabs" aria-label="Report status">
        @foreach (['pending', 'reviewing', 'resolved', 'rejected'] as $tab)
            <a href="{{ route('admin.reports', array_merge(request()->except('page'), ['status' => $tab])) }}" @class(['is-on' => $status === $tab])>{{ ucfirst($tab) }}</a>
        @endforeach
    </nav>
    @forelse ($reports as $report)
        <form class="panel stack" method="POST" action="{{ route('admin.reports.update', $report) }}">
            @csrf
            <p><strong>{{ $report->reason }}</strong> · {{ $report->created_at?->diffForHumans() }} · {{ $report->reporter->name ?? 'Reporter' }} reported {{ $report->reported->name ?? 'user' }}
                @if ($hot->contains($report->reported_user_id)) <span class="badge bad">Needs review</span> @endif
                @if ($report->match_id) <a href="{{ route('admin.matches.show', $report->match_id) }}">Match</a> @endif
            </p>
            @if ($report->description)<p>{{ $report->description }}</p>@endif
            <label for="status-{{ $report->id }}">Status</label>
            <select id="status-{{ $report->id }}" name="status">
                @foreach (['pending', 'reviewing', 'resolved', 'rejected'] as $option)
                    <option value="{{ $option }}" @selected($report->status === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <label for="owner-{{ $report->id }}">Assignee</label>
            <select id="owner-{{ $report->id }}" name="moderator_id">
                <option value="">Unassigned</option>
                @foreach ($staff as $person)
                    <option value="{{ $person->id }}" @selected($report->moderator_id === $person->id)>{{ $person->name }}</option>
                @endforeach
            </select>
            <label for="notes-{{ $report->id }}">Moderator notes</label>
            <textarea id="notes-{{ $report->id }}" name="moderator_notes">{{ $report->moderator_notes }}</textarea>
            <button type="submit">Save</button>
        </form>
    @empty
        <p class="empty">No reports in this queue.</p>
    @endforelse
    <div class="pager">{{ $reports->links() }}</div>
@endsection
