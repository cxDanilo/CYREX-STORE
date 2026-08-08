<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductActivityLog;
use Illuminate\Http\Request;

class ProductActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductActivityLog::orderByDesc('created_at');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('admin.activity.index', compact('logs'));
    }
}
