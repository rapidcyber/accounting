<div>
    <div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
            <tr>
                <td style="border:none;"><img src="{{ asset('/images/sp_logo.png') }}" width="60" height="60" alt="Logo"></td>
                <td style="border:none;text-align:center">
                    <p>6TH CONGRESSIONAL DISTRICT OFFICE</p>
                    <p>Dulong Bayan, Poblacion, Santa Maria, Bulacan</p>
                    <p>BUDGET FROM {{\Carbon\Carbon::parse($bugets->first()->date)->format('m/d/Y')}} TO {{\Carbon\Carbon::parse($bugets->last()->date)->format('m/d/Y')}}</p>
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

                <th>DATE</th>
                <th>DESCRIPTION</th>
                <th>AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($BUDGET as $expense)
                <tr>
                    <td style="text-align: center">{{ \Carbon\Carbon::parse($expense->date)->format('m/d/Y') }}</td>
                    <td>{{ $expense->description }}</td>
                    <td style="text-align: right"> {{ number_format($expense->total_amount, 2) }}</td>
                </tr>
            @endforeach
                <tr>
                    <td></td>
                    <td style="text-align: right; font-weight: bold;">GRAND TOTAL: </td>
                    <td style="text-align: right; font-weight: bold;">
                         {{ number_format($budgets->sum('amount'), 2) }}
                    </td>
                </tr>
        </tbody>
    </table>
</div>
