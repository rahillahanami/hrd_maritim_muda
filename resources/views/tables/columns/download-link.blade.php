@php
    use Illuminate\Support\Facades\Storage;

    $filePath = $record->file_path ?? null;
    $url = $filePath ? Storage::disk('public')->url($filePath) : '#';
@endphp

<a href="{{ $url }}" target="_self" class="text-blue-600 hover:underline flex items-center gap-1">
    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
    Download
</a>