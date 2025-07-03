<x-filament::page>
    <form wire:submit.prevent="evaluate">
        {{ $this->form }}
        <x-filament::button type="submit" class="mt-4">Evaluasi Sekarang</x-filament::button>
    </form>

    @if ($results)
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900 dark:border-blue-700"> {{-- <<< MODIFIKASI INI --}}
            <h3 class="text-md font-semibold text-blue-800 mb-2 dark:text-blue-200">Parameter Perhitungan WASPAS</h3> {{-- <<< MODIFIKASI INI --}}
            <p class="text-sm text-blue-700 dark:text-blue-300"> {{-- <<< MODIFIKASI INI --}}
                Lambda (λ) = <span class="font-bold">{{ $results[0]['lambda'] ?? 0.5 }}</span><br>
                Formula WASPAS: <code>λ × SAW + (1-λ) × WPM</code><br>
                = <span class="font-bold">{{ $results[0]['lambda'] ?? 0.5 }}</span> × SAW + 
                <span class="font-bold">{{ 1 - ($results[0]['lambda'] ?? 0.5) }}</span> × WPM
            </p>
        </div>

        <div class="mt-6">
            <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">Detail Perhitungan WASPAS</h2> {{-- <<< MODIFIKASI INI --}}

            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">1. Matriks Keputusan (Skor Asli)</h3> {{-- <<< MODIFIKASI INI --}}
                <div class="text-xs text-gray-600 mb-2 dark:text-gray-400"> {{-- <<< MODIFIKASI INI --}}
                    {{-- Formula Normalisasi --}}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border border-gray-300 dark:border-gray-600"> {{-- <<< MODIFIKASI INI --}}
                        <thead class="bg-gray-100 dark:bg-gray-800"> {{-- <<< MODIFIKASI INI --}}
                            <tr>
                                <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Karyawan</th> {{-- <<< MODIFIKASI INI --}}
                                @php
                                    $criteria = \App\Models\EvaluationCriteria::all();
                                @endphp
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200"> {{-- <<< MODIFIKASI INI --}}
                                        {{ $criterion->name }}<br>
                                        <small class="text-gray-500 dark:text-gray-400">({{ ucfirst($criterion->type) }}, W={{ $criterion->weight }})</small>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t border-gray-300 dark:border-gray-700"> {{-- <<< MODIFIKASI INI --}}
                                    <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 font-medium text-gray-800 dark:text-gray-200">{{ $result['name'] }}</td> {{-- <<< MODIFIKASI INI --}}
                                    @foreach($criteria as $criterion)
                                        <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200"> {{-- <<< MODIFIKASI INI --}}
                                            @php
                                                $originalScore = \App\Models\EmployeeScore::where('employee_id', $result['employee_id'])
                                                    ->where('evaluation_id', $evaluation_id)
                                                    ->where('evaluation_criteria_id', $criterion->id)
                                                    ->value('score') ?? 0;
                                            @endphp
                                            {{ $originalScore }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">2. Matriks Normalisasi</h3> {{-- <<< MODIFIKASI INI --}}
                <div class="text-xs text-gray-600 mb-2 dark:text-gray-400"> {{-- <<< MODIFIKASI INI --}}
                    Formula: <strong>Benefit:</strong> r<sub>ij</sub> = x<sub>ij</sub> / max(x<sub>ij</sub>) &nbsp;&nbsp;
                    <strong>Cost:</strong> r<sub>ij</sub> = min(x<sub>ij</sub>) / x<sub>ij</sub>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border border-gray-300 dark:border-gray-600"> {{-- <<< MODIFIKASI INI --}}
                        <thead class="bg-gray-100 dark:bg-gray-800"> {{-- <<< MODIFIKASI INI --}}
                            <tr>
                                <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Karyawan</th>
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">{{ $criterion->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t border-gray-300 dark:border-gray-700"> {{-- <<< MODIFIKASI INI --}}
                                    <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 font-medium text-gray-800 dark:text-gray-200">{{ $result['name'] }}</td>
                                    @foreach($criteria as $criterion)
                                        <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">
                                            {{ round($result['normalized_scores'][$criterion->id] ?? 0, 4) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">3. Perhitungan SAW (Simple Additive Weighting)</h3> 
                <div class="text-xs text-gray-600 mb-2 dark:text-gray-400">
                    Formula: SAW = Σ (w<sub>j</sub> × r<sub>ij</sub>)
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border border-gray-300 dark:border-gray-600"> 
                        <thead class="bg-gray-100 dark:bg-gray-800"> 
                            <tr>
                                <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Karyawan</th>
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">
                                        {{ $criterion->name }}<br>
                                        <small>(w={{ $criterion->weight }})</small>
                                    </th>
                                @endforeach
                                <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center bg-blue-50 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-semibold">Total SAW</th> {{-- <<< MODIFIKASI INI --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t border-gray-300 dark:border-gray-700"> 
                                    <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 font-medium text-gray-800 dark:text-gray-200">{{ $result['name'] }}</td>
                                    @foreach($criteria as $criterion)
                                        @php
                                            $normalized = $result['normalized_scores'][$criterion->id] ?? 0;
                                            $weight = $criterion->weight; // Bobot utuh
                                            $sawComponent = ($weight ) * $normalized; // Perhitungan dengan bobot desimal
                                        @endphp
                                        <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">
                                            {{ round($sawComponent, 4) }}<br>
                                            <small class="text-gray-500 dark:text-gray-400">({{ $weight }} × {{ round($normalized, 4) }})</small>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center bg-blue-50 dark:bg-blue-900 font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $result['saw_scores_sum'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">4. Perhitungan WPM (Weighted Product Method)</h3> 
                <div class="text-xs text-gray-600 mb-2 dark:text-gray-400"> {{-- <<< MODIFIKASI INI --}}
                    Formula: WPM = ∏ (r<sub>ij</sub>)<sup>w<sub>j</sub></sup>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border border-gray-300 dark:border-gray-600"> {{-- <<< MODIFIKASI INI --}}
                        <thead class="bg-gray-100 dark:bg-gray-800"> {{-- <<< MODIFIKASI INI --}}
                            <tr>
                                <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Karyawan</th>
                                @foreach($criteria as $criterion)
                                    <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">
                                        {{ $criterion->name }}<br>
                                        <small>(w={{ $criterion->weight }})</small>
                                    </th>
                                @endforeach
                                <th class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center bg-green-50 dark:bg-green-900 text-green-800 dark:text-green-200 font-semibold">Total WPM</th> {{-- <<< MODIFIKASI INI --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-t border-gray-300 dark:border-gray-700"> {{-- <<< MODIFIKASI INI --}}
                                    <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 font-medium text-gray-800 dark:text-gray-200">{{ $result['name'] }}</td>
                                    @foreach($criteria as $criterion)
                                        @php
                                            $normalized = $result['normalized_scores'][$criterion->id] ?? 0;
                                            $weight = $criterion->weight;
                                            $wpComponent = $normalized > 0 ? pow($normalized, ($weight / 100)) : 0; // Perhitungan dengan bobot desimal
                                        @endphp
                                        <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">
                                            {{ round($wpComponent, 4) }}<br>
                                            <small class="text-gray-500 dark:text-gray-400">({{ round($normalized, 4) }})<sup>{{ $weight  }}</sup></small>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-center bg-green-50 dark:bg-green-900 font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $result['wp_scores_product'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">5. Hasil Akhir WASPAS</h3> {{-- <<< MODIFIKASI INI --}}
                <div class="text-xs text-gray-600 mb-2 dark:text-gray-400"> {{-- <<< MODIFIKASI INI --}}
                    Formula: WASPAS = λ × SAW + (1-λ) × WPM = 
                    {{ $results[0]['lambda'] ?? 0.5 }} × SAW + {{ 1 - ($results[0]['lambda'] ?? 0.5) }} × WPM
                </div>
                <table class="w-full text-sm text-left border border-gray-300 dark:border-gray-600"> {{-- <<< MODIFIKASI INI --}}
                    <thead class="bg-gray-100 dark:bg-gray-800"> {{-- <<< MODIFIKASI INI --}}
                        <tr>
                            <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Rank</th>
                            <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Nama Karyawan</th>
                            <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">SAW Total</th>
                            <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">WPM Total</th>
                            <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Perhitungan WASPAS</th>
                            <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $i => $r)
                            <tr class="border-t border-gray-300 dark:border-gray-700"> {{-- <<< MODIFIKASI INI --}}
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-center font-semibold text-gray-800 dark:text-gray-200">
                                    @if($r['rank'] === 1) 🥇 1
                                    @elseif($r['rank'] === 2) 🥈 2
                                    @elseif($r['rank'] === 3) 🥉 3
                                    @else {{ $r['rank'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 font-medium text-gray-800 dark:text-gray-200">{{ $r['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">{{ round($r['saw_scores_sum'], 4) }}</td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-center text-gray-800 dark:text-gray-200">{{ round($r['wp_scores_product'], 4) }}</td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-center text-xs text-gray-800 dark:text-gray-200">
                                    {{ $r['lambda'] ?? 0.5 }} × {{ round($r['saw_scores_sum'], 4) }} + 
                                    {{ 1 - ($r['lambda'] ?? 0.5) }} × {{ round($r['wp_scores_product'], 4) }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-center font-bold text-lg text-gray-800 dark:text-gray-200">{{ $r['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700"> {{-- <<< MODIFIKASI INI --}}
                <h3 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">Penjelasan Metode WASPAS</h3> {{-- <<< MODIFIKASI INI --}}
                <div class="text-sm text-gray-700 space-y-2 dark:text-gray-300"> {{-- <<< MODIFIKASI INI --}}
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
        <h2 class="text-lg font-bold mb-2 text-gray-800 dark:text-gray-200">Histori Evaluasi Periode Ini</h2> {{-- <<< MODIFIKASI INI --}}
        <table class="w-full text-sm text-left border border-gray-300 dark:border-gray-600"> {{-- <<< MODIFIKASI INI --}}
            <thead class="bg-gray-200 dark:bg-gray-700"> {{-- <<< MODIFIKASI INI --}}
                <tr>
                    <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Rank</th>
                    <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Nama</th>
                    <th class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">Skor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($history as $data)
                    <tr class="border-t border-gray-300 dark:border-gray-700"> {{-- <<< MODIFIKASI INI --}}
                        <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">{{ $data['rank'] }}</td>
                        <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">{{ $data['name'] }}</td>
                        <td class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200">{{ $data['score'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</x-filament::page>