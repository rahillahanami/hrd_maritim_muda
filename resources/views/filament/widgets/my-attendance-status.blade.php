<x-filament::widget>
    <x-filament::card>
        {{-- Header with icon and title --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/20">
                <x-heroicon-o-finger-print class="w-5 h-5 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">
                    Status Absensi Hari Ini
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ now()->format('l, d F Y') }}
                </p>
            </div>
        </div>

        {{-- Content section --}}
        <div class="space-y-4">
            {{-- Status badge --}}
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</span>
                <x-filament::badge 
                    :color="$this->statusColor" 
                    size="sm"
                >
                    {{ $this->statusToday }}
                </x-filament::badge>
            </div>

            {{-- Attendance details --}}
            @if ($this->checkInTime)
                {{-- Check-in time --}}
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-arrow-right-on-rectangle class="w-4 h-4 text-green-500" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Check In</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $this->checkInTime->format('H:i') }}
                    </span>
                </div>

                {{-- Early/Late status --}}
                @if ($this->earlyMinutes > 0)
                    <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">
                            Datang Awal: <strong>{{ $this->earlyMinutes }} menit</strong>
                        </span>
                    </div>
                @elseif ($this->lateMinutes > 0)
                    <div class="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <x-heroicon-m-exclamation-triangle class="w-4 h-4 text-red-500" />
                        <span class="text-sm font-medium text-red-700 dark:text-red-300">
                            Terlambat: <strong>{{ $this->lateMinutes }} menit</strong>
                        </span>
                    </div>
                @else
                    <div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">
                            <strong>Tepat Waktu</strong>
                        </span>
                    </div>
                @endif

                {{-- Check-out time (if available) --}}
                @if ($this->checkOutTime)
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-arrow-left-on-rectangle class="w-4 h-4 text-red-500" />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Check Out</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $this->checkOutTime->format('H:i') }}
                        </span>
                    </div>
                @endif
            @else
                {{-- Not checked in yet --}}
                <div class="flex items-center gap-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <x-heroicon-m-clock class="w-4 h-4 text-yellow-500" />
                    <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">
                        Anda belum melakukan Check In hari ini
                    </span>
                </div>
            @endif
        </div>
        
        {{-- Action button --}}
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-end">
                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Resources\AttendanceResource::getUrl('index')"
                    color="gray"
                    outlined
                    size="sm"
                    icon="heroicon-m-eye"
                >
                    Lihat Semua Absensi
                </x-filament::button>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>