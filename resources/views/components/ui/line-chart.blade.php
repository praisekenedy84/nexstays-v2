@props([
    'labels' => [],
    'values' => [],
    'height' => 220,
    'currency' => '',
    'labelInterval' => null,
    'compact' => false,
])

@php
    $count = count($values);
    $canRender = $count >= 1;

    if ($canRender) {
        $maxValue = max(1.0, (float) max($values));

        $chartW = 640;
        $chartH = (int) $height;
        $fontSize = $compact ? 9 : 10;

        $fmtAxis = static function (float $v): string {
            if ($v >= 1_000_000) {
                $n = $v / 1_000_000;

                return rtrim(rtrim(sprintf('%.1f', $n), '0'), '.').'M';
            }
            if ($v >= 10_000) {
                $n = $v / 1_000;

                return rtrim(rtrim(sprintf('%.0f', $n), '0'), '.').'K';
            }
            if ($v >= 1_000) {
                $n = $v / 1_000;

                return rtrim(rtrim(sprintf('%.1f', $n), '0'), '.').'K';
            }

            return $v >= 100 ? (string) (int) round($v) : rtrim(rtrim(sprintf('%.1f', $v), '0'), '.');
        };

        $fmtTooltip = static function (float $v) use ($currency): string {
            $formatted = number_format($v, 0);

            return $currency !== '' ? "{$currency} {$formatted}" : $formatted;
        };

        $yTickCount = $compact ? 3 : 4;
        $gridLines = [];
        for ($g = 0; $g <= $yTickCount; $g++) {
            $ratio = $yTickCount > 0 ? $g / $yTickCount : 0;
            $gridLines[] = [
                'label' => $fmtAxis($maxValue * $ratio),
            ];
        }

        $longestAxisLabel = max(array_map(static fn (array $grid) => strlen($grid['label']), $gridLines));
        $padL = (int) max($compact ? 52 : 58, 12 + ($longestAxisLabel * ($fontSize * 0.62)));
        $padR = $compact ? 14 : 18;
        $padT = $compact ? 12 : 20;
        $padB = $compact ? 26 : 32;
        $plotW = $chartW - $padL - $padR;
        $plotH = $chartH - $padT - $padB;
        $pointRadius = $compact ? 2.5 : 4;
        $strokeWidth = $compact ? 2 : 2.5;

        foreach ($gridLines as $i => $grid) {
            $ratio = $yTickCount > 0 ? $i / $yTickCount : 0;
            $gridLines[$i]['y'] = $padT + $plotH - ($ratio * $plotH);
        }

        $xLabelStep = $labelInterval !== null
            ? (int) $labelInterval
            : ($count > 20 ? (int) ceil($count / 7) : ($count > 10 ? 2 : 1));

        $points = [];
        foreach ($values as $i => $value) {
            $v = (float) $value;
            $x = $padL + ($count > 1 ? ($i / ($count - 1)) * $plotW : $plotW / 2);
            $y = $padT + $plotH - ($v / $maxValue) * $plotH;
            $points[] = ['x' => $x, 'y' => $y, 'v' => $v];
        }

        $linePath = $count > 1
            ? collect($points)
                ->map(fn (array $p, int $i) => ($i === 0 ? 'M' : 'L').round($p['x'], 1).','.round($p['y'], 1))
                ->join(' ')
            : '';

        $baseline = $padT + $plotH;
        $areaPath = $count > 1
            ? $linePath
                .' L'.round($points[$count - 1]['x'], 1).','.$baseline
                .' L'.round($points[0]['x'], 1).','.$baseline
                .' Z'
            : '';

        $uid = 'lc-'.substr(md5(json_encode($values)), 0, 8);
    }
@endphp

@if ($canRender)
    <div {{ $attributes->merge(['class' => 'w-full']) }}>
        <svg
            viewBox="0 0 {{ $chartW }} {{ $chartH }}"
            class="w-full"
            role="img"
            aria-label="Revenue line chart"
            preserveAspectRatio="xMidYMid meet"
        >
            <defs>
                <linearGradient id="{{ $uid }}-area" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--color-primary)" stop-opacity="0.18" />
                    <stop offset="100%" stop-color="var(--color-primary)" stop-opacity="0.02" />
                </linearGradient>
            </defs>

            @foreach ($gridLines as $grid)
                <line
                    x1="{{ $padL }}"
                    y1="{{ round($grid['y'], 1) }}"
                    x2="{{ $chartW - $padR }}"
                    y2="{{ round($grid['y'], 1) }}"
                    stroke="currentColor"
                    stroke-opacity="0.08"
                    vector-effect="non-scaling-stroke"
                />
                <text
                    x="{{ $padL - 8 }}"
                    y="{{ round($grid['y'] + 3, 1) }}"
                    text-anchor="end"
                    class="fill-ink-subtle"
                    style="font-size: {{ $fontSize }}px"
                >{{ $grid['label'] }}</text>
            @endforeach

            @if ($areaPath !== '')
            <path d="{{ $areaPath }}" fill="url(#{{ $uid }}-area)" />
            @endif

            @if ($linePath !== '')
            <path
                d="{{ $linePath }}"
                fill="none"
                stroke="var(--color-primary)"
                stroke-width="{{ $strokeWidth }}"
                stroke-linecap="round"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
            />
            @endif

            @foreach ($points as $i => $point)
                <circle
                    cx="{{ round($point['x'], 1) }}"
                    cy="{{ round($point['y'], 1) }}"
                    r="{{ $pointRadius }}"
                    fill="var(--color-surface-elevated)"
                    stroke="var(--color-primary)"
                    stroke-width="1.5"
                    vector-effect="non-scaling-stroke"
                >
                    <title>{{ ($labels[$i] ?? '') }}: {{ $fmtTooltip($point['v']) }}</title>
                </circle>
            @endforeach

            @foreach ($labels as $i => $label)
                @php
                    $showLabel = $i === 0 || $i === $count - 1 || $i % $xLabelStep === 0;
                    $x = $padL + ($count > 1 ? ($i / ($count - 1)) * $plotW : $plotW / 2);
                @endphp
                @if ($showLabel)
                    <text
                        x="{{ round($x, 1) }}"
                        y="{{ $chartH - 6 }}"
                        text-anchor="middle"
                        class="fill-ink-muted"
                        style="font-size: {{ $fontSize }}px"
                    >{{ $label }}</text>
                @endif
            @endforeach
        </svg>
    </div>
@else
    <p {{ $attributes->merge(['class' => 'py-6 text-center text-sm text-ink-muted']) }}>
        {{ $slot->isEmpty() ? 'Not enough data to draw a trend yet.' : $slot }}
    </p>
@endif
