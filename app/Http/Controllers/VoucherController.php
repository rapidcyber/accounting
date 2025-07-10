<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;

class VoucherController extends Controller
{
    public function print()
    {
        $ids = session()->pull('print_user_ids', []);

        $vouchers = Voucher::whereIn('id', $ids)->get();

        return view('vouchers.print', ['vouchers' => $vouchers]);
    }

}
