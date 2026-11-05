<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $customer = $request->user('customer');
        $customer->loadCount('orders');

        return view('pages.account', [
            'customer' => $customer,
        ]);
    }
}
