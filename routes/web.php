<?php

use App\Http\Controllers\Client\PageController;
use App\Livewire\DeliveryMap;
use App\Models\Iphones;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


Route::view('/', [PageController::class, 'welcome'])->name('welcome');
Route::get('cara-sewa', [PageController::class, 'howtorent'])->name('howtorent');
Route::get('kebijakan-privasi', [PageController::class, 'privacy'])->name('privacy');
Route::get('syarat-ketentuan', [PageController::class, 'terms'])->name('terms');
Route::get('detail/{iphones:slug}', [PageController::class, 'detail'])->name('detail');
Route::get('faqs', [PageController::class, 'faq'])->name('faqs');
Route::get('contacts', [PageController::class, 'contacts'])->name('contacts');
Route::get('products', [PageController::class, 'products'])->name('products');
Route::get('booking-status', [PageController::class, 'bookingStatus'])->name('booking.status');
Route::get('prices', [PageController::class, 'prices'])->name('prices');

Route::get('/db-check', function () {
    return [
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_DATABASE' => env('DB_DATABASE'),
    ];
});

Route::get('/geo/search', function (Request $request) {

    $query = $request->q;

    if (!$query) {
        return [];
    }

    return Cache::remember(
        'geo_search_' . md5($query),
        now()->addHours(6),

        function () use ($query) {

            return Http::withHeaders([
                'User-Agent' => 'SkyRent/1.0'
            ])->get(
                'https://nominatim.openstreetmap.org/search',
                [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 5,
                    'countrycodes' => 'id'
                ]
            )->json();
        }
    );
});
Route::get('/geo/reverse', function (Request $request) {

    $lat = $request->lat;
    $lon = $request->lon;

    return Cache::remember(
        'geo_reverse_' . md5($lat . $lon),
        now()->addDays(7),

        function () use ($lat, $lon) {

            return Http::withHeaders([
                'User-Agent' => 'SkyRent/1.0'
            ])->get(
                'https://nominatim.openstreetmap.org/reverse',
                [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json'
                ]
            )->json();
        }
    );
});

Route::middleware(['auth', 'role:super-admin|admin'])->prefix('admin')
    ->group(function () {
        Route::view('dashboard', 'dashboard')
            ->name('dashboard');

        Route::view('iphones', 'iphones')
            ->name('iphones');

        Route::view('bookings', 'bookings')
            ->name('bookings');

        Route::view('list-delivery', 'list-delivery')
            ->name('booking.delivery');

        Route::view('delivery-map', 'delivery-map')
            ->name('delivery.map');

        Route::get('iphones/edit/{iphone:id}', function (Iphones $iphone) {
            return view('iphones.edit', [
                'iphone' => $iphone
            ]);
        })->name('iphones.edit');

        Route::view('iphones/create', 'iphones.create')
            ->name('iphones.create');

        Route::view('profile', 'profile')
            ->name('profile');

        Route::view('settings/basic', 'settings.basic')
            ->name('settings.basic');

        Route::view('reports/revenue', 'reports.revenue')
            ->name('reports.revenue');

        Route::view('reports/top-device', 'reports.top-device')
            ->name('reports.topdevice');

        Route::view('settings/users', 'settings.users')
            ->name('settings.users');

        Route::view('settings/sliders', 'settings.sliders')->name('settings.sliders');

        Route::view('faq', 'faq')
            ->name('faq');

        Route::view('payments', 'payments')
            ->name('payments');
    });


require __DIR__ . '/auth.php';
