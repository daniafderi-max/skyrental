<div class="max-w-7xl mx-auto p-6"
    x-on:display-duration-options.window="$dispatch('open-modal', 'duration-options-modal')" @close-modal="show = false"
    x-data="{
    price: @entangle('selectedPrice').live,

    // MAP + ADDRESS
    address: @entangle('address'),
    lat: @entangle('lat'),
    lng: @entangle('long'),

    type: @entangle('pickup_type').live,

    addressLoading: false,
    showSuggestions: false,
    suggestions: [],

    searchAddress() {
        if (this.address.length < 3) return;

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${this.address}`)
            .then(res => res.json())
            .then(data => {
                this.suggestions = data;
                this.showSuggestions = true;
            });
    },

    selectAddress(item) {
        this.address = item.display_name;
        this.lat = item.lat;
        this.lng = item.lon;

        this.showSuggestions = false;

        if (window.bookingMap?.map) {
            window.bookingMap.map.setView([item.lat, item.lon], 16);

            if (window.bookingMap.marker) {
                window.bookingMap.map.removeLayer(window.bookingMap.marker);
            }

            window.bookingMap.marker = L.marker([item.lat, item.lon]).addTo(window.bookingMap.map);
        }

        $wire.set('address', item.display_name);
        $wire.set('lat', item.lat);
        $wire.set('long', item.lon);
    }
}">
    @if ($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-10px]" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-[-10px]"
            class="fixed top-6 sm:right-20 sm:max-w-lg w-full z-50 p-4 rounded-lg shadow-lg border bg-white border-red-300 dark:bg-gray-800 dark:border-red-500/40">
            <div class="flex items-start gap-3">
                <!-- Icon Error -->
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M12 5a7 7 0 100 14a7 7 0 000-14z" />
                    </svg>
                </div>
                <!-- Text -->
                <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-gray-100">
                        Error
                    </p>
                    <ul class="mt-1 text-sm list-disc list-inside text-gray-700 dark:text-gray-300">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <!-- Close button -->
                <button @click="show = false"
                    class="text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif
    <form wire:submit="submit">

        {{-- NAV --}}
        <div class="flex justify-between mb-4 mt-0">
            @if ($step > 1)
                <button wire:click="back" type="button" class="px-4 py-2 border rounded-xl">
                    Kembali
                </button>
            @endif

            @if ($step < 3)
                <button wire:click="next" type="button" class="px-6 py-2 bg-black text-white rounded-xl">
                    Lanjut →
                </button>
            @else
                <button type="submit" class="px-6 py-2 bg-green-600 text-white disabled:bg-green-200 rounded-xl"
                    wire:loading.attr="disabled">
                    Konfirmasi
                </button>
            @endif
        </div>
        {{-- STEP INDICATOR --}}
        <div class="space-y-2 md:space-y-0 md:flex items-center justify-between mb-8">
            {{-- LEFT --}}
            <div>
                {{-- STEP INDICATOR --}}
                <div class="flex items-center gap-4 text-sm">

                    @php
                        $steps = [
                            1 => 'iPhone',
                            2 => 'Detail Customer',
                            3 => 'Review',
                        ];
                    @endphp

                    @foreach ($steps as $number => $label)
                        <div class="flex items-center gap-1">
                            <input type="radio" name="step" value="{{ $number }}"
                                @checked($step === $number) disabled
                                class="appearance-none h-4 w-4 rounded-full border hover:scale-125
                        {{ $step >= $number ? 'bg-black border-black' : 'border-gray-300' }}" />

                            <label
                                class="md:text-lg hidden sm:flex font-normal {{ $step >= $number ? 'text-black' : 'text-gray-400' }}">
                                {{ $label }}
                            </label>
                        </div>

                        @if (!$loop->last)
                            <span class="w-6 h-px bg-gray-300"></span>
                        @endif
                    @endforeach

                </div>
            </div>

            {{-- RIGHT --}}
            @if ($step === 1)
                <div class="flex items-center gap-4">
                    <input type="text" id="iphone_search"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Cari Tipe iPhone" wire:model.live.debounce.2500ms="iphone_search" />

                    {{-- NEXT BUTTON --}}
                    {{-- <button wire:click="next" class="flex items-center gap-2 px-4 py-2 bg-black text-white text-sm rounded">
                Lanjut
                <span>→</span>
            </button> --}}

                </div>
            @endif
        </div>


        {{-- CONTENT --}}
        @if ($step === 1)
            <div class="space-y-3 ">
                <div class="flex space-x-2 items-center ">
                    {{-- Date picker --}}
                    <div class="w-[75%]">
                        <div>
                            <label for="requested_booking_date"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tanggal
                                booking</label>
                            <livewire:booking.set-date wire:model="requested_booking_date" />
                        </div>
                        @error('requested_booking_date')
                            <span class="error">Pilih tanggal sewa</span>
                        @enderror
                    </div>
                    {{-- Time picker --}}
                    <div wire:ignore>
                        <label for="timepicker"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Waktu
                            mulai</label>
                        {{-- Initialize flatpickr for time selection --}}
                        <div class="border-gray-300" x-data="{
                            timepickerinstance: null,
                        
                            init() {
                                let timepick = document.querySelector('#timepicker')
                                this.timepickerinstance = flatpickr(timepick, {
                                    enableTime: true,
                                    noCalendar: true,
                                    dateFormat: 'H:i',
                                    time_24hr: true,
                                    defaultDate: @js($requested_time ? \Carbon\Carbon::parse($requested_time)->format('H:i') : null),
                                    onChange: (selectedDates, dateStr, instance) => {
                                        $wire.requested_time = dateStr; // Update Livewire property
                                        {{-- console.log(selectedDates) --}}
                                        {{-- $wire.setTime(dateStr); // Call Livewire method to set time --}}
                                    }
                                })
                            },
                        }">
                            <input id="timepicker" wire:ignore type="text" placeholder="YYYY-MM-DD"
                                class="w-full border-gray-300 rounded-sm" />
                        </div>
                        @error('requested_time')
                            <span class="error">Pilih jam sewa</span>
                        @enderror
                    </div>
                </div>
                <div wire:loading.flex
                    class="col-span-full flex items-center justify-center py-16 w-full absolute h-full top-0 left-0">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-orange-500 border-t-transparent">
                    </div>
                </div>
                <div class="col-span-full" wire:loading.class="opacity-30 pointer-events-none">
                    {{-- iPhone Grid --}}

                    @if ($iphones->isEmpty())
                        <div class="col-span-full flex flex-col items-center justify-center py-16 text-center "
                            wire:loading.remove>
                            <div class="mb-4 text-gray-400">
                                {{-- icon --}}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                </svg>
                            </div>

                            <p class="text-sm font-medium text-gray-700">
                                iPhone tidak ditemukan
                            </p>

                            <p class="text-xs text-gray-500 mt-1">
                                Coba gunakan nama atau serial number yang berbeda
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                            @foreach ($iphones as $iphone)
                                <button type="button"
                                    wire:click="selectIphone('{{ $iphone->id }}', '{{ $iphone->name }}', '{{ $iphone->serial_number }}')"
                                    @disabled(!$iphone->is_available)
                                    class="
                        relative rounded-xl border p-4 text-left transition
                        {{ $selectedIphoneId === $iphone->id ? 'border-black ring-2 ring-black' : 'border-gray-200' }}
                        {{ !$iphone->is_available ? 'opacity-40 cursor-not-allowed' : 'hover:border-black' }}
                    ">
                                    {{-- IMAGE --}}
                                    <div class="flex justify-center mb-4">
                                        <img src="{{ asset('storage/' . $iphone->gallery->image) }}"
                                            alt="{{ $iphone->name }}" class="h-40 object-contain">
                                    </div>

                                    {{-- NAME --}}
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $iphone->name }}
                                    </p>

                                    {{-- SERIAL --}}
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $iphone->serial_number ?? '—' }}
                                    </p>

                                    {{-- STATUS --}}
                                    @if (!$iphone->is_available)
                                        <span
                                            class="absolute top-3 right-3 text-xs bg-red-100 text-red-600 px-2 py-1 rounded">
                                            Sedang disewa
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
            {{-- <livewire:rent.steps.iphone wire:model="requested_booking_date" wire:model="requested_time" /> --}}
        @elseif ($step === 2)
            <div class="space-y-2">
                <div>
                    <label for="customer_name"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama
                        customer</label>
                    <input type="text" id="customer_name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="John" wire:model="customer_name" />
                    @error('customer_name')
                        <span class="error">Nama tida bole kosong 😒</span>
                    @enderror
                </div>
                <div>
                    <label for="customer_phone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Nomor Customer
                    </label>

                    <div x-data="{
                        countryCode: @entangle('countryCode'),
                        customerPhone: @entangle('customer_phone'),
                        countries: [
                            { code: '+62', name: 'Indonesia', flag: 'ID' },
                            { code: '+60', name: 'Malaysia', flag: 'MY' },
                            { code: '+65', name: 'Singapore', flag: 'SG' },
                            { code: '+66', name: 'Thailand', flag: 'TH' },
                            { code: '+63', name: 'Philippines', flag: 'PH' },
                            { code: '+95', name: 'Myanmar', flag: 'MM' },
                            { code: '+855', name: 'Cambodia', flag: 'KH' },
                            { code: '+856', name: 'Laos', flag: 'LA' },
                            { code: '+84', name: 'Vietnam', flag: 'VN' },
                            { code: '+673', name: 'Brunei', flag: 'BN' }
                        ]
                    }" class="flex gap-2">

                        <!-- Dropdown Kode Negara -->
                        <select x-model="countryCode"
                            class="w-[40%] bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5">
                            <template x-for="country in countries" :key="country.code">
                                <option :value="country.code" x-text="country.flag + ' ' + country.code"></option>
                            </template>
                        </select>

                        <!-- Input Nomor -->
                        <input type="tel" id="customer_phone" x-model="customerPhone"
                            @input="
        let raw = $event.target.value.replace(/[^0-9]/g, '');
        customerPhone = raw.match(/.{1,4}/g)?.join('-') || '';
    "
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
           focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 
           dark:text-white p-2.5"
                            placeholder="8123-4567-8901" />

                    </div>

                    @error('customer_phone')
                        <span class="error">Nomor tida bole kosong 😒</span>
                    @enderror
                </div>
                <div>
                    <label for="customer_email"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email
                        customer optional</label>
                    <input type="email" id="customer_email"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="jhondoe@gmail.com" wire:model="customer_email" />
                    @error('customer_email')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
        Tipe Pengambilan
    </label>

    <select wire:model.live="pickup_type"
        class="w-full p-2.5 border rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white">
        <option value="pickup">Pickup (Ambil di tempat)</option>
        <option value="delivery">Delivery (Diantar ke lokasi)</option>
    </select>
</div>
                <div>
                                <h1
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Alamat
                                </h1>
                                <div class="relative">

                                    <input type="text" x-model="address" @input.debounce.700ms="searchAddress"
                                        @focus="showSuggestions = true" @click.outside="showSuggestions = false"
                                        :disabled="type === 'pickup' || addressLoading"
                                        class="w-full p-2 pr-10 rounded-xl border-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-700 placeholder-gray-400 focus:border-orange-500 focus:outline-none"
                                        :placeholder="addressLoading
                                            ?
                                            'Mengambil alamat...' :
                                            'e.g. Jl. Merdeka No.123'">

                                    <!-- Spinner -->
                                    <div x-show="addressLoading" x-transition
                                        class="absolute right-3 top-1/2 -translate-y-1/2">

                                        <svg class="animate-spin h-5 w-5 text-orange-500"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>

                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8v4 a4 4 0 00-4 4H4z"></path>
                                        </svg>

                                    </div>

                                    <!-- Dropdown -->
                                    <div x-show="showSuggestions && suggestions.length > 0" x-transition
                                        class="absolute z-[999] w-full bg-white border rounded-xl shadow-lg mt-1 max-h-64 overflow-auto">

                                        <template x-for="(item, index) in suggestions" :key="index">

                                            <div @click="selectAddress(item)"
                                                class="p-3 hover:bg-gray-100 cursor-pointer border-b">

                                                <div class="font-medium text-sm" x-text="item.display_name"></div>

                                            </div>

                                        </template>

                                    </div>

                                </div>
                            </div>
                            @error('address')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                            <div x-show="$wire.pickup_type === 'delivery'" x-transition class="mt-3">
    <h1 class="text-lg font-medium mb-2">Pilih Lokasi Pengantaran</h1>

    <div wire:ignore>
        <div id="map" class="w-full h-64 rounded-xl border"></div>
    </div>

    <input type="hidden" wire:model="lat">
    <input type="hidden" wire:model="long">
</div>
                    <label for="customer_email"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tipe
                        Jaminan</label>
                    <select wire:model.live="jaminan_type"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="KTP">KTP</option>
                        <option value="KK">KK</option>
                        <option value="Kartu Pelajar">Kartu Pelajar</option>
                        <option value="SIM">SIM</option>
                        <option value="Kartu Identitas Mahasiswa">Kartu Identitas Mahasiswa</option>
                        <option value="Kartu Identitas Anak">Kartu Identitas Anak</option>
                    </select>

                    @error('jaminan_type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror

                </div>
                {{-- Payment --}}
                <div>
                    <div>
                        <h1 class="text-xl font-semibold mb-4">Metode Pembayaran</h1>
                        <div class="space-y-2">
                            @if (!empty($payments))
                                <div class="w-48">
                                    <x-mary-select wire:model.live="selectedPaymentId" :options="$payments"
                                        placeholder="Metode pembayaran" placeholder-value="1" option-value="id"
                                        option-label="name" />
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300 italic leading-relaxed">
                        <span>{{ $selectedPayment->description }}</span>
                    </div>

                </div>
            </div>
        @elseif ($step === 3)
            <div class="bg-white rounded-xl shadow p-6 space-y-6 max-w-lg mx-auto">

                {{-- Header --}}
                <h2 class="text-lg font-semibold text-center">
                    Review detail booking
                </h2>

                {{-- STEP 1 : Device --}}
                <div class="border-t pt-4">
                    <div class="flex justify-between items-start">
                        <div class="space-y-3 text-sm text-gray-700 w-full">
                            <div class="flex justify-between gap-6">
                                <h1 class="text-gray-500">Tipe iPhone</h1>
                                <h1 class="font-medium text-end">
                                    {{ $iphone_name ?? '-' }}
                                </h1>
                            </div>
                            <div class="flex justify-between gap-6">
                                <h1 class="text-gray-500">Serial Number</h1>
                                <h1 class="font-medium text-end">
                                    {{ $serial_number ?? '-' }}
                                </h1>
                            </div>

                            <div class="flex justify-between gap-6">
                                <span class="text-gray-500">Alamat</span>
                                <span class="font-medium">
                                    {{ $address ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2 : Waktu --}}
                <div class="border-t pt-4">
                    <div class="flex justify-between items-start ">
                        <div class="space-y-3 text-sm text-gray-700 w-full">
                            <div class="flex justify-between gap-6  w-full">
                                <span class="text-gray-500">
                                   Tanggal Booking
                                </span>
                                <span class="font-medium text-end">
                                    {{ $requested_booking_date }} {{ $requested_time }}
                                </span>
                            </div>

                            <div class="flex justify-between gap-6">
                                <span class="text-gray-500">Durasi</span>
                                <span class="font-medium text-end">
                                    @if ($jumlah > 1)
                                        @switch($unit)
                                            @case('Jam')
                                                {{ $jumlah }} jam
                                            @break

                                            @case('Hari')
                                                {{ $jumlah }} hari ({{ $jumlah * 24 }} jam)
                                            @break

                                            @case('Minggu')
                                                {{ $jumlah }} minggu ({{ $jumlah * 24 * 7 }} jam)
                                            @break

                                            @case('Bulan')
                                                {{ $jumlah }} bulan ({{ $jumlah * 24 * 30 }} jam)
                                            @break

                                            @default
                                        @endswitch
                                    @else
                                        {{ $selectedDuration }} jam
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 3 : Data Customer --}}
                <div class="border-t pt-4">
                    <div class="flex justify-between items-start">
                        <div class="space-y-3 text-sm text-gray-700 w-full">
                            <div class="flex justify-between gap-6">
                                <span class="text-gray-500">Nama Lengkap</span>
                                <span class="font-medium text-end">
                                    {{ $customer_name ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between gap-6">
                                <span class="text-gray-500">Nomor WhatsApp</span>
                                <span class="font-medium text-end">
                                    {{ $countryCode }}{{ $customer_phone }}
                                </span>
                            </div>

                            <div class="flex justify-between gap-6">
                                <span class="text-gray-500">Email</span>
                                <span class="font-medium text-end">
                                    {{ $customer_email ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between gap-6">
                                <span class="text-gray-500">Jenis Jaminan</span>
                                <span class="font-medium text-end">
                                    {{ $jaminan_type }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TOTAL --}}
                <div class="border-t pt-4">
                    <div class="flex justify-between text-base font-semibold">
                        <span>Total Harga</span>
                        <span>
                            Rp {{ number_format($selectedPrice, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                <x-mary-checkbox label="Kirim pesan WhatsApp ke customer" wire:model="sendWhatsapp"
                    hint="Centang jika ingin mengirim pesan otomatis" />

            </div>

        @endif


    </form>
    <x-modal name="duration-options-modal" max-width="md">
        {{-- Duration --}}
        <div class="m-3">
            <label for="duration" class="block mb-3 text-xl font-medium text-gray-900 dark:text-white">Pilih
                Durasi</label>
            <div class="grid md:grid-cols-3 grid-cols-2 gap-1" x-data="{
                activeTab: @entangle('selectedDuration').live,
                price: @entangle('selectedPrice').live,
            
                setActiveTab(tab, priceValue) {
                    this.activeTab = tab;
                    this.price = priceValue;
                }
            }">
                @foreach ($durations as $item)
                    <div @click="setActiveTab({{ $item['hours'] }}, {{ $item['price'] }})"
                        :class="{ 'bg-black text-white ': activeTab === {{ $item['hours'] }} }"
                        class="p-3 font-semibold text-base text-center rounded-xl border-2 border-slate-300 cursor-pointer w-full hover:shadow-xl hover:scale-105 transition">
                        {{ $item['hours'] }} jam
                    </div>
                @endforeach
            </div>

            @error('selectedDuration')
                <span class="error">Pilih durasi sewa😊</span>
            @enderror

            <div class="space-y-3 relative" x-data="{ openDuration: false }">

                <div class="flex space-x-2 w-full" x-data="{
                    activeTab: @entangle('selectedDuration').live,
                    price: @entangle('selectedPrice').live,
                    {{-- jumlah: @entangle('jumlah').defer, --}}
                
                    setActiveTab(tab, priceValue) {
                        this.activeTab = tab;
                        this.price = priceValue;
                        {{-- this.jumlah = 0; --}}
                        $wire.jumlah = 0;
                    }
                }">
                </div>
                <button
                    @click="() => { openDuration = !openDuration;
                                                    window.dispatchEvent(
                                                        new CustomEvent('open-bottom-sheet', {
                                                            detail: { id: 'customDuration' }
                                                        })
                                                    )
                                                }"
                    class="w-full p-2.5 cursor-pointer text-center font-semibold rounded-2xl border-2 transition-colors duration-150 text-black border-slate-900 hover:bg-slate-900 hover:text-white dark:text-gray-100 dark:border-gray-600 dark:hover:bg-gray-100 dark:hover:text-gray-900">
                    Durasi Custom
                </button>
                <div x-show="openDuration" x-collapse x-cloak class="top-5 z-50 right-0 mt-2 hidden sm:flex w-full ">
                    <div class="rounded-md ring-1 ring-black ring-opacity-5 w-full">

                        <div
                            class="p-4  dark:bg-gray-800 text-lg w-full border-2 border-slate-900 shadow-xl rounded-xl">
                            <div class="relative z-10">
                                <label for="customDuration" class="block text-sm font-medium text-white/90 ">
                                    Masukkan Durasi Sendiri
                                </label>

                                <div class="flex items-center gap-3 w-full" x-data="{ unit: $wire.entangle('unit') }">
                                    <!-- Input jumlah -->
                                    <input id="customDuration" type="number" wire:model.live.debounce.250ms="jumlah"
                                        min="24" class="w-24 px-2 py-1.5 border-2 border-black rounded-xl"
                                        placeholder="Jumlah">

                                    <!-- Pilihan unit waktu -->
                                    <div
                                        class="flex overflow-hidden w-full justify-between border-2 border-black rounded-xl">
                                        <template x-for="opt in ['Hari','Minggu','Bulan']" :key="opt">
                                            <button type="button" @click="$wire.setCustom(opt); unit = opt"
                                                :class="{
                                                    'bg-black text-white': unit ===
                                                        opt,
                                                    'hover:border-white/40 hover:bg-white/10': unit !== opt
                                                }"
                                                class="px-3 py-2 text-sm border-r border-white/20 transition-all duration-200 w-full">
                                                <span x-text="opt"></span>
                                            </button>
                                        </template>

                                    </div>
                                </div>

                                <p class="text-xs mt-1 font-semibold italic text-gray-400">Contoh: 3 Hari, 2
                                    Minggu, 1 Bulan</p>
                            </div>
                            <div class="mt-4 text-base font-medium">
                                <p class="text-sm ">
                                    Durasi sewa:
                                    @switch($unit)
                                        @case('Jam')
                                            {{ $jumlah }} jam
                                        @break

                                        @case('Hari')
                                            {{ $jumlah }} hari ({{ $jumlah * 24 }} jam)
                                        @break

                                        @case('Minggu')
                                            {{ $jumlah }} minggu ({{ $jumlah * 24 * 7 }} jam)
                                        @break

                                        @case('Bulan')
                                            {{ $jumlah }} bulan ({{ $jumlah * 24 * 30 }} jam)
                                        @break

                                        @default
                                    @endswitch
                                    <br>
                                    Total Harga: Rp <span x-text="new Intl.NumberFormat('id-ID').format(price)"
                                        class="text-red-500"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>
    <x-bottom-sheet id="customDuration" title="Pilih durasi" glass="">
        <div
            class="max-w-md mx-auto space-y-6 
    bg-gradient-to-br from-white/15 to-white/5 dark:from-gray-800/40 dark:to-gray-900/20
    backdrop-blur-xl 
    border border-white/20 dark:border-gray-700/50 
    rounded-3xl shadow-[0_8px_32px_0_rgba(0,0,0,0.25)]
    p-6 relative overflow-hidden
    transition-all duration-300 hover:scale-[1.01] hover:shadow-[0_12px_48px_0_rgba(0,0,0,0.35)]">

            <!-- Gradient overlay untuk efek kaca -->
            <div
                class="absolute inset-0 bg-gradient-to-tr from-white/10 via-transparent to-white/5 pointer-events-none rounded-3xl">
            </div>
            <div class="relative z-10">
                <label for="customDuration" class="block text-sm font-medium text-white/90 mb-2">
                    Masukkan Durasi Sendiri
                </label>

                <div class="flex items-center gap-3 w-full" x-data="{ unit: $wire.entangle('unit') }">
                    <!-- Input jumlah -->
                    <input id="customDuration" type="number" wire:model.live.debounce.250ms="jumlah" min="24"
                        class="w-24 px-2 py-1.5 border border-white/30 
             rounded-xl bg-white/10 backdrop-blur-sm text-center 
             text-white placeholder-white/50 
             focus:ring-2 focus:ring-blue-400/70 focus:border-blue-300 
             outline-none transition-all duration-200
             hover:bg-white/20"
                        placeholder="Jumlah">

                    <!-- Pilihan unit waktu -->
                    <div
                        class="flex bg-white/5 backdrop-blur-sm border border-white/20 rounded-xl overflow-hidden w-full justify-between">
                        <template x-for="opt in ['Hari','Minggu','Bulan']" :key="opt">
                            <button type="button" @click="$wire.setCustom(opt); unit = opt"
                                :class="{
                                    'bg-white/20 text-white backdrop-blur-sm border-white/40': unit === opt,
                                    'hover:border-white/40 hover:bg-white/10': unit !== opt
                                }"
                                class="px-3 py-2 text-sm border-r border-white/20 transition-all duration-200 w-full">
                                <span x-text="opt"></span>
                            </button>
                        </template>

                    </div>
                </div>

                <p class="text-xs text-white/70 mt-1">Contoh: 3 Hari, 2 Minggu, 1 Bulan</p>
            </div>


            <!-- Preview Total -->
            <div
                class="p-4 rounded-2xl border border-white/30 
        bg-white/10 backdrop-blur-md relative z-10
        transition-all duration-200 hover:bg-white/20">
                <p class="text-sm text-white/90">
                    Durasi sewa:
                    @switch($unit)
                        @case('Jam')
                            {{ $jumlah }} jam
                        @break

                        @case('Hari')
                            {{ $jumlah }} hari ({{ $jumlah * 24 }} jam)
                        @break

                        @case('Minggu')
                            {{ $jumlah }} minggu ({{ $jumlah * 24 * 7 }} jam)
                        @break

                        @case('Bulan')
                            {{ $jumlah }} bulan ({{ $jumlah * 24 * 30 }} jam)
                        @break

                        @default
                    @endswitch
                    <br>
                    Total Harga: Rp <span x-text="new Intl.NumberFormat('id-ID').format(price)"></span>
                </p>
            </div>
        </div>
    </x-bottom-sheet>
</div>

<script>
window.bookingMap = {
    map: null,
    marker: null
};

window.addEventListener('livewire:update', () => {
    if (@this.get('pickup_type') === 'pickup') {
        if (window.bookingMap.marker) {
            window.bookingMap.map.removeLayer(window.bookingMap.marker);
            window.bookingMap.marker = null;
        }
    }
});

async function initMap() {
    if (window.bookingMap.map) return;

    const map = L.map('map').setView([-8.219, 114.369], 13);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    window.bookingMap.map = map;

    map.on('click', async function (e) {
        const { lat, lng } = e.latlng;

        // FIX AMAN
        if (window.bookingMap.marker) {
            map.removeLayer(window.bookingMap.marker);
        }

        window.bookingMap.marker = L.marker([lat, lng]).addTo(map);

        @this.set('lat', lat);
        @this.set('long', lng);

        try {
            const res = await fetch(`/geo/reverse?lat=${lat}&lon=${lng}`);
            const data = await res.json();

            if (data.display_name) {
                @this.set('address', data.display_name);
            }
        } catch (err) {
            console.error(err);
        }
    });

    setTimeout(() => {
        map.invalidateSize();
    }, 300);
}

function setMarker(lat, lng) {
    const map = window.bookingMap.map;

    if (!map) return;

    if (window.bookingMap.marker) {
        window.bookingMap.map.removeLayer(window.bookingMap.marker);
    }

    window.bookingMap.marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    window.bookingMap.marker.on('dragend', async function () {
        const pos = window.bookingMap.marker.getLatLng();

        const lat = pos.lat;
        const lng = pos.lng;

        const res = await fetch(`/geo/reverse?lat=${lat}&lon=${lng}`);
        const data = await res.json();

        if (data.display_name) {
            @this.set('address', data.display_name);
            @this.set('lat', lat);
            @this.set('long', lng);
        }
    });
}

async function detectCurrentLocation() {

    if (!navigator.geolocation) return;

    navigator.geolocation.getCurrentPosition(async (position) => {

        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        map.setView([lat, lng], 16);
        setMarker(lat, lng);

        try {
            const res = await fetch(`/geo/reverse?lat=${lat}&lon=${lng}`);
            const data = await res.json();

            if (data.display_name) {
                @this.set('address', data.display_name);
                @this.set('lat', lat);
                @this.set('long', lng);
            }

        } catch (err) {
            console.error(err);
        }

    });
}

// LIVEWIRE HOOK
document.addEventListener('livewire:init', () => {
    waitForMap();
});

document.addEventListener('livewire:navigated', () => {
    waitForMap();
});

function waitForMap() {
    const check = setInterval(() => {
        const el = document.getElementById('map');

        if (el && !window.bookingMap.map) {
            clearInterval(check);
            initMap();
        }
    }, 300);
}
</script>