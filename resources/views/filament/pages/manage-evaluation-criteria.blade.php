<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-900/10 ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $editing_id ? 'Edit Kriteria' : 'Tambah Kriteria Baru' }}
                </h3>
                @if($editing_id)
                    <button 
                        wire:click="cancelEdit" 
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 underline transition-colors"
                    >
                        Batal Edit
                    </button>
                @endif
            </div>
            
            <form wire:submit.prevent="save" class="space-y-6">
                {{ $this->form }}
                
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex items-center justify-end gap-3">
                        @if($editing_id)
                            <x-filament::button
                                type="button"
                                wire:click="cancelEdit"
                                color="gray"
                                size="sm"
                            >
                                Batal
                            </x-filament::button>
                        @endif
                        
                        <x-filament::button
                            type="submit"
                            size="sm"
                        >
                            {{ $editing_id ? 'Update Kriteria' : 'Simpan Kriteria' }}
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Information Card -->
        <div class="bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/50 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400 dark:text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Informasi Penting
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong class="font-semibold">Total bobot harus 100% (1.00)</strong> untuk hasil evaluasi yang akurat</li>
                            <li><strong class="font-semibold">Benefit:</strong> Kriteria dimana nilai tinggi lebih baik (contoh: Kedisiplinan, Kualitas Kerja)</li>
                            <li><strong class="font-semibold">Cost:</strong> Kriteria dimana nilai rendah lebih baik (contoh: Tingkat Kesalahan, Keterlambatan)</li>
                            <li>Gunakan tombol "Cek Total Bobot" untuk memverifikasi total bobot kriteria</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-900/10 ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Daftar Kriteria Evaluasi</h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>