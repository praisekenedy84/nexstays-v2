@php
    /** @var array<string, array{label: string, description?: string}> $featureDefinitions */
    /** @var list<string> $enabledFeatures */
    $enabledFeatures = $enabledFeatures ?? array_keys($featureDefinitions);
@endphp

<div class="space-y-3">
    @foreach ($featureDefinitions as $key => $definition)
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3 hover:bg-white/[0.04] transition">
            <input type="checkbox" name="features[]" value="{{ $key }}"
                   @checked(in_array($key, $enabledFeatures, true))
                   class="mt-0.5 rounded border-white/20 bg-white/5 text-indigo-500 focus:ring-indigo-500/40">
            <span>
                <span class="block text-sm font-medium text-white">{{ $definition['label'] }}</span>
                @if (! empty($definition['description']))
                    <span class="mt-0.5 block text-xs text-slate-500">{{ $definition['description'] }}</span>
                @endif
            </span>
        </label>
    @endforeach
</div>
