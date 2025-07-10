<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Vouchers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @media print {
            @page {size: landscape}

            body {
                font-family: Arial, sans-serif;
                color: #000;
                background: #fff;
                margin: 0;
                font-size:12px;
            }
            .no-print {
                display: none !important;
            }
            .voucher-card {
                float: left;
                width:50%;
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
    @php($ctr = 0)
    <div>

        @foreach ($vouchers as $item)
            @php($ctr++)
            @include('components.voucher', ['voucher'=>$item])
            @if($ctr % 2 == 0)
            <br clear="all">
            @endif
        @endforeach
    </div>

    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = () => window.close()
        };
    </script>

</body>
</html>
