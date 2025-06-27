<x-filament::page>
    <form wire:submit.prevent="evaluate">
        {{ $this->form }}
        <x-filament::button type="submit" class="mt-4">Evaluasi Sekarang</x-filament::button>
    </form>

    @if ($results)
        <!-- Informasi Parameter Lambda -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="text-md font-semibold text-blue-800 mb-2">Parameter Perhitungan WASPAS</h3>
            <p class="text-sm text-blue-700">
                Lambda (λ) = <span class="font-bold">{{ $results[0]['lambda'] ?? 0.5 }}</span><br>
                Formula WASPAS: <code>λ × SAW + (1-λ) × WPM</code><br>
                = <span class="font-bold">{{ $results[0]['lambda'] ?? 0.5 }}</span> × SAW + 
                <span class="font-bold">{{ 1 - ($results[0]['lambda'] ?? 0.5) }}</span> × WPM
            </p>
        </div>

        <!-- Detail Perhitungan Normalisasi per Kriteria -->
        <div class="mt-6">
            <h2 class="text-lg font-bold mb-4">Detail Perhitungan WASPAS</h2>
            
            <!-- Tabel Skor Asli -->
            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2">1. Matriks Keputusan (Skor Asli)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 border">Karyawan</th>
                                @php
                                    $criteria = \App\Models\EvaluationCriteria::all();
                                @endphp
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border text-center">
                                        {{ $criterion->name }}<br>
                                        <small class="text-gray-500">({{ ucfirst($criterion->type) }}, W={{ $criterion->weight }})</small>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t">
                                    <td class="px-3 py-2 border font-medium">{{ $result['name'] }}</td>
                                    @foreach($criteria as $criterion)
                                        @php
                                            $originalScore = \App\Models\EmployeeScore::where('employee_id', $result['employee_id'])
                                                ->where('evaluation_id', $evaluation_id)
                                                ->where('evaluation_criteria_id', $criterion->id)
                                                ->value('score') ?? 0;
                                        @endphp
                                        <td class="px-3 py-2 border text-center">{{ $originalScore }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Normalisasi -->
            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2">2. Matriks Normalisasi</h3>
                <div class="text-xs text-gray-600 mb-2">
                    <strong>Benefit:</strong> r<sub>ij</sub> = x<sub>ij</sub> / max(x<sub>ij</sub>) &nbsp;&nbsp;
                    <strong>Cost:</strong> r<sub>ij</sub> = min(x<sub>ij</sub>) / x<sub>ij</sub>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 border">Karyawan</th>
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border text-center">{{ $criterion->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t">
                                    <td class="px-3 py-2 border font-medium">{{ $result['name'] }}</td>
                                    @foreach($criteria as $criterion)
                                        <td class="px-3 py-2 border text-center">
                                            {{ round($result['normalized_scores'][$criterion->id] ?? 0, 4) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Perhitungan SAW -->
            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2">3. Perhitungan SAW (Simple Additive Weighting)</h3>
                <div class="text-xs text-gray-600 mb-2">
                    Formula: SAW = Σ (w<sub>j</sub> × r<sub>ij</sub>)
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 border">Karyawan</th>
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border text-center">
                                        {{ $criterion->name }}<br>
                                        <small>(w={{ $criterion->weight }})</small>
                                    </th>
                                @endforeach
                                <th class="px-3 py-2 border text-center bg-blue-50">Total SAW</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t">
                                    <td class="px-3 py-2 border font-medium">{{ $result['name'] }}</td>
                                    @foreach($criteria as $criterion)
                                        @php
                                            $normalized = $result['normalized_scores'][$criterion->id] ?? 0;
                                            $weight = $criterion->weight;
                                            $sawComponent = $weight * $normalized;
                                        @endphp
                                        <td class="px-3 py-2 border text-center">
                                            {{ round($sawComponent, 4) }}<br>
                                            <small class="text-gray-500">({{ $weight }} × {{ round($normalized, 4) }})</small>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 border text-center bg-blue-50 font-semibold">
                                        {{ $result['saw_scores_sum'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Perhitungan WPM -->
            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2">4. Perhitungan WPM (Weighted Product Method)</h3>
                <div class="text-xs text-gray-600 mb-2">
                    Formula: WPM = ∏ (r<sub>ij</sub>)<sup>w<sub>j</sub></sup>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 border">Karyawan</th>
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border text-center">
                                        {{ $criterion->name }}<br>
                                        <small>(w={{ $criterion->weight }})</small>
                                    </th>
                                @endforeach
                                <th class="px-3 py-2 border text-center bg-green-50">Total WPM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t">
                                    <td class="px-3 py-2 border font-medium">{{ $result['name'] }}</td>
                                    @foreach($criteria as $criterion)
                                        @php
                                            $normalized = $result['normalized_scores'][$criterion->id] ?? 0;
                                            $weight = $criterion->weight;
                                            $wpComponent = $normalized > 0 ? pow($normalized, $weight) : 0;
                                        @endphp
                                        <td class="px-3 py-2 border text-center">
                                            {{ round($wpComponent, 4) }}<br>
                                            <small class="text-gray-500">({{ round($normalized, 4) }})<sup>{{ $weight }}</sup></small>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 border text-center bg-green-50 font-semibold">
                                        {{ $result['wp_scores_product'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Hasil Akhir WASPAS -->
            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2">5. Hasil Akhir WASPAS</h3>
                <div class="text-xs text-gray-600 mb-2">
                    Formula: WASPAS = λ × SAW + (1-λ) × WPM = 
                    {{ $results[0]['lambda'] ?? 0.5 }} × SAW + {{ 1 - ($results[0]['lambda'] ?? 0.5) }} × WPM
                </div>
                <table class="w-full text-sm text-left border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 border">Rank</th>
                            <th class="px-4 py-2 border">Nama Karyawan</th>
                            <th class="px-4 py-2 border">SAW Total</th>
                            <th class="px-4 py-2 border">WPM Total</th>
                            <th class="px-4 py-2 border">Perhitungan WASPAS</th>
                            <th class="px-4 py-2 border">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $i => $r)
                            <tr class="border-t {{ $i < 3 ? 'bg-yellow-50' : '' }}">
                                <td class="px-4 py-2 border text-center font-semibold">
                                    @if($r['rank'] === 1) 🥇 1
                                    @elseif($r['rank'] === 2) 🥈 2
                                    @elseif($r['rank'] === 3) 🥉 3
                                    @else {{ $r['rank'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 border font-medium">{{ $r['name'] }}</td>
                                <td class="px-4 py-2 border text-center">{{ round($r['saw_scores_sum'], 4) }}</td>
                                <td class="px-4 py-2 border text-center">{{ round($r['wp_scores_product'], 4) }}</td>
                                <td class="px-4 py-2 border text-center text-xs">
                                    {{ $r['lambda'] ?? 0.5 }} × {{ round($r['saw_scores_sum'], 4) }} + 
                                    {{ 1 - ($r['lambda'] ?? 0.5) }} × {{ round($r['wp_scores_product'], 4) }}
                                </td>
                                <td class="px-4 py-2 border text-center font-bold text-lg">{{ $r['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Penjelasan Metode -->
            <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="text-md font-semibold mb-2">Penjelasan Metode WASPAS</h3>
                <div class="text-sm text-gray-700 space-y-2">
                    <p><strong>WASPAS (Weighted Aggregated Sum Product Assessment)</strong> adalah metode MCDM yang menggabungkan SAW dan WPM.</p>
                    <p><strong>SAW:</strong> Menggunakan penjumlahan terbobot dari nilai normalisasi.</p>
                    <p><strong>WPM:</strong> Menggunakan perkalian nilai normalisasi yang dipangkatkan dengan bobot.</p>
                    <p><strong>Parameter λ:</strong> Mengontrol kontribusi SAW dan WPM dalam hasil akhir.</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>λ = 0: 100% WPM (fokus pada keseimbangan kriteria)</li>
                        <li>λ = 0.5: 50% SAW + 50% WPM (seimbang)</li>
                        <li>λ = 1: 100% SAW (fokus pada total skor)</li>
                    </ul>
                </div>
            </div>

            <form wire:submit.prevent="submit" class="mt-6">
                <x-filament::button type="submit" color="success">Simpan Hasil Evaluasi</x-filament::button>
            </form>
        </div>
    @endif

    {{-- Bagian Histori Evaluasi --}}
    @if ($history)
    <div class="mt-10">
        <h2 class="text-lg font-bold mb-2">Histori Evaluasi Periode Ini</h2>
        <table class="w-full text-sm text-left border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2">Rank</th>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Skor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($history as $data)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $data['rank'] }}</td>
                        <td class="px-4 py-2">{{ $data['name'] }}</td>
                        <td class="px-4 py-2">{{ $data['score'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</x-filament::page>