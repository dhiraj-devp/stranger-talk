@extends('layouts.admin')

@section('content')
    <h1>Reports</h1>
    <nav class="tabs" aria-label="Report status">
        @foreach (['pending', 'reviewing', 'resolved', 'rejected'] as $tab)
            <a href="{{ route('admin.reports', ['status' => $tab]) }}" @class(['is-on' => $status === $tab])>{{ ucfirst($tab) }}</a>
        @endforeach
    </nav>
    @forelse ($reports as $report)
        <form class="panel stack" method="POST" action="{{ route('admin.reports.update', $report) }}">
            @csrf
            <p><strong>{{ $report->reason }}</strong> · {{ $report->reporter->name ?? 'Reporter' }} reported {{ $report->reported->name ?? 'user' }}</p>
            @if ($report->description)<p>{{ $report->description }}</p>@endif
            <label for="status-{{ $report->id }}">Status</label>
            <select id="status-{{ $report->id }}" name="status">
                @foreach (['pending', 'reviewing', 'resolved', 'rejected'] as $option)
                    <option value="{{ $option }}" @selected($report->status === $option)>{{ $option }}</option>
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
