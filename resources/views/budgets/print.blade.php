<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print budgets</title>
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
                        <p>BUDGET FROM {{\Carbon\Carbon::parse($budgets->first()->date)->format('m/d/Y')}} TO {{\Carbon\Carbon::parse($budgets->last()->date)->format('m/d/Y')}}</p>
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
                        <th>AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgets as $budget)
                        <tr>
                            <td style="text-align: center">{{ \Carbon\Carbon::parse($budget->date)->format('m/d/Y') }}</td>
                            <td>{{ $budget->description ?? 'New Budget' }}</td>
                            <td style="text-align: right"> {{ number_format($budget->amount, 2) }}</td>
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
    </div>
    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = () => window.close()
        };
    </script>

</body>
</html>
