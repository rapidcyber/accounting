<x-filament-panels::page>

    <form wire:submit.prevent="save" class="space-y-6 max-w-md">
        {{ $this->form }}
        <x-filament::button type="submit">
            Save Changes
        </x-filament::button>
    </form>


</x-filament-panels::page>
