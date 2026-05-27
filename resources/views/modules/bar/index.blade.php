<x-layouts.app active-nav="bar" :title="$outlet->name" subtitle="Bar POS">
    @include('modules.pos._hub', ['outletType' => 'bar', 'hasTables' => false])
</x-layouts.app>
