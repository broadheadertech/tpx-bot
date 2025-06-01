<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Http\Controllers\Controller;
use App\Models\AppscriptReport;
use App\Models\Barber;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        if ($update->getMessage()) {
            $message = $update->getMessage();
            $text = $message->getText();
            $chatId = $message->getChat()->getId();

            $senderId = $message->getFrom()->getId();

            // ✅ Get the actual bot ID dynamically (or cache this)
            $botId = Telegram::getMe()->getId();

            // ✅ Log sender and bot IDs (for debugging — remove later)
            Log::info('Sender ID: ' . $senderId);
            Log::info('Bot ID: ' . $botId);

            // ✅ Prevent bot from replying to itself
            if ($senderId == $botId) {
                return response()->json('Bot message ignored', 200);
            }

            try {
                $parsed = $this->parseMessage($text);

                // Assign variables from the parsed data
                $customer_no   = $parsed['customer_no'] ?? throw new \Exception("Missing customer_no");
                $name          = $parsed['name'] ?? throw new \Exception("Missing name");
                $barber        = $parsed['barber'] ?? throw new \Exception("Missing barber");
                $booking_type  = $parsed['booking_type'] ?? throw new \Exception("Missing booking_type");
                $time          = $parsed['time'] ?? throw new \Exception("Missing time");
                $date          = $parsed['date'] ?? throw new \Exception("Missing date");
                $service       = $parsed['service'] ?? throw new \Exception("Missing service");
                $amount        = $parsed['amount'] ?? throw new \Exception("Missing amount");
                $mop           = $parsed['mop'] ?? throw new \Exception("Missing mode of payment");

                $barberDetail = Barber::where('name', strtoupper($barber))->first();
                if (!$barberDetail) {
                    throw new \Exception("Barber not found: " . $barber);
                }

                $serviceDetail = Service::where('name', strtoupper($service))->first();
                if (!$serviceDetail) {
                    throw new \Exception("Service not found: " . $service);
                }

                $slug = Str::random(6);

                Report::create([
                    'customer_no'   => $customer_no,
                    'barber_id'     => $barberDetail->id,
                    'service_id'    => $serviceDetail->id,
                    'slug'          => $slug,
                    'name'          => $name,
                    'booking_type'  => $booking_type,
                    'time'          => $time,
                    'date'          => $date,
                    'amount'        => $amount,
                    'mop'           => $mop
                ]);

                AppscriptReport::create([
                    'customer_no'   => $customer_no,
                    'barber'        => $barberDetail->name,
                    'service'       => $serviceDetail->name,
                    'name'          => $name,
                    'booking_type'  => $booking_type,
                    'time'          => $time,
                    'date'          => $date,
                    'amount'        => $amount,
                    'mop'           => $mop
                ]);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => '✅ Record Saved!',
                ]);

                return response()->json('success', 200);
            } catch (\Exception $e) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ Error: " . $e->getMessage(),
                ]);

                // ✅ Always return 200 to stop Telegram retries
                return response()->json(['error' => $e->getMessage()], 200);
            }
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

    // Original
    public function getWeeklySalesOriginal()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Get all reports with barber and service
        $reports = Report::with(['barber', 'service'])->get();

        // Filter reports within current week (based on string dates)
        $weeklyReports = $reports->filter(function ($report) use ($startOfWeek, $endOfWeek) {
            try {
                $parsed = Carbon::createFromFormat('m/d/Y', $report->date);
                return $parsed->between($startOfWeek, $endOfWeek);
            } catch (\Exception $e) {
                return false;
            }
        });

        // Group by barber
        $groupedByBarber = $weeklyReports->groupBy('barber_id');

        $result = [];

        foreach ($groupedByBarber as $barberId => $barberReports) {
            $totalSalary = 0;
            $barberName = $barberReports->first()->barber->name ?? 'Unknown Barber';
            $barberRate = $barberReports->first()->barber->rate;

            // Group by date (still string format)
            $groupedByDate = $barberReports->groupBy('date');

            // Sort dates in ascending order
            $sortedDates = collect($groupedByDate)->sortKeysUsing(function ($a, $b) {
                $dateA = Carbon::createFromFormat('m/d/Y', $a);
                $dateB = Carbon::createFromFormat('m/d/Y', $b);
                return $dateA->lessThan($dateB) ? -1 : 1;
            });

            $dailyServices = [];
            foreach ($sortedDates as $date => $dailyReports) {
                $groupedByService = $dailyReports->groupBy('service_id');

                $serviceEntries = [];
                $totalIncentive = 0;
                foreach ($groupedByService as $serviceId => $serviceReports) {
                    $incentive = 0;
                    $serviceName = $serviceReports->first()->service->name ?? 'Unknown Service';
                    $incentive = $incentive + ($serviceReports->sum('amount') * $serviceReports->first()->service->percentage);
                    $serviceEntries[] = [
                        'name' => $serviceName,
                        'count' => $serviceReports->count(),
                        'gross_amount' => $serviceReports->sum('amount'),
                        'incentive' => $incentive
                    ];

                    $totalIncentive = $totalIncentive + $incentive;
                }

                $dailyServices[] = [
                    'date' => $date,
                    'entries' => $serviceEntries,
                ];

                if ($barberRate > $totalIncentive) {
                    $totalIncentive = $barberRate;
                }

                $totalSalary = $totalSalary + $totalIncentive;
            }

            $result[] = [
                'barber' => $barberName,
                'barber_rate' => $barberRate,
                'services' => $dailyServices,
                'total_salary' => $totalSalary,
            ];
        }

        return response()->json($result);
    }

    // Modified
    public function getWeeklySales()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Get all reports with barber and service
        $reports = Report::with(['barber', 'service'])->get();

        // Filter reports within current week (based on string dates)
        $weeklyReports = $reports->filter(function ($report) use ($startOfWeek, $endOfWeek) {
            try {
                $parsed = Carbon::createFromFormat('m/d/Y', $report->date);
                return $parsed->between($startOfWeek, $endOfWeek);
            } catch (\Exception $e) {
                return false;
            }
        });

        // Group by barber
        $groupedByBarber = $weeklyReports->groupBy('barber_id');

        $result = [];

        foreach ($groupedByBarber as $barberId => $barberReports) {
            $totalSalary = 0;
            $barberName = $barberReports->first()->barber->name ?? 'Unknown Barber';
            $barberRate = $barberReports->first()->barber->rate;

            // Group by date (still string format)
            $groupedByDate = $barberReports->groupBy('date');

            // Sort dates in ascending order
            $sortedDates = collect($groupedByDate)->sortKeysUsing(function ($a, $b) {
                $dateA = Carbon::createFromFormat('m/d/Y', $a);
                $dateB = Carbon::createFromFormat('m/d/Y', $b);
                return $dateA->lessThan($dateB) ? -1 : 1;
            });

            $dailyServices = [];
            foreach ($sortedDates as $date => $dailyReports) {
                $groupedByService = $dailyReports->groupBy('service_id');

                $serviceEntries = [];
                $totalIncentive = 0;
                foreach ($groupedByService as $serviceId => $serviceReports) {
                    $incentive = 0;
                    $serviceName = $serviceReports->first()->service->name ?? 'Unknown Service';
                    $incentive = $incentive + ($serviceReports->sum('amount') * $serviceReports->first()->service->percentage);
                    $serviceEntries[] = [
                        'name' => $serviceName,
                        'count' => $serviceReports->count(),
                        'gross_amount' => $serviceReports->sum('amount'),
                        'incentive' => $incentive
                    ];

                    $totalIncentive = $totalIncentive + $incentive;
                }

                $dailyServices[] = [
                    'date' => $date,
                    'entries' => $serviceEntries,
                ];

                if ($barberRate > $totalIncentive) {
                    $totalIncentive = $barberRate;
                }

                $totalSalary = $totalSalary + $totalIncentive;

                $resultDetails[] = [
                'barber' => $barberName,
                'barber_rate' => $barberRate,
                'services' => $dailyServices,
                'total_salary' => $totalSalary,
            ];

                // Format the message to send to Telegram
                $message = "Weekly Sales Report\n\n";
                foreach ($resultDetails as $barberReport) {
                    $message .= "Barber: " . $barberReport['barber'] . "\n";
                    $message .= "Total Salary: $" . number_format($barberReport['total_salary'], 2) . "\n";
                    $message .= "---------------------------------\n";
                    foreach ($barberReport['services'] as $service) {
                        $message .= "Date: " . $service['date'] . "\n";
                        foreach ($service['entries'] as $entry) {
                            $message .= "Service: " . $entry['name'] . "\n";
                            $message .= "Gross Amount: $" . number_format($entry['gross_amount'], 2) . "\n";
                            $message .= "Incentive: $" . number_format($entry['incentive'], 2) . "\n";
                        }
                        $message .= "---------------------------------\n";
                    }
                }

                // Send the message to Telegram
                $this->sendToTelegram($message);
            }

            $result[] = [
                'barber' => $barberName,
                'barber_rate' => $barberRate,
                'services' => $dailyServices,
                'total_salary' => $totalSalary,
            ];
        }

        // Return response
        return response()->json($result);
    }


    public function sendToTelegram($message)
    {
        // Your bot's API token and chat ID
        $botToken = "7769572088:AAFW5ulJXrRD7f8eYbnKofpypsDnUYNwjWo";  // Replace with your bot's token
        $chatId = '-4764468184';      // Replace with your group's chat ID

        // Telegram API endpoint
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Prepare the message payload
        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML', // You can use HTML formatting
        ];

        // Send the message
        try {
            $response = Http::post($url, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            // Log error if something goes wrong
            Log::error('Telegram message failed: ' . $e->getMessage());
            return false;
        }
    }
}
