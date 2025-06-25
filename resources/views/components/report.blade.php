<div>
    <div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
            <tr>
                <td style="border:none;"><img src="{{ asset('/images/sp_logo.png') }}" width="60" height="60" alt="Logo"></td>
                <td style="border:none;text-align:center">
                    <p>6TH CONGRESSIONAL DISTRICT OFFICE</p>
                    <p>Dulong Bayan, Poblacion, Santa Maria, Bulacan</p>
                    <p>EXPENSES AS {{now()->format('F, Y')}}</p>
                </td>
                <td style="border:none;text-align:right"><img src="{{ asset('/images/hrp_logo.png') }}" width="60" height="60" alt="Logo"></td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align:right">DATE:</td>
                <td>{{now()->format('m/d/Y')}}</td>
            </tr>
        </table>
    </div>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr style="background-color: #e0e0e0;">
                <th>ID</th>
                <th>DATE</th>
                <th>QUANTITY</th>
                <th>UNIT</th>
                <th>DESCRIPTION</th>
                <th>AMOUNT</th>
                <th>TOTAL AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td style="text-align: center">{{ $expense->id }}</td>
                    <td style="text-align: center">{{ \Carbon\Carbon::parse($expense->date)->format('m/d/Y') }}</td>
                    <td style="text-align: center">
                        {{ fmod($expense->quantity, 1) == 0 ? number_format($expense->quantity, 0) : $expense->quantity }}
                    </td>
                    <td style="text-align: center">{{ ucfirst($expense->unit) }}</td>
                    <td>{{ $expense->description }}</td>
                    <td style="text-align: right">&#8369; {{ number_format($expense->amount, 2) }}</td>
                    <td style="text-align: right">&#8369; {{ number_format($expense->total_amount, 2) }}</td>
                </tr>
            @endforeach
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: right; font-weight: bold;">GRAND TOTAL: </td>
                    <td style="text-align: right; font-weight: bold;">
                        &#8369; {{ number_format($expenses->sum('total_amount'), 2) }}
                    </td>
                </tr>
        </tbody>
    </table>
</div>
