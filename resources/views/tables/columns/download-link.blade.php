{{-- resources/views/tables/columns/download-link.blade.php --}}

@php
    // Mendapatkan instance model Document untuk baris saat ini
    $record = $getRecord();

    // Mendapatkan path file dari kolom 'file_path' di model
    $filePath = $record->file_path;

    // Mendapatkan nama file yang akan ditampilkan/disarankan saat download
    // Kita gunakan nama dokumen dan fallback ke 'file' jika tipe tidak tersedia
    // Karena Anda ingin lupakan file type, kita bisa ambil nama dari path atau default
    $fileName = pathinfo($filePath, PATHINFO_BASENAME) ?? 'downloaded_file';

    // Pastikan file tersebut benar-benar ada di storage disk 'public'
    $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($filePath);
@endphp

<div>
    @if ($filePath && $fileExists)
        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($filePath) }}"
           download="{{ $fileName }}"
           class="filament-link filament-link-primary inline-flex items-center justify-center gap-0.5 text-sm font-medium outline-none transition duration-75 hover:underline focus:underline text-primary-600 dark:text-primary-400">
            {{-- Ikon untuk mengunduh --}}
            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
            Download
        </a>
    @else
        {{-- Tampilkan pesan jika file tidak ada --}}
        <span class="text-gray-400 dark:text-gray-600">File Tidak Tersedia</span>
    @endif
</div>