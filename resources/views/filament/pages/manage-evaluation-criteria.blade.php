<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ $editing_id ? 'Edit Kriteria' : 'Tambah Kriteria Baru' }}
                </h3>
                @if($editing_id)
                    <button 
                        wire:click="cancelEdit" 
                        class="text-sm text-gray-500 hover:text-gray-700 underline"
                    >
                        Batal Edit
                    </button>
                @endif
            </div>
            
            <form wire:submit.prevent="save" class="space-y-6">
                {{ $this->form }}
                
                <div class="border-t border-gray-200 pt-6">
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
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">
                        Informasi Penting
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Total bobot harus 100% (1.00)</strong> untuk hasil evaluasi yang akurat</li>
                            <li><strong>Benefit:</strong> Kriteria dimana nilai tinggi lebih baik (contoh: Kedisiplinan, Kualitas Kerja)</li>
                            <li><strong>Cost:</strong> Kriteria dimana nilai rendah lebih baik (contoh: Tingkat Kesalahan, Keterlambatan)</li>
                            <li>Gunakan tombol "Cek Total Bobot" untuk memverifikasi total bobot kriteria</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Daftar Kriteria Evaluasi</h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>