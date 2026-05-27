<x-layouts.app active-nav="restaurant" :title="$outlet->name" subtitle="Restaurant POS">
    @include('modules.pos._hub', ['outletType' => 'restaurant', 'hasTables' => true])
</x-layouts.app>
