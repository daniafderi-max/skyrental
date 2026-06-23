<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Iphones;
use Illuminate\Support\Facades\Http;
use App\Models\Revenue;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class BookingPage extends Component
{
    public $search = '';
    public $sortField = 'created_at';       // default sort pakai status
    public $sortDirection = 'desc';      // bisa diatur 'asc' / 'desc'
    public $filterStatus = '';
    public $paginate = 50;
    public $selectedAll = false;
    public $mySelected = [];

    public $iphonesAvailable;
    public $revenueToday;
    public $returnToday;
    public $bookingToday;
    public $returnTodayCount;

    public $pickup_type = 'pickup';
    public $lat;
    public $long;
    public $address;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->iphonesAvailable = Iphones::whereDoesntHave('bookings', function ($q) {
            $q->where('status', 'confirmed');
        })->get();

        $this->revenueToday = Revenue::whereDate('created_at', now()->toDateString())->sum('amount');
        $this->bookingToday = Booking::whereDate('created_at', Carbon::today())->get();
        $this->getReturnToday();
    }

    public function getReturnToday()
    {
        $this->returnToday = Booking::where('status', 'confirmed')->whereDate('end_booking_date', Carbon::today())->get();
    }

    public function destroy()
    {
        if (auth()->user()->can('delete')) {
            if ($this->mySelected) {
                try {
                    //code...
                    // dd('masuk ke try');
                    Booking::whereIn('id', $this->mySelected)->delete();
                    $this->mySelected = [];
                    $this->selectedAll = false;
                    LivewireAlert::title('Data berhasil dihapus')
                        ->position('top-end')
                        ->text('bulk delete data berhasil')
                        ->timer(5000)
                        ->toast()
                        ->success()
                        ->show();
                } catch (\Throwable $th) {
                    LivewireAlert::title('iPhone tidak ditemukan 1')
                        ->position('top-end')
                        ->text('tidak dapat menghapus data')
                        ->timer(5000)
                        ->toast()
                        ->error()
                        ->show();
                }
            } else {
                LivewireAlert::title('iPhone tidak ditemukan 2')
                    ->position('top-end')
                    ->text('tidak dapat menghapus data')
                    ->timer(5000)
                    ->toast()
                    ->error()
                    ->show();
            }
        } else {
            LivewireAlert::title('kamu tidak memiliki izin')
                ->position('top-end')
                ->text('tidak dapat menghapus data')
                ->timer(5000)
                ->toast()
                ->error()
                ->show();
            $this->mySelected = [];
            $this->selectedAll = false;
        }
    }

    public function delete($data)
    {
        if (auth()->user()->can('delete')) {
            $this->mySelected[] = $data['data'];
            $this->destroy('deleted successfully');
        } else {
            LivewireAlert::title('kamu tidak memiliki izin')
                ->position('top-end')
                ->text('tidak dapat menghapus data')
                ->timer(5000)
                ->error()
                ->show();
            $this->mySelected = [];
            $this->selectedAll = false;
        }
    }

    public function destroyAlert($value = '', $onConfirm = 'destroy')
    {

        LivewireAlert::title('Delete this posts ?')
            ->warning()
            ->toast()
            ->position('top-end')
            ->withConfirmButton('Delete')
            ->confirmButtonColor('green')
            ->cancelButtonColor('red')
            ->withCancelButton('Cancel')
            ->onConfirm($onConfirm, ['data' => $value])
            ->show();
    }

    public function getData()
    {
        $query = Booking::query()->with('iphone');

        // Filter status (default: confirmed)
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Search
        if ($this->search) {
            $query->search([
                'customer_name',
                'customer_phone',
                'customer_email',
                'booking_code',
                'status',
                'iphone.serial_number',
                'iphone.name'
            ], $this->search);
        }

        // Sorting
        if ($this->sortField === 'status') {
            $query->orderByRaw("
            FIELD(status, 'pending', 'confirmed', 'returned', 'cancelled')
        {$this->sortDirection}");
        } elseif (in_array($this->sortField, [
            'start_booking_date',
            'end_booking_date',
            'start_time',
            'end_time',
            'price',
            'duration',
            'created_at',
            'updated_at'
        ])) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function updatedSelectedAll($val)
    {
        $val ? $this->mySelected = $this->getData()->limit($this->paginate)->pluck('id') : $this->mySelected = [];
    }

    public function updatedMySelected()
    {
        if (count($this->mySelected) === $this->paginate) {
            $this->selectedAll = true;
        } else {
            $this->selectedAll = false;
        }
    }

    public function updatedPage($page)
    {
        $this->mySelected = [];
        $this->selectedAll = false;
    }

    public function sendGroupMessage()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => env('FONNTE_TOKEN'),
            ])->post('https://api.fonnte.com/send', [
                'target'  => env('FONNTE_GROUP_ID'),
                'message' => 'test from dashboard admin',
            ]);

            if ($response->failed()) {
                Log::error('Fonnte group message failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {

            Log::error('Fonnte exception', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return false;
        }
    }

    #[On('close-modal')]
    public function reRender()
    {
        $this->revenueToday = Revenue::whereDate('created_at', now()->toDateString())->sum('amount');
    }

    public function render()
    {
        return view('livewire.booking-page', [
            'bookings' => $this->getData()->paginate($this->paginate)
        ]);
    }
}
