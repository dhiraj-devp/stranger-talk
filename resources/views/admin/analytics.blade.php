@extends('layouts.admin')

@section('content')
    <h1>Analytics</h1>
    <p class="lead">First-party counts for {{ $from }} to {{ $to }}. Empty days stay at zero.</p>
    <nav class="tabs" aria-label="Analytics">
        @foreach ($sections as $item)
            <a href="{{ route('admin.analytics', ['section' => $item, 'from' => $from, 'to' => $to]) }}" @class(['is-on' => $section === $item])>{{ ucfirst($item) }}</a>
        @endforeach
    </nav>
    <form class="filters" method="GET">
        <label>From <input type="date" name="from" value="{{ $from }}"></label>
        <label>To <input type="date" name="to" value="{{ $to }}"></label>
        <button type="submit">Apply</button>
        <a class="btn" href="{{ route('admin.analytics.export', ['section' => $section, 'from' => $from, 'to' => $to]) }}">Export CSV</a>
    </form>
    <section class="metrics">
        @foreach ($cards as $label => $value)
            <article><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></article>
        @endforeach
    </section>
    <section class="panel">
        <table>
            <thead><tr><th>Detail</th><th>Value</th></tr></thead>
            <tbody>
            @forelse ($table as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['value']) }}</td></tr>
            @empty
                <tr><td class="empty" colspan="2">No recorded rows in this range.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
