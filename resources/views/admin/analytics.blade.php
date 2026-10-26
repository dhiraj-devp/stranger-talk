@extends('layouts.admin')

@section('crumb', 'Analytics')

@section('content')
    <h1>{{ ucfirst($section) }}</h1>
    <p class="lead">@if ($section === 'traffic') Page views include signed-out visitors. Unique addresses are distinct IP addresses. The hourly report is for {{ $traffic['day'] }}. Days and hours with no traffic stay at zero. @else Cookie visits are separate from signed-in events. {{ $from }} to {{ $to }}. Days without rows stay at zero. The previous window is the same number of days before this range. @endif</p>
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
    @if ($section === 'traffic')
        @php $max = max(1, collect($traffic['days'])->max('value')); @endphp
        <h2>Page views by day</h2>
        <div class="bars" aria-label="Page views by day">
            @foreach ($traffic['days'] as $point)
                <div><b>{{ $point['label'] }}</b><span><i style="width: {{ (int) round($point['value'] / $max * 100) }}%"></i></span>{{ $point['value'] }} <small>{{ $point['uniques'] }} unique</small></div>
            @endforeach
        </div>
        @php $hourMax = max(1, collect($traffic['hours'])->max('views')); @endphp
        <h2>Hours on {{ $traffic['day'] }}</h2>
        <div class="bars" aria-label="Page views by hour">
            @foreach ($traffic['hours'] as $point)
                <div><b>{{ $point['label'] }}</b><span><i style="width: {{ (int) round($point['views'] / $hourMax * 100) }}%"></i></span>{{ $point['views'] }} <small>{{ $point['uniques'] }} unique</small></div>
            @endforeach
        </div>
    @elseif ($section === 'overview' && $series->isNotEmpty())
        @php $max = max(1, $series->max('value')); @endphp
        <div class="bars" aria-label="Visits by day">
            @foreach ($series as $point)
                <div><b>{{ $point['label'] }}</b><span><i style="width: {{ (int) round($point['value'] / $max * 100) }}%"></i></span>{{ $point['value'] }}</div>
            @endforeach
        </div>
    @endif
    <section class="metrics">
        @foreach ($cards as $label => $value)
            <article>
                <span>{{ $label }}</span>
                <strong>{{ number_format($value) }}</strong>
                @if (array_key_exists($label, $previous))
                    <small>Previous {{ number_format($previous[$label]) }}</small>
                @endif
            </article>
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
