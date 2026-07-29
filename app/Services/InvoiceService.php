<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public static function generate(Booking $booking)
    {
        // Load relationships for invoice
        $booking->load(['guest', 'room', 'payments']);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', [
            'booking' => $booking,
        ]);

        // Use invoice number as filename
        $fileName = $booking->invoice_number.'.pdf';
        // $fileName = 'invoice-booking-'.$booking->id.'.pdf';

        // Storage::put(
        //     'invoices/'.$fileName,
        //     $pdf->output()
        // );

        // Ensure invoices folder exists
        if (! Storage::disk('public')->exists('invoices')) {
            Storage::disk('public')->makeDirectory('invoices');
        }

        // Save the PDF
        Storage::disk('public')->put(
            'invoices/'.$fileName,
            $pdf->output()
        );

        // return $pdf->download(
        //     'invoice-booking-' . $booking->id . '.pdf'
        // );

        return $fileName;
    }

    public static function generateInvoiceNumber()
    {
        $year = now()->year;

        $count = Booking::whereYear('created_at', $year)->count() + 1;

        return 'INV-'.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
