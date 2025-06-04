<x-filament::page>
    {{ $this->form }}

    @if ($results)
        <div class="mt-6 space-y-2">
            <h3 class="text-lg font-bold">Hasil Evaluasi</h3>
            <table class="w-full text-sm text-left border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 border">Kriteria</th>
                        <th class="px-3 py-2 border">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $result)
                        <tr>
                            <td class="px-3 py-1 border">{{ $result->criteria->name ?? '-' }}</td>
                            <td class="px-3 py-1 border">{{ $result->score }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::page>
