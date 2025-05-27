<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // 1. Handle Callback Query first (button press)
        if ($callback = $update->getCallbackQuery()) {
            $chatId = $callback->getMessage()->getChat()->getId();
            $data = $callback->getData();

            if ($data === 'data_final_yes') {
                $booking = Cache::pull("booking_$chatId");

                if ($booking) {
                    // TODO: Save to DB here if needed
                    // Booking::create($booking);

                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => 'Booking confirmed!',
                    ]);

                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => '✅ Your booking has been saved!',
                    ]);
                } else {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => 'No booking data found.',
                    ]);
                }
            } elseif ($data === 'data_final_no') {
                Cache::forget("booking_$chatId");

                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callback->getId(),
                    'text' => 'Booking cancelled.',
                ]);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => '❌ Booking cancelled. Please resend the booking info.',
                ]);
            }

            // ✅ Stop further execution
            return response('ok', 200);
        }

        // 2. Handle incoming message
        if ($message = $update->getMessage()) {
            $text = $message->getText();
            $chatId = $message->getChat()->getId();

            // Parse booking info
            $parsed = $this->parseMessage($text);

            if (!$parsed) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => '⚠️ Could not parse booking info. Please check your format.',
                ]);
                return response('ok', 200);
            }

            // Save to cache
            Cache::put("booking_$chatId", $parsed, 300);

            // Prepare variables
            $reply = "✅ Booking Info:\n"
                . "Customer #: {$parsed['customer_no']}\n"
                . "Name: {$parsed['name']}\n"
                . "Type: {$parsed['booking_type']}\n"
                . "Time: {$parsed['time']}\n"
                . "Date: {$parsed['date']}\n"
                . "Service: {$parsed['service']}\n"
                . "Amount: {$parsed['amount']}\n"
                . "MOP: {$parsed['mop']}\n\n"
                . "Is the data final?";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $reply,
                'reply_markup' => Keyboard::make([
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Yes', 'callback_data' => 'data_final_yes'],
                            ['text' => '❌ No', 'callback_data' => 'data_final_no'],
                        ]
                    ]
                ])
            ]);
        }

        return response('ok', 200); // ✅ Always return 200 OK once
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
