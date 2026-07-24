<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use Illuminate\View\View;

class RestaurantTableQrController extends Controller
{
    public function print(RestaurantTable $table): View
    {
        abort_unless(filled($table->qr_token), 404, 'This table does not have a QR token.');

        $table->load('restaurant');
        $orderingUrl = route('restaurant.table.menu', ['table' => $table->qr_token]);

        return view('restaurant.tables.print-qr', compact('table', 'orderingUrl'));
    }
}
