<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table {{ $table->table_number }} QR Code</title>
    <style>
        body { margin: 0; background: #f3f4f6; color: #111827; font-family: Arial, Helvetica, sans-serif; }
        .sheet { width: 148mm; min-height: 200mm; margin: 20px auto; box-sizing: border-box; background: white; border-radius: 18px; padding: 20mm; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,.12); }
        .restaurant { margin: 0; font-size: 20px; color: #4b5563; }.table-number { margin: 12px 0 6px; font-size: 40px; font-weight: 800; }.instructions { margin: 12px auto 25px; max-width: 360px; color: #4b5563; font-size: 18px; line-height: 1.5; }.qr-wrapper { display: inline-block; padding: 20px; border: 3px solid #111827; border-radius: 18px; background: white; }.url { margin-top: 22px; color: #6b7280; font-size: 11px; word-break: break-all; }.print-button { display: inline-block; margin-top: 28px; border: 0; border-radius: 10px; background: #2563eb; color: white; padding: 12px 24px; cursor: pointer; font-size: 16px; }
        @media print { body { background: white; }.sheet { width: auto; min-height: auto; margin: 0; box-shadow: none; }.print-button, .url { display: none; } }
    </style>
</head>
<body>
    <main class="sheet">
        <p class="restaurant">{{ $table->restaurant?->name ?? config('app.name') }}</p>
        <h1 class="table-number">Table {{ $table->table_number }}</h1>
        <p class="instructions">Scan this QR code to browse the menu, order food, and pay from your phone.</p>
        <div class="qr-wrapper">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(320)->margin(2)->errorCorrection('H')->generate($orderingUrl) !!}</div>
        <p class="url">{{ $orderingUrl }}</p>
        <button type="button" class="print-button" onclick="window.print()">Print Table QR</button>
    </main>
</body>
</html>
