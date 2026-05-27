<x-layouts.app active-nav="lounge" :title="$outlet->name" subtitle="Lounge POS">
    @include('modules.pos._hub', ['outletType' => 'lounge', 'hasTables' => false])
</x-layouts.app>
