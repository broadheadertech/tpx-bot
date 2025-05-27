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
        // $update = Telegram::getWebhookUpdate();

        // if ($update->getMessage()) {
        //     $message = $update->getMessage();
        //     $text = $message->getText();
        //     $chatId = $message->getChat()->getId();

        //     $parsed = $this->parseMessage($text);

        //     // Assign variables from the parsed data
        //     $customer_no   = $parsed['customer_no'] ?? null;
        //     $name          = $parsed['name'] ?? null;
        //     $booking_type  = $parsed['booking_type'] ?? null;
        //     $time          = $parsed['time'] ?? null;
        //     $date          = $parsed['date'] ?? null;
        //     $service       = $parsed['service'] ?? null;
        //     $amount        = $parsed['amount'] ?? null;
        //     $mop           = $parsed['mop'] ?? null;

        //     $reply = "✅ Booking Info:\nCustomer #: $customer_no\nName: $name\nType: $booking_type\nTime: $time\nDate: $date\nService: $service\nAmount: $amount\nMOP: $mop";

        //     Telegram::sendMessage([
        //         'chat_id' => $chatId,
        //         'text' => $reply,
        //         'reply_markup' => Keyboard::make([
        //             'inline_keyboard' => [
        //                 [
        //                     ['text' => '✅ Yes', 'callback_data' => 'data_final_yes'],
        //                     ['text' => '❌ No', 'callback_data' => 'data_final_no'],
        //                 ]
        //             ]
        //         ])
        //     ]); // store for 5 minutes

        // }

        $update = Telegram::getWebhookUpdate();

        if ($update->getMessage()) {
            $this->handleTextMessage($update->getMessage());
        } elseif ($update->getCallbackQuery()) {
            $this->handleCallbackQuery($update->getCallbackQuery());
        }

        return response('ok', 200);
    }

    private function handleTextMessage($message)
    {
        $text = $message->getText();
        $chatId = $message->getChat()->getId();

        $parsed = $this->parseMessage($text);

        $customer_no   = $parsed['customer_no'] ?? null;
        $name          = $parsed['name'] ?? null;
        $booking_type  = $parsed['booking_type'] ?? null;
        $time          = $parsed['time'] ?? null;
        $date          = $parsed['date'] ?? null;
        $service       = $parsed['service'] ?? null;
        $amount        = $parsed['amount'] ?? null;
        $mop           = $parsed['mop'] ?? null;

        $reply = "✅ Booking Info:\n"
            . "Customer #: $customer_no\n"
            . "Name: $name\n"
            . "Type: $booking_type\n"
            . "Time: $time\n"
            . "Date: $date\n"
            . "Service: $service\n"
            . "Amount: $amount\n"
            . "MOP: $mop";

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $reply,
        ]);

        Cache::put("booking_$chatId", $parsed, 300); // Store for 5 minutes

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => 'Is the data final?',
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

    private function handleCallbackQuery($callback)
    {
        $chatId = $callback->getMessage()->getChat()->getId();
        $data = $callback->getData();

        if ($data === 'data_final_yes') {
            $booking = Cache::get("booking_$chatId");

            if ($booking) {
                // TODO: Save to DB or other processing
                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callback->getId(),
                    'text' => 'Booking confirmed! ✅',
                ]);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Your booking has been confirmed and saved! 📦',
                ]);
            } else {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'No booking data found. Please try again.',
                ]);
            }
        } elseif ($data === 'data_final_no') {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback->getId(),
                'text' => 'Booking canceled. ❌',
            ]);

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Please resend the booking info.',
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
