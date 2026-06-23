<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'iphone_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'requested_booking_date',
        'requested_time',
        'start_booking_date',
        'start_time',
        'end_booking_date',
        'end_time',
        'duration',
        'status',
        'price',
        'created',
        'booking_code',
        'payment_id',
        'reminder_sent',
        'address',
        'pickup_type',
        'jaminan_type',
        'lat',
        'long',
        'delivery_status'
    ];

    public static function generateBookingCode()
    {
        do {
            // Format: SKY + TahunBulanTanggal + Random 4 digit
            $code = 'SKY' . now()->format('ymd') . Str::upper(Str::random(4));
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }

    public function revenue()
    {
        return $this->hasOne(Revenue::class);
    }


    public function iphone()
    {
        return $this->belongsTo(Iphones::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnIphone::class);
    }

    public function latestReturn()
    {
        return $this->hasOne(ReturnIphone::class, 'booking_id')->latestOfMany();
    }

    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function extendHours(int $hours): void
    {
        $start = Carbon::parse(
            "{$this->start_booking_date} {$this->start_time}",
            'Asia/Jakarta'
        );

        $end = Carbon::parse(
            "{$this->end_booking_date} {$this->end_time}",
            'Asia/Jakarta'
        );

        // Tambah jam
        $newEnd = $end->copy()->addHours($hours);

        $this->duration += $hours;
        $this->end_booking_date = $newEnd->toDateString();
        $this->end_time = $newEnd->format('H:i');

        $this->saveQuietly();
    }

    public function canExtend(int $hours): bool
    {
        $currentEnd = Carbon::parse(
            "{$this->end_booking_date} {$this->end_time}",
            'Asia/Jakarta'
        );

        $newEnd = $currentEnd->copy()->addHours($hours);

        return !Booking::where('iphone_id', $this->iphone_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('id', '!=', $this->id)
            ->get()
            ->contains(function ($booking) use ($currentEnd, $newEnd) {
                $start = Carbon::parse(
                    "{$booking->start_booking_date} {$booking->start_time}",
                    'Asia/Jakarta'
                );

                $end = Carbon::parse(
                    "{$booking->end_booking_date} {$booking->end_time}",
                    'Asia/Jakarta'
                );

                return $currentEnd->lt($end) && $newEnd->gt($start);
            });
    }
}
