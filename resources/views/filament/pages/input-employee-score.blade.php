<x-filament::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        <x-filament::button type="submit" class="mt-4">
            Simpan Skor
        </x-filament::button>
    </form>

    @if (session()->has('success'))
        <div class="text-green-600 mt-4">{{ session('success') }}</div>
    @endif
</x-filament::page>
