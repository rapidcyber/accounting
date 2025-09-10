<div class="voucher-card">
    <div style="border:1px solid #444; padding:10px">
        <div>
            <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
                <tr>
                    <td style="border:none;"><img src="{{ asset('/images/sp_logo.png') }}" width="80" height="80" alt="Logo"></td>
                    <td style="border:none;text-align:center">
                        <h1 style="font-size: 16px; font-weight:bold"><u>PETTY CASH VOUCHER</u></h1>
                        {{-- <p>6TH CONGRESSIONAL DISTRICT OFFICE</p>
                        <p>Dulong Bayan, Poblacion, Santa Maria, Bulacan</p> --}}
                    </td>
                    <td style="border:none; text-align:right">
                        No: <strong style="color:red;text-align:center;min-width: 4rem;display:inline-block;border-bottom:1px solid black">{{str_pad($voucher->id, 4, '0', STR_PAD_LEFT)}}</strong>
                    </td>
                </tr>
                <tr style="font-size:16px">
                    <td></td>
                    <td style="text-align:right"><b>DATE:</b></td>
                    <td style="border-bottom:1px solid black; text-align:center">{{\Carbon\Carbon::parse($voucher->date)->format('m/d/Y')}}</td>
                </tr>
            </table>
        </div>
        <div>
            <table style="width: 100%">
                <tr style="font-size:14px">
                    <td style="width: 80%">
                        <strong>To: </strong>
                        <span style="display:inline-block; width:90%; border-bottom: 1px solid black">
                            {{$voucher->name}}
                        </span>
                    </td>
                    <td></td>
                </tr>
            </table>
        </div>
        <div style="padding-top: 10px">
            <table style="width:100%; border: 1px solid black; border-collapse: collapse;" cellpadding="4">
                <thead>
                <tr>
                    <th style="text-align: center; border: 1px solid black; width: 80%;">PARTICULARS</th>
                    <th style="text-align: center; border: 1px solid black; width: 20%;">PAID BY</th>
                    <th style="text-align: center; border: 1px solid black;">AMOUNT</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($voucher->expenses as $item)
                <tr>
                    <td style="border: 1px solid black;">( {{$item->quantity .' '. $item->unit}} ) {{$item->description}} </td>
                    <td style="border: 1px solid black; text-align:center">
                        {{ucfirst($item->payment_method)}}
                    <td style="border: 1px solid black; text-align:right"> {{number_format($item->total_amount, 2)}}</td>
                </tr>
                @endforeach
                <tr style="font-weight: bold">
                    <td style="border: 1px solid black; text-align:right">TOTAL</td>
                    <td style="border: 1px solid black; text-align:right"> {{number_format($voucher->expenses->sum('total_amount'), 2)}}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div style="font-weight: bold; padding-top:5px">
            <table style="width: 100%">
                <tr>
                    <td style="width: 33%">Approved for payment:</td>
                    <td style="width: 33%">Paid by:</td>
                    <td style="width: 33%">Recieve Payment</td>
                </tr>
                <tr>
                    <td style="padding-right:20px">
                        <br><br>
                        <hr style="border-bottom: 1px solid black">
                    </td>
                    <td style="padding-right:20px">
                        <br><br>
                        <hr style="border-bottom: 1px solid black">
                    </td>
                    <td style="padding-right:20px">
                        <br><br>
                        <hr style="border-bottom: 1px solid black">
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
