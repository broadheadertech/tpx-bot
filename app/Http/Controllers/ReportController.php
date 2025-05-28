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
            $botId = 7769572088; // 🔁 Replace this with your actual bot ID

            // ✅ Prevent the bot from replying to itself
            if ($senderId == $botId) {
                return;
            }

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

            $reply = "✅ Record Info:\nCustomer #: $customer_no\nName: $name\nBarber: $barber\nType: $booking_type\nTime: $time\nDate: $date\nService: $service\nAmount: $amount\nMOP: $mop \n \n '✅ Record has been saved!";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $reply,
            ]);

            $barberDetail = Barber::where('name', strtoupper($barber))->first();
            $serviceDetail = Service::where('name', strtoupper($service))->first();
            $slug = Str::random(6);

            $report = Report::create([
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
            ]);

            $report = AppscriptReport::create([
                'customer_no' => $customer_no,
                'barber' => $barberDetail->name,
                'service' => $serviceDetail->name,
                'name' => $name,
                'booking_type' => $booking_type,
                'time' => $time,
                'date' => $date,
                'amount' => $amount,
                'mop' => $mop
            ]);

            return response()->json('success', 200);
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
            $barberName = $barberReports->first()->barber->name ?? 'Unknown Barber';
            $barberRate = $barberReports->first()->barber->rate;
            $totalSalary = 0;
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


                foreach ($groupedByService as $serviceId => $serviceReports) {
                    $serviceName = $serviceReports->first()->service->name ?? 'Unknown Service';

                    $todaysIncentive = $serviceReports->sum('amount') * $serviceReports->first()->service->percentage;
                    if($barberRate > $todaysIncentive)
                    {
                        $todaysIncentive = $barberRate;
                    }
                    $serviceEntries[] = [
                        'name' => $serviceName,
                        'count' => $serviceReports->count(),
                        'gross_amount' => $serviceReports->sum('amount'),
                        'salary_for_the_day' => $todaysIncentive
                    ];
                }

                $dailyServices[] = [
                    'date' => $date,
                    'entries' => $serviceEntries,
                ];

                $totalSalary = $totalSalary + $todaysIncentive;
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
}
