<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Iphones;
use App\Models\Payment;
use App\Models\Revenue;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Http;

class RentIphoneWizard extends Component
{
    public int $step = 1;
    public $countryCode = '+62';
    public $selectedIphone = null;
    public $price = 0;
    public $iphone_search = '';
    public $unit = '';
    public $is_available = false;
    public ?int $jumlah = null;
    public $totalHarga = 0;
    public int $basePricePerHour = 5000;

    // STEP 1
    public $iphones;
    public ?int $selectedIphoneId = null;
    public $requested_booking_date;
    public $requested_time;
    public $selectedDuration;
    public $selectedPrice;
    public $durations = [];
    public $end_booking_date;
    public $end_time;


    // STEP 2
    public $customer_name;
    public $customer_phone;
    public $customer_email;
    public $address;
    public $jaminan_type = 'KTP';
    public $payments;
    public $selectedPaymentId = 1;
    public $selectedPayment;
    public $lat;
    public $long;
    public $pickup_type;

    // STEP 3
    public $sendWhatsapp = true;
    public $iphone_name;
    public $serial_number;

    #[On('iphone-selected')]
    public function setIphone(int $iphoneId)
    {
        $this->selectedIphoneId = $iphoneId;
        $this->getDurations();
        $this->dispatch('display-duration-options');
    }

    protected function rules(): array
    {
        return match ($this->step) {
            1 => [
                'selectedIphoneId' => 'required|exists:iphones,id',
                'requested_booking_date' => 'required|date',
                'requested_time' => 'required|date_format:H:i',
                'selectedDuration' => 'required|integer|min:1',
                'selectedPrice' => 'required|numeric|min:0',
            ],
            2 => [
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:15',
                'customer_email' => 'nullable|email|max:255',
                'address' => 'required|string|max:255',
                'jaminan_type' => 'required|in:KTP,KK,Kartu Pelajar,SIM,Kartu Identitas Mahasiswa, Kartu Identitas Anak',

            ],
            3 => [
                'customerName' => 'required|min:3',
                'customerPhone' => 'required',
            ],
            default => [],
        };
    }

