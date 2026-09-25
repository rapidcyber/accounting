<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @media print {
            body {
                font-family: Arial, sans-serif;
                color: #000;
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 14px;
            background: #fff;
        }
        p {
            margin: 0%;
        }
    </style>
</head>
<body>

    <div>
        <div>
            <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
                <tr>
                    <td style="border:none;"><img src="{{ asset('/images/sp_logo.png') }}" width="60" height="60" alt="Logo"></td>
                    <td style="border:none;text-align:center">
                        <p>6TH CONGRESSIONAL DISTRICT OFFICE</p>
                        <p>Dulong Bayan, Poblacion, Santa Maria, Bulacan</p>
                        @if($entries->isNotEmpty())
                        <p>BUDGET HISTORY FROM {{ $entries->first()->entry_date->format('m/d/Y') }} TO {{ $entries->last()->entry_date->format('m/d/Y') }}</p>
                        @endif
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
        <div>
            <table border="1" cellpadding="8" cellspacing="0" width="100%">
                <thead>
                    <tr style="background-color: #e0e0e0;">
                        <th>DATE</th>
                        <th>DESCRIPTION</th>
                        <th>ADDED</th>
                        <th>SPENT</th>
                        <th>BALANCE</th>
                    </tr>
                </thead>
                <tbody>
                    @if($entries->isNotEmpty())
                        <tr>
                            <td></td>
                            <td style="font-weight: bold;">BALANCE BROUGHT FORWARD</td>
                            <td></td>
                            <td></td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format($entries->first()->balance - $entries->first()->money_in + $entries->first()->money_out, 2) }}</td>
                        </tr>
                    @endif
                    @foreach($entries as $entry)
                        <tr>
                            <td style="text-align: center">{{ $entry->entry_date->format('m/d/Y') }}</td>
                            <td>{{ $entry->description }}</td>
                            <td style="text-align: right">{{ (float) $entry->money_in ? number_format($entry->money_in, 2) : '' }}</td>
                            <td style="text-align: right">{{ (float) $entry->money_out ? number_format($entry->money_out, 2) : '' }}</td>
                            <td style="text-align: right">{{ number_format($entry->balance, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                        <td style="text-align: right; font-weight: bold;">TOTAL: </td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($entries->sum('money_in'), 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($entries->sum('money_out'), 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format(optional($entries->last())->balance ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = () => window.close()
        };
    </script>

</body>
</html>
