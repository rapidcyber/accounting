<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;

class VoucherController extends Controller
{
    public function print($voucherId)
    {
        $voucher = Voucher::find($voucherId);

        return view('vouchers.print', ['voucher' => $voucher]);
    }

}
