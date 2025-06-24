<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Voucher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @media print {
            body {
                font-family: Arial, sans-serif;
                color: #000;
                background: #fff;
                margin: 0;
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

    @include('components.voucher')
    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = () => window.close()
        };
    </script>

</body>
</html>
