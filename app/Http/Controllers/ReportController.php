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

            $reply = "✅ Parsed:\n";
            foreach ($parsed as $key => $value) {
                $reply .= "$key: $value\n";
            }

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
                $data[trim($key)] = trim($value);
            }
        }

        return $data;
    }
}
