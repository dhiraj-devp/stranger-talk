@extends('layouts.app')

@section('content')
    <section class="card wide">
        <h1>Reports</h1>
        <p class="switch">
            @foreach (['pending', 'reviewing', 'resolved', 'rejected'] as $tab)
                <a href="{{ route('admin.reports', ['status' => $tab]) }}">{{ ucfirst($tab) }}</a>
            @endforeach
        </p>
        @forelse ($reports as $report)
            <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="stack">
                @csrf
                <p>{{ $report->reason }} · from user {{ $report->reporter_id }} about user {{ $report->reported_user_id }}</p>
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
            <p>No reports in this queue.</p>
        @endforelse
        {{ $reports->links() }}
    </section>
@endsection
