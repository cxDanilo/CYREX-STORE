<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\WooCommerceImporter;
use Illuminate\Http\Request;

class WooCommerceImportController extends Controller
{
    public function create()
    {
        return view('admin.woocommerce.create');
    }

    public function store(Request $request, WooCommerceImporter $importer)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $result = $importer->import($request->file('csv')->getRealPath());

        return view('admin.woocommerce.result', compact('result'));
    }
}
