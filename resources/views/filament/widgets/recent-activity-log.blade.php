<x-filament-widgets::widget>
    <x-filament::section>
        <h2 class="text-lg font-bold mb-4">Recent Activity Logs</h2>

        <ul class="space-y-2">
            @foreach ($this->getPaginatedRecords() as $log)
                <li class="flex gap-4 items-center justify-between border-b hover:bg-gray-100">
                    <div class="text-sm text-gray-700">{{ $log->description }}</div>
                    <div class="text-xs text-gray-500">
                        {{ $log->created_at->format('M d, Y H:i') }} by {{ $log->causer?->name ?? 'System' }}
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-4">
            {{ $this->getPaginatedRecords()->links() }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
