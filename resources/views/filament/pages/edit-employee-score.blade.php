<x-filament::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        <br>
        <x-filament::button type="submit" class="mt-4">
            Simpan Perubahan
        </x-filament::button>
    </form>
</x-filament::page>
