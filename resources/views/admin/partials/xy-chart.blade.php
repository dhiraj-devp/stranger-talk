@php
    $points = array_values($points ?? []);
    $count = count($points);
    $max = 1;
    foreach ($points as $point) {
        $max = max($max, (int) ($point['views'] ?? 0), (int) ($point['uniques'] ?? 0));
    }
    $width = 720;
    $height = 280;
    $left = 46;
    $right = 16;
    $top = 16;
    $bottom = 36;
    $plotW = $width - $left - $right;
    $plotH = $height - $top - $bottom;
    $xy = function (int $index, int $value) use ($count, $max, $left, $top, $plotW, $plotH): array {
        $x = $count <= 1 ? $left + ($plotW / 2) : $left + ($index / ($count - 1)) * $plotW;
        $y = $top + $plotH - ($value / $max) * $plotH;

        return [round($x, 1), round($y, 1)];
    };
    $path = function (string $key) use ($points, $xy): string {
        $parts = [];
        foreach ($points as $index => $point) {
            [$x, $y] = $xy($index, (int) ($point[$key] ?? 0));
            $parts[] = $x.','.$y;
        }

        return implode(' ', $parts);
    };
    $step = $count > 12 ? (int) ceil($count / 8) : 1;
@endphp
<figure class="xy">
    <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="{{ $label }}">
        @for ($tick = 0; $tick <= 4; $tick++)
            @php
                $value = (int) round($max * ($tick / 4));
                $y = round($top + $plotH - ($tick / 4) * $plotH, 1);
            @endphp
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y }}" y2="{{ $y }}"></line>
            <text x="{{ $left - 8 }}" y="{{ $y + 4 }}" text-anchor="end">{{ $value }}</text>
        @endfor
        <line class="axis" x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $top + $plotH }}" y2="{{ $top + $plotH }}"></line>
        <line class="axis" x1="{{ $left }}" x2="{{ $left }}" y1="{{ $top }}" y2="{{ $top + $plotH }}"></line>
        @if ($count > 0)
            <polyline class="series-views" points="{{ $path('views') }}"></polyline>
            <polyline class="series-uniques" points="{{ $path('uniques') }}"></polyline>
            @foreach ($points as $index => $point)
                @php
                    [$vx, $vy] = $xy($index, (int) ($point['views'] ?? 0));
                    [$ux, $uy] = $xy($index, (int) ($point['uniques'] ?? 0));
                    $tick = (string) $point['label'];
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tick)) {
                        $tick = substr($tick, 5);
                    }
                @endphp
                <circle class="series-views" cx="{{ $vx }}" cy="{{ $vy }}" r="3"><title>{{ $point['label'] }}: {{ (int) ($point['views'] ?? 0) }} page views</title></circle>
                <circle class="series-uniques" cx="{{ $ux }}" cy="{{ $uy }}" r="3"><title>{{ $point['label'] }}: {{ (int) ($point['uniques'] ?? 0) }} unique addresses</title></circle>
                @if ($index % $step === 0 || $index === $count - 1)
                    <text class="x" x="{{ $vx }}" y="{{ $height - 10 }}" text-anchor="middle">{{ $tick }}</text>
                @endif
            @endforeach
        @endif
    </svg>
    <figcaption>
        <span><i class="swatch views"></i>Page views</span>
        <span><i class="swatch uniques"></i>Unique addresses</span>
    </figcaption>
</figure>
