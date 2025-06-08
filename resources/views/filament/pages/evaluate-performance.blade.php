<x-filament::page>
    <form wire:submit.prevent="evaluate">
        {{ $this->form }}
        <x-filament::button type="submit" class="mt-4">Evaluasi Sekarang</x-filament::button>
    </form>

    @if ($results)
        <div class="mt-6">
            <table class="w-full text-sm text-left border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Nama Karyawan</th>
                        <th class="px-4 py-2">Nilai Akhir</th>
                        <th class="px-4 py-2">Ranking</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $i => $r)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $i + 1 }}</td>
                            <td class="px-4 py-2">{{ $r['name'] }}</td>
                            <td class="px-4 py-2">{{ $r['total'] }}</td>
                            <td class="px-4 py-2 font-semibold">
                                @if($r['rank'] === 1) 🥇
                                @elseif($r['rank'] === 2) 🥈
                                @elseif($r['rank'] === 3) 🥉
                                @else #{{ $r['rank'] }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <form wire:submit.prevent="submit" class="mt-4">
                <br>
                <x-filament::button type="submit" color="success">Simpan Hasil Evaluasi</x-filament::button>
            </form>
        </div>
    @endif

    @if ($history)
    <div class="mt-10">
        <h2 class="text-lg font-bold mb-2">Histori Evaluasi Periode Ini</h2>
        <table class="w-full text-sm text-left border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Skor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($history as $data)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $data['name'] }}</td>
                        <td class="px-4 py-2">{{ $data['score'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

</x-filament::page>

