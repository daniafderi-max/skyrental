<div x-data="{
    open: false,
    showDatepicker: false,
    type: @entangle('pickup_type'),
    selectedDateFormatted: @entangle('selectedDateFormatted').live,
    month: null,
    year: null,
    daysInMonth: [],
    blankdays: [],
    monthNames: [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ],

    selectedDate: @entangle('selectedDate').live,
    selectedHour: @entangle('selectedHour').live,
    selectedMinute: @entangle('selectedMinute').live,
    hours: Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0')),
    minutes: ['00', '15', '30', '45'],

    price: @entangle('selectedPrice').live,
    address: @entangle('address').defer,
    lat: @entangle('lat').defer,
    lng: @entangle('long').defer,
    searchTimeout: null,
    suggestions: [],
    showSuggestions: false,
    addressLoading: false,

    init() {
        window.bookingMap = this;

        console.log(this.price)
        if (this.selectedDate) {
            this.selectedDate = new Date(this.selectedDate);
        } else {
            const today = new Date();
            this.selectedDate = today;
        }

        this.month = this.selectedDate.getMonth();
        this.year = this.selectedDate.getFullYear();
        this.selectedDateFormatted = this.formatDate(this.selectedDate);
        this.calculateDays();
    },

    isToday(date) {
        const today = new Date();
        const d = new Date(this.year, this.month, date);
        return today.toDateString() === d.toDateString();
    },

    formatDate(date) {
        const day = date.getDate();
        const month = this.monthNames[date.getMonth()];
        const year = date.getFullYear();
        const time = `${this.selectedHour}:${this.selectedMinute}`;
        return `${day} ${month} ${year}, ${time}`;
    },


    pickDate(date) {
        this.selectedDate = new Date(this.year, this.month, date);
        this.selectedDateFormatted = this.formatDate(this.selectedDate);
    },

    calculateDays() {
        const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
        const firstDayOfMonth = new Date(this.year, this.month, 1).getDay(); // 0 = Minggu

        this.blankdays = Array(firstDayOfMonth).fill(null);
        this.daysInMonth = Array.from({ length: daysInMonth }, (_, i) => i + 1);
    },

    prevMonth() {
        this.month--;
        if (this.month < 0) {
            this.month = 11;
            this.year--;
        }
        this.calculateDays();
    },

    nextMonth() {
        this.month++;
        if (this.month >
            11) {
            this.month = 0;
            this.year++;
        }
        this.calculateDays();
    },

    isSelectedDate(date) {
        const d = new Date(this.year, this.month, date);
        return this.selectedDate.toDateString() === d.toDateString();
    },

    pickDate(date) {
        const picked = new Date(this.year, this.month, date);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // buang jam agar akurat membandingkan tanggal

        if (picked < today) return; // blokir jika tanggal sebelum hari ini

        this.selectedDate = picked;
        this.selectedDateFormatted = this.formatDate(this.selectedDate);
    },

    isPastDate(date) {
        const picked = new Date(this.year, this.month, date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return picked < today;
    },

    createBooking() {
        {{-- $dispatch('booking-create') --}}
        $dispatch('open-modal', 'user-booking-create')
    },

    async searchAddress() {

        if (!this.address || this.address.length < 4) return;

        try {

            const res = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.address)}`
            );

            const data = await res.json();

            if (data.length > 0) {

                const place = data[0];

                this.lat = parseFloat(place.lat);
                this.lng = parseFloat(place.lon);

                map.setView([this.lat, this.lng], 16);

                if (marker) {
                    map.removeLayer(marker);
                }

                marker = L.marker([this.lat, this.lng]).addTo(map);
            }

        } catch (e) {
            console.error(e);
        }
    },

    async searchAddress() {

        if (!this.address || this.address.length < 4) {

            this.suggestions = [];
            return;
        }

        this.addressLoading = true;

        try {

            const res = await fetch(
                `/geo/search?q=${encodeURIComponent(this.address)}`
            );

            const data = await res.json();

            this.suggestions = data;
            this.showSuggestions = true;

        } catch (e) {

            console.error(e);

        } finally {

            this.addressLoading = false;
        }
    },

    selectAddress(item) {

        this.address = item.display_name;

        this.lat = parseFloat(item.lat);
        this.lng = parseFloat(item.lon);

        this.showSuggestions = false;

        map.setView([this.lat, this.lng], 16);

        if (marker) {
            map.removeLayer(marker);
        }

        marker = L.marker([this.lat, this.lng]).addTo(map);

        @this.set('address', this.address);
        @this.set('lat', this.lat);
        @this.set('long', this.lng);
    }

}" x-init="$watch('selectedHour', () => selectedDateFormatted = formatDate(selectedDate));
$watch('selectedMinute', () => selectedDateFormatted = formatDate(selectedDate));" class="max-w-screen-2xl mx-auto">
    <div class="lg:flex xl:space-x-3 min-h-[70vh] w-full xl:mt-20 ">
        <div class="w-full lg:w-[60%] xl:w-[70%] xl:flex md:space-x-3
           xl:sticky top-10 h-fit">

            {{-- Image --}}
            <div class="w-full h-[40vh] relative">
                <img src="{{ asset('storage/' . $iphone->gallery->image) }}" alt="{{ $iphone->name }}"
                    class="w-full h-full object-contain absolute">
            </div>

            {{-- Content --}}
            <div class="lg:w-full lg:my-0 p-2 mt-6">

                {{-- Rating --}}
                <div class="flex justify-end space-x-1 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"
                        class="w-5 h-5 text-yellow-400">
                        <path
                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                    </svg>
                    <span class="text-sm font-semibold
                       text-gray-700 dark:text-gray-300">
                        ( {{ $avgRating }} )
                    </span>
                </div>

                {{-- Title + Price --}}
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-3xl font-medium font-neulis
                           text-gray-900 dark:text-gray-100">
                            {{ $iphone->name }}
                        </h1>
                        <p class="text-lg dark:text-gray-200 font-neulis text-gray-500">{{ $iphone->serial_number }}</p>
                    </div>

                    <div>
                        <div class="flex space-x-1 md:hidden">
                            <h1
                                class="text-base font-medium
                               text-gray-700 dark:text-gray-300">
                                Rp
                            </h1>
                            <h1 class="text-xl font-bold
                               text-gray-900 dark:text-gray-100"
                                x-text="new Intl.NumberFormat('id-ID').format(price)">
                            </h1>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div
                    class="prose prose-base lg:prose-lg max-w-none
                   text-black dark:text-gray-200
                   prose-li:text-black dark:prose-li:text-gray-200
                   prose-a:text-blue-600 dark:prose-a:text-blue-400
                   md:flex flex-col hidden">

                    {!! $iphone->description !!}
                </div>
            </div>
        </div>

        <div
            class="md:full lg:w-[40%] xl:w-[28%] h-fit md:border-2 border-y-gray-300 md:border-slate-900 p-3 md:p-5 rounded-xl mx-3">
            <div>
                {{-- BOOKING --}}
                <div class="flex flex-col sm:space-y-3 space-y-2">
                    <div class="space-y-3">
                        <h1 class="text-lg md:text-2xl font-medium font-neulis">Tanggal</h1>
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false"
                            @close.stop="open = false">
                            <div @click="open = ! open">
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center p-3 pointer-events-none">
                                        <svg width="30px" height="30px" viewBox="0 0 24.00 24.00" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M20 10V7C20 5.89543 19.1046 5 18 5H6C4.89543 5 4 5.89543 4 7V10M20 10V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V10M20 10H4M8 3V7M16 3V7"
                                                stroke="#000000" stroke-width="1.344" stroke-linecap="round"></path>
                                            <rect x="6" y="12" width="3" height="3" rx="0.5"
                                                fill="#000000" />
                                            <rect x="10.5" y="12" width="3" height="3" rx="0.5"
                                                fill="#000000" />
                                            <rect x="15" y="12" width="3" height="3" rx="0.5"
                                                fill="#000000" />
                                        </svg>
                                    </div>
                                    <input
                                        class="rounded-xl block w-full pl-12 p-4 ps-10 text-gray-900 border border-gray-300 bg-gray-50 focus:ring-slate-900 focus:border-slate-900 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-slate-900 dark:focus:border-slate-900"
                                        placeholder="Pilih tanggal" x-model="selectedDateFormatted" readonly
                                        @click="window.dispatchEvent(new CustomEvent('open-bottom-sheet', { detail: { id: 'sheetTanggalB' } }))" />
                                </div>
                            </div>

                            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95" class="absolute z-50 mt-2 hidden sm:flex"
                                style="display: none;">
                                <div class="rounded-md ring-1 ring-black ring-opacity-5">

                                    <!-- Kalender -->
                                    <div
                                        class="p-4 bg-white dark:bg-gray-800 text-lg z-10 w-full lg:w-full border-2 rounded-xl border-slate-900 shadow-xl">
                                        <div class="border-b-2 border-gray-300 pb-4">
                                            <div class="flex items-center  text-lg">
                                                <!-- Hour Picker -->
                                                <div>
                                                    <input type="number" x-model="selectedHour" min="0"
                                                        max="23"
                                                        class="w-16 text-center bg-transparent border border-transparent focus:border-gray-400 focus:outline-none px-2 py-1 rounded text-2xl font-bold"
                                                        placeholder="HH">
                                                </div>

                                                <div class="font-bold">:</div>

                                                <!-- Input Menit -->
                                                <div>
                                                    <input type="number" x-model="selectedMinute" min="0"
                                                        max="59" step="1"
                                                        class="w-16 text-center bg-transparent border border-transparent focus:border-gray-400 focus:outline-none px-2 py-1 rounded text-2xl font-bold"
                                                        placeholder="MM">
                                                </div>
                                            </div>
                                            <div class="text-lg font-medium text-gray-700 dark:text-gray-200"
                                                x-text="monthNames[month] + ' ' + year"></div>
                                        </div>
                                        <!-- Header navigasi bulan -->
                                        <div class="flex mb-2">
                                            <button type="button" @click="prevMonth()"
                                                class="px-2 py-1 text-gray-600 hover:bg-gray-200 rounded">&lt;</button>

                                            <button type="button" @click="nextMonth()"
                                                class="px-2 py-1 text-gray-600 hover:bg-gray-200 rounded">&gt;</button>
                                        </div>

                                        <!-- Hari -->
                                        <div class="grid grid-cols-7 text-gray-500 mb-1">
                                            <template x-for="day in ['Min','Sen','Sel','Rab','Kam','Jum','Sab']">
                                                <div x-text="day" class="text-center"></div>
                                            </template>
                                        </div>

                                        <!-- Tanggal -->
                                        <div class="grid grid-cols-7 gap-2 font-semibold text-sm">
                                            <!-- Sisipkan hari kosong -->
                                            <template x-for="blank in blankdays">
                                                <div></div>
                                            </template>

                                            <!-- Tanggal -->
                                            <template x-for="(date, index) in daysInMonth" :key="index">
                                                <div @click="!isPastDate(date) && pickDate(date); $refs.dropdownButton?.click()"
                                                    x-text="date"
                                                    class="text-center cursor-pointer p-4 flex justify-center transition-colors duration-200 ease-in-out border-2 border-transparent"
                                                    :class="{
                                                        'bg-slate-900 text-white': isSelectedDate(date),
                                                        'text-gray-400 cursor-not-allowed opacity-50': isPastDate(date),
                                                        'hover:border-slate-900': !isPastDate(date)
                                                    }">
                                                </div>
                                            </template>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3 relative" x-data="{ openDuration: false }" @click.outside="openDuration = false">
                        <div class="flex justify-between items-center">
                            <h1 class="text-lg md:text-2xl font-medium font-neulis ">Durasi</h1>
                        </div>
                        <div x-show="openDuration" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute top-5 z-50 right-0 mt-2 hidden sm:flex" style="display: none;">
                            <div class="rounded-md ring-1 ring-black ring-opacity-5">

                                <div
                                    class="p-4 bg-white dark:bg-gray-800 text-lg z-10 w-full lg:w-[20vw] border-2 border-slate-900 shadow-xl rounded-xl">
                                    <div class="relative z-10">
                                        <label for="customDuration" class="block text-sm font-medium text-white/90 ">
                                            Masukkan Durasi Sendiri
                                        </label>

                                        <div class="flex items-center gap-3 w-full" x-data="{ unit: $wire.entangle('unit') }">
                                            <!-- Input jumlah -->
                                            <input id="customDuration" type="number" wire:model.live="jumlah"
                                                min="24"
                                                class="w-24 px-2 py-1.5 border-2 border-black rounded-xl"
                                                placeholder="Jumlah">

                                            <!-- Pilihan unit waktu -->
                                            <div
                                                class="flex overflow-hidden w-full justify-between border-2 border-black rounded-xl">
                                                <template x-for="opt in ['Hari','Minggu','Bulan']"
                                                    :key="opt">
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
                            @foreach ($iphone->durations as $item)
                                <div @click="setActiveTab({{ $item['hours'] }}, {{ $item->pivot->price }})"
                                    :class="{ 'bg-black dark:bg-orange-500 text-white': activeTab === {{ $item['hours'] }} }"
                                    class="p-2.5 cursor-pointer text-black w-full font-medium rounded-xl text-center border-2 border-slate-900">
                                    {{ $item['hours'] }} jam
                                </div>
                            @endforeach

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

                    </div>

                </div>
            </div>
            <div class="mt-5 space-y-2 hidden sm:flex flex-col" x-data="{ price: @entangle('selectedPrice').live }">
                <span class="text-xl sm:text-2xl font-medium font-neulis hidden md:flex"
                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(price)"></span>
                <button type="button" wire:click="bookingNow"
                    class="flex justify-between {{ $is_available ? '' : 'bg-gray-300' }} items-center space-x-4 bg-black text-white text-xl rounded-xl font-semibold group overflow-hidden cursor-pointer p-4 hover:bg-orange-500 border-2 transition duration-300 ease-in-out w-full hidden sm:flex">
                    <div class="text-start">
                        <h1 class="text-xl font-medium font-neulis">
                            {{ $is_available ? 'Booking Sekarang' : 'Tidak tersedia' }}
                        </h1>
                    </div>
                    <div class="w-fit h-full group-hover:translate-x-24 transition duration-200 ease-in-out">
                        <svg width="64" height="20" viewBox="0 0 64 16" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M63.7071 8.70711C64.0976 8.31658 64.0976 7.68342 63.7071 7.29289L57.3431 0.928932C56.9526 0.538408 56.3195 0.538408 55.9289 0.928932C55.5384 1.31946 55.5384 1.95262 55.9289 2.34315L61.5858 8L55.9289 13.6569C55.5384 14.0474 55.5384 14.6805 55.9289 15.0711C56.3195 15.4616 56.9526 15.4616 57.3431 15.0711L63.7071 8.70711ZM0 8V9H63V8V7H0V8Z"
                                fill="white" />
                        </svg>
                    </div>
                </button>
                <span
                    class="text-xs italic text-red-500">{{ !$is_available ? 'tidak tersedia untuk tanggal yang dipilih' : '' }}</span>
            </div>
            <div x-data="{ expanded: false }" class="flex flex-col md:hidden">
                <div class="border-b-2 border-gray-200 py-4 mt-3 text-lg md:text-2xl font-semibold flex justify-between items-center cursor-pointer"
                    @click="expanded = ! expanded">
                    <h1>Deskripsi</h1>
                    <div>
                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none"
                            class="ease-in duration-200" :class="expanded ? 'rotate-180' : ''"
                            xmlns="http://www.w3.org/2000/svg">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round">
                            </g>
                            <g id="SVGRepo_iconCarrier">
                                <path
                                    d="M15 11L12.2121 13.7879C12.095 13.905 11.905 13.905 11.7879 13.7879L9 11M7 21H17C19.2091 21 21 19.2091 21 17V7C21 4.79086 19.2091 3 17 3H7C4.79086 3 3 4.79086 3 7V17C3 19.2091 4.79086 21 7 21Z"
                                    stroke="rgb(31, 41, 55)" stroke-width="2" stroke-linecap="round">
                                </path>
                            </g>
                        </svg>
                    </div>
                </div>

                <div x-show="expanded" x-collapse class="mt-4" x-cloak>
                    <div
                        class="prose prose-base lg:prose-lg prose-invert text-black prose-li:text-black prose-a:text-blue-600 max-w-none md:flex flex-col">
                        {!! $iphone->description !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{--  --}}
    <span
        class="fixed sm:hidden flex bottom-16 text-sm italic text-red-500">{{ !$is_available ? 'tidak tersedia untuk tanggal yang dipilih' : '' }}</span>

    <button type="button" wire:click="bookingNow"
        class="flex justify-between {{ $is_available ? '' : 'bg-gray-300' }} items-center space-x-4 bg-black text-white text-xl font-semibold group overflow-hidden cursor-pointer p-4 hover:bg-gray-300 hover:text-black transition duration-700 ease-in-out w-full fixed bottom-0 sm:hidden hover:border-2 hover:border-black">
        <div class="text-start">
            <h1 class="text-xl font-bold">
                {{ $is_available ? 'Booking Sekarang' : 'Tidak tersedia' }}
            </h1>

        </div>
        <div class="w-fit h-full group-hover:translate-x-24 transition duration-200 ease-in-out">
            <svg width="64" height="20" viewBox="0 0 64 16" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M63.7071 8.70711C64.0976 8.31658 64.0976 7.68342 63.7071 7.29289L57.3431 0.928932C56.9526 0.538408 56.3195 0.538408 55.9289 0.928932C55.5384 1.31946 55.5384 1.95262 55.9289 2.34315L61.5858 8L55.9289 13.6569C55.5384 14.0474 55.5384 14.6805 55.9289 15.0711C56.3195 15.4616 56.9526 15.4616 57.3431 15.0711L63.7071 8.70711ZM0 8V9H63V8V7H0V8Z"
                    fill="white" />
            </svg>
        </div>
    </button>
    {{-- Reviews --}}
    <div class="xl:w-[65%] p-3 xl:p-0 xl:mt-0" x-data="{ reviewsOpen: false }">
        <div class="border-b-2 border-gray-200 py-4 text-lg md:text-2xl font-semibold flex justify-between items-center cursor-pointer"
            @click="reviewsOpen = ! reviewsOpen">
            <h1>Lihat Reviews</h1>
            <div>
                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" class="ease-in duration-200"
                    :class="reviewsOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round">
                    </g>
                    <g id="SVGRepo_iconCarrier">
                        <path
                            d="M15 11L12.2121 13.7879C12.095 13.905 11.905 13.905 11.7879 13.7879L9 11M7 21H17C19.2091 21 21 19.2091 21 17V7C21 4.79086 19.2091 3 17 3H7C4.79086 3 3 4.79086 3 7V17C3 19.2091 4.79086 21 7 21Z"
                            stroke="rgb(31, 41, 55)" stroke-width="2" stroke-linecap="round">
                        </path>
                    </g>
                </svg>
            </div>
        </div>
        <div class="space-y-5" x-show="reviewsOpen" x-collapse x-cloak class="">

            <div class="space-y-3 mt-4">
                <div class="flex justify-start items-center space-x-4">
                    <div class="w-[40px] sm:w-[60px] h-[40px] sm:h-[60px] rounded-full overflow-hidden">
                        <img src="https://placehold.co/600x400" alt="" srcset=""
                            class="object-cover w-full h-full">
                    </div>
                    <div class="space-y-2">
                        <div>
                            <h3 class="font-semibold text-lg sm:text-xl text-gray-600">{{ $name }}</h3>
                            <p class="text-sm text-gray-600 font-medium">Tulis review dengan nama anonimous</p>
                        </div>
                    </div>
                </div>
                <livewire:reviews :iphone_id="$selectedIphoneId" :rating="$rating" :name="$name" :avgRating="$avgRating"
                    :reviews="$reviews" />
            </div>
        </div>
    </div>
    <livewire:cards lazy="on-load" :title="'Mungkin anda tertarik'" />
    <x-modal name="user-booking-create" :show="$errors->isNotEmpty()" rounded="rounded-none"
        border="border-2 border-orange-500 dark:border-none">
        <form wire:submit="booking">
            @if ($errors->any())
                <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-[-10px]"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 translate-y-0"
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
            <div class="p-4 border" x-on:close-modal.window="show = false">
                @if ($step === 1)
                    <div>
                        <!-- Type Pengambilan -->
                        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl">
                            <div class="flex justify-between items-center">

                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        Tipe Pengambilan
                                    </span>
                                </div>

                                <!-- Toggle -->
                                <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                                    <button @click="type = 'pickup'"
                                        :class="type === 'pickup' ? 'bg-orange-500 text-white' : 'text-gray-600'"
                                        class="px-4 py-1 rounded-lg text-sm transition">
                                        Pickup
                                    </button>
                                    <button @click="type = 'delivery'"
                                        :class="type === 'delivery' ? 'bg-orange-500 text-white' : 'text-gray-600'"
                                        class="px-4 py-1 rounded-lg text-sm transition">
                                        Delivery
                                    </button>
                                </div>

                            </div>
                        </div>

                        <div class="space-y-2 md:space-y-3 bg-white dark:bg-slate-900 mt-1 sm:mt-7 p-4">
                            <div>
                                <h1
                                    class="sm:text-lg text-base font-medium font-neulis text-gray-900 dark:text-gray-100">
                                    Nama
                                </h1>
                                <input type="text" wire:model.live.debounce.250ms="customer_name"
                                    placeholder="e.g. John Doe"
                                    class="w-full p-2 rounded-xl border-2 bg-white text-gray-900 border-gray-300 focus:border-orange-500 focus:ring-0 dark:bg-slate-900 dark:text-gray-100 dark:border-gray-600 dark:focus:border-orange-400" />
                                @error('customer_name')
                                    <span class="mt-1 block text-sm text-red-500">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                            <div>
                                <h1
                                    class="sm:text-lg text-base font-medium font-neulis text-gray-900 dark:text-gray-100">
                                    Nomor Whatsapp
                                </h1>
                                <div x-data="{
                                    countryCode: @entangle('countryCode'),
                                    customerPhone: @entangle('customer_phone').live,
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
                                        class="w-[40%] p-2 rounded-xl border-2 bg-white text-gray-900 border-gray-300 focus:border-orange-500 focus:ring-0 dark:bg-slate-900 dark:text-gray-100 dark:border-gray-600 dark:focus:border-orange-400">
                                        <template x-for="country in countries" :key="country.code">
                                            <option :value="country.code" x-text="country.flag + ' ' + country.code">
                                            </option>
                                        </template>
                                    </select>
                                    <!-- Input Nomor -->
                                    <input type="text" id="customer_phone" x-model="customerPhone"
                                        @input=" let raw = $event.target.value.replace(/[^0-9]/g, ''); customerPhone = raw.match(/.{1,4}/g)?.join('-') || ''; "
                                        placeholder="8123-4567-8901"
                                        class="w-full p-2 rounded-xl border-2 bg-white text-gray-900 border-gray-300 focus:border-orange-500 focus:ring-0 dark:bg-slate-900 dark:text-gray-100 dark:border-gray-600 dark:focus:border-orange-400" />
                                </div>
                                @error('customer_phone')
                                    <span class="mt-1 block text-sm text-red-500">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                            <div>
                                <h1
                                    class="sm:text-lg text-base font-medium font-neulis text-gray-900 dark:text-gray-100">
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
                            <div x-show="type === 'delivery'" x-transition x-init="$watch('type', value => {
                                if (value === 'delivery') {
                                    setTimeout(() => initMap(), 200)
                                }
                            })">
                                <h1 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    Pilih Lokasi Pengantaran
                                </h1>

                                <div wire:ignore>
                                    <div id="map" class="w-full h-64 rounded-xl border"></div>
                                </div>

                                <input type="hidden" wire:model="lat">
                                <input type="hidden" wire:model="long">
                            </div>
                            <div>
                                <h1
                                    class="sm:text-lg text-base font-medium font-neulis text-gray-900 dark:text-gray-100">
                                    Email
                                </h1>
                                <input type="email" wire:model="customer_email"
                                    class="w-full p-2 rounded-xl border-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-700 placeholder-gray-400 dark:placeholder-gray-500 active:border-orange-500 focus:border-orange-500 focus:outline-none"placeholder="jhondoe@mail.com">
                            </div>
                            @error('customer_email')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                            <div class="mt-4">
                                <h1
                                    class="sm:text-lg text-base font-medium font-neulis text-gray-900 dark:text-gray-100">
                                    Tipe Jaminan
                                </h1>
                                <select wire:model="jaminan_type"
                                    class="w-full p-2 rounded-xl border-2  bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-700 active:border-orange-500 focus:border-orange-500 focus:outline-none">
                                    <option value="KTP">KTP</option>
                                    <option value="KK">KK</option>
                                    <option value="SIM">SIM</option>
                                    <option value="Kartu Identitas Mahasiswa">Kartu Mahasiswa</option>
                                    <option value="Kartu Pelajar">Kartu Pelajar</option>
                                    <option value="Kartu Identitas Anak">Kartu Identitas Anak</option>
                                </select>

                                @error('jaminan_type')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        {{-- Invoice --}}
                        <div class="sm:mt-7 mt-3 space-y-5 bg-white dark:bg-slate-900 p-4">
                            <div>
                                <h1 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">
                                    Metode Pembayaran
                                </h1>

                                <div class="space-y-2">
                                    @if (!empty($payments))
                                        <div class="w-full">
                                            <div class="space-y-2">
                                                @foreach ($payments as $payment)
                                                    <label
                                                        wire:click="$set('selectedPaymentId', {{ $payment['id'] }})"
                                                        class="flex items-center justify-between rounded-xl p-3 cursor-pointer transition bg-white dark:bg-gray-900 shadow-sm dark:shadow-none border{{ $selectedPaymentId == $payment['id'] ? 'border-orange-500 ring-1 ring-orange-500/30' : 'border-gray-200dark:border-gray-700' }} hover:border-black dark:hover:border-gray-500">
                                                        <!-- Nama Payment -->
                                                        <div class="flex items-center gap-3">
                                                            <span
                                                                class="font-semibold text-gray-800 dark:text-gray-100">
                                                                {{ $payment['name'] }}
                                                            </span>
                                                        </div>
                                                        <!-- Radio -->
                                                        <div
                                                            class="relative w-5 h-5 rounded-full border{{ $selectedPaymentId == $payment['id'] ? 'border-orange-500' : 'border-gray-300 dark:border-gray-600' }}">
                                                            @if ($selectedPaymentId == $payment['id'])
                                                                <div
                                                                    class="absolute inset-1 bg-orange-500 rounded-full">
                                                                </div>
                                                            @endif
                                                        </div>

                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Details booking --}}
                @elseif($step === 2)
                    <div x-data="{ detailss: true }" class="bg-white dark:bg-slate-900 p-4">
                        <div class="border-b-2 border-gray-200 py-4 text-lg md:text-2xl font-semibold flex justify-between items-center cursor-pointer"
                            @click="detailss = ! detailss">
                            <h1 class="text-xl font-medium font-neulis">Detail booking</h1>
                            <div>
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none"
                                    class="ease-in duration-200" :class="detailss ? 'rotate-180' : ''"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round">
                                    </g>
                                    <g id="SVGRepo_iconCarrier">
                                        <path
                                            d="M15 11L12.2121 13.7879C12.095 13.905 11.905 13.905 11.7879 13.7879L9 11M7 21H17C19.2091 21 21 19.2091 21 17V7C21 4.79086 19.2091 3 17 3H7C4.79086 3 3 4.79086 3 7V17C3 19.2091 4.79086 21 7 21Z"
                                            stroke="rgb(31, 41, 55)" stroke-width="2" stroke-linecap="round">
                                        </path>
                                    </g>
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-5" x-show="detailss" x-collapse x-cloak class="">
                            <div class="border-y-2 border-gray-400 py-6 my-py-6 space-y-1">

                                <div class="flex justify-between items-center font-medium">
                                    <p class="font-neulis">Nama</p>
                                    <p class="font-normal">{{ $customer_name ?? '-' }}</p>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <p class="font-neulis">Nomor Telepon</p>
                                    <p class="font-normal">
                                        {{ $customer_phone ? $countryCode . ' ' . $customer_phone : '-' }}</p>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <p class="font-neulis">Alamat Sesuai Jaminan</p>
                                    <p class="font-normal">{{ $address ?? '-' }}</p>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <p class="font-neulis">Jaminan</p>
                                    <p class="font-normal">{{ $jaminan_type ?? '-' }}</p>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <p class="font-neulis">Tipe iPhone</p>
                                    <p class="font-normal">{{ $iphone->name }}</p>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <p class="font-neulis">Durasi</p>
                                    <p class="font-normal">
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
                                    </p>
                                </div>
                                <div class="flex justify-between items-center font-medium">
                                    <p class="font-neulis">Tanggal booking</p>
                                    <p class="font-normail">{{ $selectedDateFormatted }}</p>
                                </div>
                                <div class="flex justify-between items-center font-medium font-neulis"
                                    x-data="{ price: @entangle('selectedPrice').live }">
                                    <p>Harga</p>
                                    <span class=" font-medium"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(price)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-2">
                        <div class="space-y-2">
                            <label class="flex gap-3 cursor-pointer items-center">
                                <!-- Checkbox -->
                                <input type="checkbox" wire:model="terms_condition"
                                    class="mt-1 h-5 w-5 rounded border-gray-300
                   text-orange-500 focus:ring-orange-500
                   dark:border-gray-600 dark:bg-gray-900
                   dark:checked:bg-orange-500" />

                                <div>
                                    <!-- Label -->
                                    <span class="text-sm text-gray-800 dark:text-gray-200">
                                        Saya setuju dengan
                                        <a href="{{ route('terms') }}" target="_blank" wire:navigate
                                            class="text-blue-600 underline hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            syarat & ketentuan sewa iPhone
                                        </a>
                                    </span>
                                    <!-- Hint -->
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Persetujuan ini wajib untuk melanjutkan proses penyewaan
                                    </p>
                                </div>
                            </label>


                            <!-- Error -->
                            @error('terms_condition')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                @endif
            </div>
            @if ($step === 1)
                <button type="button" wire:click="next"
                    class="p-3 font-semibold text-white bg-black w-full rounded-xl disabled:bg-gray-400 hover:bg-orange-500 ease-in duration-100"
                    wire:loading.attr="disabled">
                    Lanjut
                </button>
            @else
                <button type="submit"
                    class="p-3 font-semibold text-white bg-black w-full rounded-xl disabled:bg-gray-400  hover:bg-orange-500 ease-in duration-100"
                    wire:loading.attr="disabled">
                    Lanjutkan Pembayaran
                </button>
            @endif
        </form>
    </x-modal>
    <x-bottom-sheet id="sheetTanggalB" title="Form Booking" glass="">
        {{-- glass effect --}}
        <div
            class="relative bg-white/20 dark:bg-gray-800/30 
            backdrop-blur-xl border border-white/20 
            rounded-2xl shadow-xl text-lg z-10 w-full p-4">

            <div class="border-b border-white/20 dark:border-gray-700/40 pb-4">
                <div class="flex items-center text-lg">
                    <!-- Hour Picker -->
                    <div>
                        <input type="number" x-model="selectedHour" min="0" max="23"
                            class="w-16 text-center bg-transparent border border-transparent 
                           focus:border-white/40 dark:focus:border-gray-500 
                           focus:outline-none px-2 py-1 rounded text-2xl font-bold text-white"
                            placeholder="HH">
                    </div>

                    <div class="font-bold text-white">:</div>

                    <!-- Input Menit -->
                    <div>
                        <input type="number" x-model="selectedMinute" min="0" max="59" step="1"
                            class="w-16 text-center bg-transparent border border-transparent 
                           focus:border-white/40 dark:focus:border-gray-500 
                           focus:outline-none px-2 py-1 rounded text-2xl font-bold text-white"
                            placeholder="MM">
                    </div>
                </div>
                <div class="text-lg font-medium text-white/90 dark:text-gray-200"
                    x-text="monthNames[month] + ' ' + year"></div>
            </div>

            <!-- Header navigasi bulan -->
            <div class="flex mb-2 mt-2">
                <button type="button" @click="prevMonth()"
                    class="px-2 py-1 text-white/80 hover:bg-white/10 rounded">&lt;</button>
                <button type="button" @click="nextMonth()"
                    class="px-2 py-1 text-white/80 hover:bg-white/10 rounded">&gt;</button>
            </div>

            <!-- Hari -->
            <div class="grid grid-cols-7 text-white/70 mb-1">
                <template x-for="day in ['Min','Sen','Sel','Rab','Kam','Jum','Sab']">
                    <div x-text="day" class="text-center"></div>
                </template>
            </div>

            <!-- Tanggal -->
            <div class="grid grid-cols-7 gap-2 font-semibold text-sm">
                <!-- Sisipkan hari kosong -->
                <template x-for="blank in blankdays">
                    <div></div>
                </template>

                <!-- Tanggal -->
                <template x-for="(date, index) in daysInMonth" :key="index">
                    <div @click="!isPastDate(date) && pickDate(date); $refs.dropdownButton?.click()" x-text="date"
                        class="text-center cursor-pointer p-4 flex justify-center 
                       transition-colors duration-200 ease-in-out 
                       border border-transparent rounded-lg"
                        :class="{
                            'bg-white/20 text-white backdrop-blur-sm border-white/40': isSelectedDate(date),
                            'text-white/40 cursor-not-allowed opacity-50': isPastDate(date),
                            'hover:border-white/40 hover:bg-white/10': !isPastDate(date)
                        }">
                    </div>
                </template>
            </div>
        </div>

    </x-bottom-sheet>
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
    let map;
    let marker;

    async function detectCurrentLocation() {

        if (!navigator.geolocation) {

            alert('Browser tidak mendukung geolocation');
            return;
        }

        navigator.geolocation.getCurrentPosition(

            async (position) => {

                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    map.setView([lat, lng], 16);

                    if (marker) {
                        map.removeLayer(marker);
                    }

                    marker = L.marker([lat, lng]).addTo(map);

                    @this.set('lat', lat);
                    @this.set('long', lng);

                    // reverse geocoding
                    try {

                        const res = await fetch(
                            `/geo/reverse?lat=${lat}&lon=${lng}`
                        );

                        const data = await res.json();

                        if (data.display_name) {

                            @this.set('address', data.display_name);

                            // update input Alpine
                            const addressInput = document.querySelector(
                                '[x-model="address"]'
                            );

                            if (addressInput) {
                                addressInput.value = data.display_name;
                            }
                        }

                    } catch (e) {
                        console.error(e);
                    }
                },

                (error) => {

                    console.error(error);

                    alert('Gagal mendapatkan lokasi');
                },

                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
        );
    }

    async function initMap() {
        if (map) return; // biar tidak double init

        map = L.map('map').setView([-8.219, 114.369], 13);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        map.on('click', async function(e) {

            if (marker) {
                map.removeLayer(marker);
            }

            marker = L.marker(e.latlng).addTo(map);

            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            window.bookingMap.lat = lat;
            window.bookingMap.lng = lng;

            // START LOADING
            window.bookingMap.addressLoading = true;

            try {

                const res = await fetch(
                    `/geo/reverse?lat=${lat}&lon=${lng}`
                );

                const data = await res.json();

                if (data.display_name) {

                    window.bookingMap.address = data.display_name;

                    @this.set('address', data.display_name);
                    @this.set('lat', lat);
                    @this.set('long', lng);
                }

            } catch (e) {

                console.error(e);

            } finally {

                // STOP LOADING
                window.bookingMap.addressLoading = false;
            }
        });

        // 🔥 FIX penting biar map tidak blank
        setTimeout(() => {
            map.invalidateSize();

            if (navigator.geolocation) {
                detectCurrentLocation();
            }
        }, 300);
    }
</script>
