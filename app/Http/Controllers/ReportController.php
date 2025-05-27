<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        //
    }

    public function webhook(Request $request)
    {
        $update = Telegram::getWebhookUpdate();

        if ($update->getMessage()) {
            $message = $update->getMessage();
            $text = $message->getText();
            $chatId = $message->getChat()->getId();

            $parsed = $this->parseMessage($text);

            // Assign variables from the parsed data
            $customer_no   = $parsed['customer_no'] ?? null;
            $name          = $parsed['name'] ?? null;
            $booking_type  = $parsed['booking_type'] ?? null;
            $time          = $parsed['time'] ?? null;
            $date          = $parsed['date'] ?? null;
            $service       = $parsed['service'] ?? null;
            $amount        = $parsed['amount'] ?? null;
            $mop           = $parsed['mop'] ?? null;

            $reply = "✅ Booking Info:
            Customer #: $customer_no
            Name: $name
            Type: $booking_type
            Time: $time
            Date: $date
            Service: $service
            Amount: $amount
            MOP: $mop";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $reply,
            ]);
        }
    }

    private function parseMessage(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($text));
        $data = [];

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);

                // Normalize keys: lowercase, trim, snake_case
                $normalizedKey = strtolower(trim($key));
                $normalizedKey = str_replace(' ', '_', $normalizedKey);

                $data[$normalizedKey] = trim($value);
            }
        }

        return $data;
    }
}