    public function next(): void
    {
        $this->validate();

        if ($this->step === 1) {
            $this->handleStepOne();
        }

        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function handleStepOne()
    {
        $this->calculateEndDateTime();

        if (!$this->end_booking_date || !$this->end_time) {
            LivewireAlert::title('Gagal Hitung Waktu')
                ->text('Gagal menghitung waktu selesai booking.')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
            return;
        }

        // Gabungkan tanggal dan waktu dari booking sekarang
        $start = Carbon::createFromFormat('Y-m-d H:i', Carbon::parse($this->requested_booking_date)->format('Y-m-d') . ' ' . $this->requested_time);
        $end = Carbon::createFromFormat('Y-m-d H:i', Carbon::parse($this->end_booking_date)->format('Y-m-d') . ' ' . $this->end_time);

        // 🔍 Cek apakah ada booking bentrok
        $bookings = Booking::where('iphone_id', $this->selectedIphoneId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        $conflict = $bookings->contains(function ($booking) use ($start, $end) {
            $bookingStart = Carbon::parse($booking->requested_booking_date . ' ' . $booking->requested_time);

            // Hitung bookingEnd berdasarkan requested + durasi
            $bookingEnd = $bookingStart->copy()->addHours((int) $booking->duration);

            // Cek apakah waktu booking lama bertabrakan dengan booking baru
            return $bookingStart < $end && $bookingEnd > $start;
        });

        if ($conflict) {
            LivewireAlert::title('Booking Gagal')
                ->text('Tanggal dan waktu yang dipilih sudah dibooking.')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
            return;
        }
    }

    public function calculateEndDateTime()
    {

        if (!$this->requested_booking_date || !$this->requested_time || !$this->selectedDuration) {
            $this->end_booking_date = null;
            $this->end_time = null;
            return;
        }

        try {
            $dateOnly = Carbon::parse($this->requested_booking_date)->toDateString(); // pastikan hanya tanggal
            $startDateTime = Carbon::parse("{$dateOnly} {$this->requested_time}");

            $endDateTime = $startDateTime->copy()->addHours($this->selectedDuration);

            $this->end_booking_date = $endDateTime->toDateString();
            $this->end_time = $endDateTime->format('H:i');
        } catch (\Exception $e) {
            logger()->error('DateTime parse error: ' . $e->getMessage());
            $this->end_booking_date = null;
            $this->end_time = null;
        }
    }

    public function getDurations()
    {
        if ($this->selectedIphoneId) {
            $iphone = Iphones::with('durations')->find($this->selectedIphoneId);
            $this->durations = $iphone->durations->map(function ($duration, $index) {
                return [
                    'index' => $index,
                    'hours' => $duration->hours,           // dari tabel durations
                    'price' => (int) $duration->pivot->price, // dari pivot table
                ];
            })->toArray();
        }
    }

    public function submit(): void
    {
        if ($this->jumlah > 1) {
            $this->selectedDuration = $this->selectedDuration * 24;
        }

        $this->validate([
            'selectedIphoneId' => 'required|exists:iphones,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:15',
            'customer_email' => 'nullable|email|max:255',
            'requested_booking_date' => 'required|date',
            'requested_time' => 'required|date_format:H:i',
            'selectedDuration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'pickup_type' => 'required|in:pickup,delivery',
            'address' => $this->pickup_type === 'delivery'
                ? 'required|string|min:10|max:255'
                : 'required|string|min:10|max:255',
            'lat' => $this->pickup_type === 'delivery'
                ? 'required'
                : 'nullabel',
            'long' => $this->pickup_type === 'delivery'
                ? 'required'
                : 'nullabel',
        ]);

        $booking = Booking::create([
            'iphone_id' => $this->selectedIphoneId,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->countryCode . '-' . $this->customer_phone,
            'customer_email' => $this->customer_email,
            'requested_booking_date' => $this->requested_booking_date,
            'requested_time' => $this->requested_time,
            // 'end_booking_date' => $end->toDateString(),
            // 'end_time' => $end->format('H:i'),
            'duration' => $this->selectedDuration,
            'price' => $this->selectedPrice,
            'status' => 'pending',
            'created' => Carbon::now('Asia/Jakarta'),
            'booking_code' => Booking::generateBookingCode(),
            'payment_id' => $this->selectedPayment ? $this->selectedPayment->id : null,
            'address' => $this->address,
            'lat' => $this->lat,
            'long' => $this->long,
            'pickup_type' => $this->pickup_type,
            'jaminan_type' => $this->jaminan_type,
        ]);

        // debug off
        $message = "Halo {$booking->customer_name},\n\n"
            . "Terima kasih telah melakukan booking di *SkyRental*.\n\n"
            . "Berikut adalah detail booking Anda:\n"
            . "--------------------------------------\n"
            . "Kode Booking : *{$booking->booking_code}*\n"
            . "Perangkat    : {$booking->iphone->name} {$booking->iphone->serial_number}\n"
            . "Tanggal      : {$booking->requested_booking_date}\n"
            . "Waktu        : {$booking->requested_time}\n"
            . "Durasi       : {$booking->duration} jam\n"
            . "Total Biaya  : Rp" . number_format($booking->price, 0, ',', '.') . "\n"
            . "--------------------------------------\n\n"
            . "Mohon segera melakukan pembayaran *maksimal 30 menit* setelah pesan ini diterima.\n"
            . "Apabila pembayaran belum kami terima hingga batas waktu tersebut, "
            . "maka booking akan *dibatalkan secara otomatis*.\n\n"
            . "Setelah melakukan pembayaran, silakan lakukan konfirmasi dengan membalas pesan ini "
            . "atau mengirim bukti pembayaran melalui WhatsApp ini.\n\n"
            . "Untuk memeriksa status booking, silakan kunjungi:\n"
            . url('/booking-status') . "\n\n"
            . "Terima kasih atas kerja samanya.\n"
            . "SkyRental";

        $adminMessage = "<b>Booking Baru Diterima</b>\n\n"
            . "<b>Nama</b> : {$booking->customer_name}\n"
            . "<b>HP</b>   : {$booking->customer_phone}\n"
            . "<b>Email</b>: {$booking->customer_email}\n\n"
            . "<b>Kode Booking</b>: {$booking->booking_code}\n"
            . "<b>Perangkat</b>   : {$booking->iphone->name}\n"
            . "<b>Tanggal</b>     : {$booking->requested_booking_date}\n"
            . "<b>Waktu</b>       : {$booking->requested_time}\n"
            . "<b>Durasi</b>      : {$booking->duration} jam\n"
            . "<b>Total Biaya</b>: Rp" . number_format($booking->price, 0, ',', '.') . "\n\n"
            . "🔗 <a href='" . url('/admin/bookings/' . $booking->id) . "'>Lihat detail di Admin Panel</a>";

        $groupMessage = "BOOKING BARU MASUK\n\n"
            . "Perangkat    : {$booking->iphone->name} {$booking->iphone->serial_number}\n"
            . "Nama         : {$booking->customer_name}\n"
            . "No. HP       : {$booking->customer_phone}\n"
            . "Alamat       : {$booking->address}\n"
            . "Jaminan       : {$booking->jaminan_type}\n"
            . "Email        : {$booking->customer_email}\n\n"
            . "Kode Booking : {$booking->booking_code}\n"
            . "Tanggal      : {$booking->requested_booking_date}\n"
            . "Waktu        : {$booking->requested_time}\n"
            . "Durasi       : {$booking->duration} jam\n"
            . "Total Biaya  : Rp" . number_format($booking->price, 0, ',', '.') . "\n\n"
            . "Status       : {$booking->status} \n"
            . "Admin Panel:\n"
            . url('/admin/bookings/');

        $token = env('TELEGRAM_BOT_TOKEN'); // simpan token di .env
        $chatId = env('TELEGRAM_CHAT_ID'); // chat id kamu

        Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN'),
        ])->post('https://api.fonnte.com/send', [
            'target'  => env('FONNTE_GROUP_ID'),
            'message' => $groupMessage,
        ]);

        if ($this->sendWhatsapp) {
            Http::withHeaders([
                'Authorization' => env('FONNTE_TOKEN'),
            ])->post('https://api.fonnte.com/send', [
                'target' => $this->formatPhoneNumber($booking->customer_phone), // hapus tanda "-" biar format sesuai
                'message' => $message,
            ]);

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $adminMessage,
                'parse_mode' => 'HTML',
            ]);
        }


        // Reset
        $this->dispatch('close-modal');
        $this->reset([
            'selectedDuration',
            'selectedIphone',
            'selectedIphoneId',
            'customer_name',
            'customer_phone',
            'customer_email',
            'requested_booking_date',
            'end_booking_date',
            'requested_time',
            'end_time',
            'price'
        ]);
        $this->step = 1;
        $this->requested_booking_date = Carbon::now('Asia/Jakarta');
        $this->requested_time = Carbon::now('Asia/Jakarta')->format('H:i');
        LivewireAlert::title('Success!')
            ->text('Booking berhasil disimpan.')
            ->success()
            ->toast()
            ->position('top-end')
            ->show();
    }

    public function selectIphone(int $iphoneId, string $name, $serial_number)
    {
        $this->selectedIphoneId = $iphoneId;
        $this->iphone_name = $name;
        $this->serial_number = $serial_number;
        $this->getDurations();
        $this->dispatch('display-duration-options');
    }

    public function updatedSelectedPaymentId()
    {
        $this->selectedPayment = $this->payments->firstWhere('id', $this->selectedPaymentId);
        if (!$this->selectedPayment) {
            $this->selectedPaymentId = optional($this->payments->first())->id ?? null;
            $this->selectedPayment = $this->payments->first();
        }
    }

    public function mount()
    {

        $this->requested_booking_date = Carbon::now('Asia/Jakarta');
        $this->payments = Payment::orderBy('created_at', 'desc')->get();
        $this->selectedPayment = $this->payments->firstWhere('id', $this->selectedPaymentId);

        if (!$this->selectedPayment) {
            $this->selectedPaymentId = optional($this->payments->first())->id ?? null;
            $this->selectedPayment = $this->payments->first();
        }
        $now = Carbon::now('Asia/Jakarta');

        $this->requested_booking_date = $now->toDateString(); // Y-m-d
        $this->requested_time = $now->format('H:i');
    }

    function formatPhoneNumber($phone)
    {
        // hapus semua karakter non-digit
        $digits = preg_replace('/\D/', '', $phone);

        // kalau nomor sudah diawali 62 -> biarkan
        if (substr($digits, 0, 2) === '62') {
            return $digits;
        }

        // kalau diawali 0 -> ubah ke 62
        if (substr($digits, 0, 1) === '0') {
            return '62' . substr($digits, 1);
        }

        return $digits;
    }

    public function loadIphones()
    {
        $now = Carbon::now('Asia/Jakarta');

        $this->iphones = Iphones::query()
            ->with(['bookings' => function ($query) {
                $query->whereIn('status', ['pending', 'confirmed']);
            }])
            ->when($this->iphone_search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->iphone_search . '%')
                        ->orWhere('serial_number', 'like', '%' . $this->iphone_search . '%');
                });
            })
            ->get()
            ->map(function ($iphone) use ($now) {

                $iphone->is_available = true;

                foreach ($iphone->bookings as $booking) {

                    $bookingStart = Carbon::parse(
                        "{$booking->requested_booking_date} {$booking->requested_time}",
                        'Asia/Jakarta'
                    );

                    $bookingEnd = $bookingStart->copy()
                        ->addHours((int) $booking->duration);

                    // booking sedang aktif sekarang
                    if ($bookingStart->lte($now) && $bookingEnd->gt($now)) {
                        $iphone->is_available = false;
                        break;
                    }
                }

                return $iphone;
            });
    }

    // #[On('booking-time-set')]
    // public function receiveBookingTime(string $date, string $time): void
    // {
    //     $this->requested_booking_date = $date;
    //     $this->requested_time = $time;
    // }

    public function setCustom($unit)
    {
        // dd($unit);
        $this->unit = $unit;
        $this->basePricePerHour = Iphones::find($this->selectedIphoneId)
            ?->durations()
            ->where('hours', 24)
            ->first()
            ?->pivot
            ?->price ?? 5000;
        $this->updateDurationAndPrice();
    }

    public function updatedJumlah()
    {
        $this->updateDurationAndPrice();
    }

    private function updateDurationAndPrice()
    {

        if (empty($this->unit) || empty($this->jumlah)) {
            $this->selectedDuration = 0;
            $this->selectedPrice = 0;
            return;
        }

        if ($this->unit === 'Jam' && $this->jumlah < 24) {
            $this->jumlah = 24;
        }


        if ($this->jumlah < 1) {
            $this->selectedDuration = 0;
            $this->selectedPrice = 0;
            return;
        }


        if ($this->basePricePerHour === 5000) {
            # code...
            switch ($this->unit) {

                case 'Hari':
                    $this->selectedDuration = $this->jumlah * 24;
                    break;
                case 'Minggu':
                    $this->selectedDuration = $this->jumlah * 24 * 7;
                    break;
                case 'Bulan':
                    $this->selectedDuration = $this->jumlah * 24 * 30; // asumsi 30 hari
                    break;
                default:
                    $this->selectedDuration = $this->jumlah;
                    break;
            }
        } else {
            switch ($this->unit) {

                case 'Hari':
                    $this->selectedDuration = $this->jumlah;
                    break;
                case 'Minggu':
                    $this->selectedDuration = $this->jumlah * 7;
                    break;
                case 'Bulan':
                    $this->selectedDuration = $this->jumlah * 30; // asumsi 30 hari
                    break;
                default:
                    $this->selectedDuration = $this->jumlah;
                    break;
            }
        }

        // hitung harga (contoh sederhana per jam)
        $this->selectedPrice = $this->selectedDuration * $this->basePricePerHour;
    }

    public function render()
    {
        return view('livewire.rent-iphone-wizard', [
            'iphones' => $this->loadIphones()
        ]);
    }
}
