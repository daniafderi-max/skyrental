<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Booking;

class ListDelivery extends Component
{
    public $search = '';
    public $selectedBookings = [];
    public $selectedAll = false;
    public $autoSelectPeriod = '';

    public function getTotalDeliveryProperty()
    {
        return Booking::where('pickup_type', 'delivery')->count();
    }

    public function getTodayDeliveryProperty()
    {
        return Booking::whereDate('start_booking_date', today())->count();
    }

    public function getOnDeliveryCountProperty()
    {
        return Booking::where('pickup_type', 'delivery')
            ->where('delivery_status', 'on_delivery')
            ->count();
    }

    public function getDeliveredTodayCountProperty()
    {
        return Booking::where('pickup_type', 'delivery')
            ->where('delivery_status', 'delivered')
            ->whereDate('updated_at', today())
            ->count();
    }

    public function changeStatus($id, $status)
    {
        Booking::findOrFail($id)->update([
            'delivery_status' => $status
        ]);

        session()->flash(
            'success',
            'Status berhasil diubah'
        );
    }

    public function autoSelectByTime()
    {
        if (!$this->autoSelectPeriod) {
            return;
        }

        $query = Booking::query()
            ->where('pickup_type', 'delivery')
            ->whereDate('start_booking_date', today());

        switch ($this->autoSelectPeriod) {

            case 'morning':
                $query->whereTime('start_time', '>=', '06:00:00')
                    ->whereTime('start_time', '<=', '11:00:00');
                break;

            case 'afternoon':
                $query->whereTime('start_time', '>=', '12:00:00')
                    ->whereTime('start_time', '<=', '17:00:00');
                break;

            case 'evening':
                $query->whereTime('start_time', '>=', '17:30:00')
                    ->whereTime('start_time', '<=', '22:00:00');
                break;
        }

        $this->selectedBookings = $query
            ->pluck('id')
            ->toArray();
    }

    public function getDeliveryBookingsProperty()
    {
        return Booking::where('pickup_type', 'delivery')
            ->where('customer_name', 'like', '%' . $this->search . '%')
            ->get();
    }

    public function getPendingDeliveryProperty()
    {
        return Booking::where('pickup_type', 'delivery')
            ->where('delivery_status', 'pending')
            ->get();
    }

    public function updatedSelectedAll($value)
    {
        if ($value) {
            $this->selectedBookings = $this->deliveryBookings->pluck('id')->toArray();
        } else {
            $this->selectedBookings = [];
        }
    }

    public function generateRoute()
    {
        if (empty($this->selectedBookings)) {
            session()->flash('error', 'Pilih minimal 1 booking');
            return;
        }

        Booking::whereIn('id', $this->selectedBookings)
            ->update([
                'delivery_status' => 'assigned'
            ]);

        return redirect()->route('delivery.map', [
            'ids' => implode(',', $this->selectedBookings)
        ]);
    }

    public function markDelivered($id)
    {
        Booking::find($id)->update([
            'delivery_status' => 'delivered'
        ]);
    }

    public function showRoute($id)
    {
        return redirect()->route('delivery.map', [
            'ids' => $id
        ]);
    }

    public function render()
    {
        return view('livewire.list-delivery', [
            'deliveryBookings' => $this->deliveryBookings,
            'pendingDelivery' => $this->pendingDelivery,
            'totalDelivery' => $this->totalDelivery,
            'todayDelivery' => $this->todayDelivery,
            'onDeliveryCount' => $this->onDeliveryCount,
            'deliveredTodayCount' => $this->deliveredTodayCount,
        ]);
    }
}
