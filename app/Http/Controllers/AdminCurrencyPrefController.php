<?php

namespace App\Http\Controllers;

use App\Support\AdminCurrencyPref;
use Illuminate\Http\Request;

class AdminCurrencyPrefController extends Controller
{
    public function toggle(Request $request)
    {
        session([
            AdminCurrencyPref::SESSION_KEY => ! session(AdminCurrencyPref::SESSION_KEY, false),
        ]);

        return back();
    }
}
