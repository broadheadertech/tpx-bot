<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
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
        $botInfo = Telegram::getMe();
        $botId = $botInfo->getId();

        if ($callback = $update->getCallbackQuery()) {
            return response()->json(
                200
            );
        } elseif ($update->getMessage()) {
            $message = $update->getMessage();
            $text = $message->getText();
            $chatId = $message->getChat()->getId();

            // 👇 Ignore messages sent by the bot itself

            $parsed = $this->parseMessage($text);

            // Assign variables from the parsed data
            $customer_no   = $parsed['customer_no'] ?? null;
            $name          = $parsed['name'] ?? null;
            $barber        = $parsed['barber'] ?? null;
            $booking_type  = $parsed['booking_type'] ?? null;
            $time          = $parsed['time'] ?? null;
            $date          = $parsed['date'] ?? null;
            $service       = $parsed['service'] ?? null;
            $amount        = $parsed['amount'] ?? null;
            $mop           = $parsed['mop'] ?? null;

            $reply = "✅ Booking Info:\nCustomer #: $customer_no\nName: $name\nBarber: $barber\nType: $booking_type\nTime: $time\nDate: $date\nService: $service\nAmount: $amount\nMOP: $mop";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => '✅ Record has been saved!' . $botId,
            ]);

            $barberDetail = Barber::where('name', strtoupper($barber))->first();
            $serviceDetail = Service::where('name', strtoupper($service))->first();
            $slug = Str::random(6);
            $report = Report::create(
                [
                    'customer_no' => $customer_no,
                    'barber_id' => $barberDetail->id,
                    'service_id' => $serviceDetail->id,
                    'slug' => $slug,
                    'name' => $name,
                    'booking_type' => $booking_type,
                    'time' => $time,
                    'date' => $date,
                    'amount' => $amount,
                    'mop' => $mop
                ]
            );


            return response()->json(
                'success',
                200
            );
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
