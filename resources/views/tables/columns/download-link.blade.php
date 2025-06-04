{{-- resources/views/tables/columns/download-link.blade.php --}}

@php
    $record = $getRecord();
    $filePath = $record->file_path; // Ini bisa jadi NULL

    // Jika filePath adalah NULL, kita tidak perlu memproses lebih lanjut
    if (is_null($filePath)) {
        $fileExists = false;
        $fileName = 'N/A';
    } else {
        // Jika ada filePath, baru proses
        $fileName = pathinfo($filePath, PATHINFO_BASENAME) ?? 'downloaded_file';
        $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($filePath);
    }
@endphp

<div>
    @if ($filePath && $fileExists)
        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($filePath) }}"
           download="{{ $fileName }}"
           class="filament-link filament-link-primary inline-flex items-center justify-center gap-0.5 text-sm font-medium outline-none transition duration-75 hover:underline focus:underline text-primary-600 dark:text-primary-400">
            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
            Download
        </a>
    @else
        <span class="text-gray-400 dark:text-gray-600">N/A</span>
    @endif
</div>