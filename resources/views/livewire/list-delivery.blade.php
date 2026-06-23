<div x-data="{
    generateRoute() {
        $wire.generateRoute()
    }
}">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <x-mary-stat title="Total Delivery" :value="$totalDelivery . ' Booking'" icon="o-truck" color="text-blue-600" />

        <x-mary-stat title="Delivery Hari Ini" :value="$todayDelivery . ' Booking'" icon="o-calendar" color="text-indigo-600" />

        <x-mary-stat title="Dalam Pengiriman" :value="$onDeliveryCount . ' Booking'" icon="o-arrow-path" color="text-orange-600" />

        <x-mary-stat title="Sudah Dikirim Hari Ini" :value="$deliveredTodayCount . ' Booking'" icon="o-check-circle" color="text-green-600" />

    </div>

    <x-tables.table name="Delivery Booking">

        {{-- tombol tambahan --}}
        <x-slot name="secondBtn">

            <div class="flex gap-2">

                <select wire:model.live="autoSelectPeriod" class="border rounded-lg px-3 py-2">

                    <option value="">Pilih Sesi Pengiriman</option>
                    <option value="morning">Pengiriman Pagi (06:00 - 11:00)</option>
                    <option value="afternoon">Pengiriman Siang (12:00 - 17:00)</option>
                    <option value="evening">Pengiriman Sore (17:30 - 22:00)</option>

                </select>

                <button wire:click="autoSelectByTime"
                    class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg">

                    Auto Pilih

                </button>

            </div>

        </x-slot>

        <x-slot name="addBtn">
            <button @click="generateRoute()" @disabled(count($selectedBookings) == 0)
                class="px-4 py-2 bg-blue-600 text-white rounded-lg disabled:opacity-50">

                Generate Route

            </button>
        </x-slot>

        <x-slot name="search">
            <x-search wire:model.live.debounce.500ms="search" />
        </x-slot>

        <x-slot name="thead">
            <x-tables.th>
                <input type="checkbox" wire:model.live="selectedAll">
            </x-tables.th>
            <x-tables.th>Booking Code</x-tables.th>
            <x-tables.th>Customer</x-tables.th>
            <x-tables.th>Alamat Delivery</x-tables.th>
            <x-tables.th>Jam Booking</x-tables.th>
            <x-tables.th>Status</x-tables.th>
            <x-tables.th>Aksi</x-tables.th>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($deliveryBookings as $booking)
                <tr>
                    <x-tables.td>
                        <input type="checkbox" wire:model.live="selectedBookings" value="{{ $booking->id }}">
                    </x-tables.td>

                    <x-tables.td>
                        {{ $booking->booking_code }}
                    </x-tables.td>

                    <x-tables.td>
                        {{ $booking->customer_name }}
                    </x-tables.td>

                    <x-tables.td>
                        <div class="max-w-xs truncate">
                            {{ $booking->address }}
                        </div>
                    </x-tables.td>

                    <x-tables.td>
                        {{ $booking->start_time }}
                    </x-tables.td>

                    <x-tables.td>

                        <div x-data="{ open: false }" class="relative">

                            <button @click="open=!open"
                                class="px-2 py-1 rounded text-white text-sm

            @if ($booking->delivery_status == 'assigned') bg-blue-500
            @elseif($booking->delivery_status == 'on_delivery')
                bg-orange-500
            @elseif($booking->delivery_status == 'delivered')
                bg-green-500
            @else
                bg-gray-500 @endif
            ">

                                {{ str_replace('_', ' ', ucfirst($booking->delivery_status)) }}

                            </button>

                            <div x-show="open" @click.outside="open=false"
                                class="absolute z-50 mt-1 bg-white border rounded shadow w-40">

                                <button wire:click="changeStatus({{ $booking->id }}, 'assigned')"
                                    class="block w-full text-left px-3 py-2 hover:bg-gray-100">
                                    Assigned
                                </button>

                                <button wire:click="changeStatus({{ $booking->id }}, 'on_delivery')"
                                    class="block w-full text-left px-3 py-2 hover:bg-gray-100">
                                    On Delivery
                                </button>

                                <button wire:click="changeStatus({{ $booking->id }}, 'delivered')"
                                    class="block w-full text-left px-3 py-2 hover:bg-gray-100">
                                    Delivered
                                </button>

                            </div>

                        </div>

                    </x-tables.td>

                    <x-tables.td class="flex gap-2">

                        <x-primary-button wire:click="showRoute({{ $booking->id }})">
                            Route
                        </x-primary-button>

                        @if ($booking->delivery_status === 'on_delivery')
                            <x-primary-button wire:click="markDelivered({{ $booking->id }})">
                                Delivered
                            </x-primary-button>
                        @endif

                    </x-tables.td>
                </tr>
            @endforeach
        </x-slot>

    </x-tables.table>
</div>
