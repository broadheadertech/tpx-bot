<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;

class GoogleSheetService
{
    protected $spreadsheetId;
    protected $service;

    public function __construct()
    {
        $this->spreadsheetId = env('GOOGLE_SHEET_ID');
        $credentialsJson = base64_decode(env('GOOGLE_SERVICE_ACCOUNT_BASE64'));
        $credentialsArray = json_decode($credentialsJson, true);

        $client = new Client();
        $client->setApplicationName('Laravel Google Sheets Bot');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig($credentialsArray);
        $client->setAccessType('offline');

        $this->service = new Sheets($client);
    }

    /**
     * Create the current week's sheet tab if it doesn't exist
     */
    protected function createWeeklySheetIfNotExists(): string
    {
        $startOfWeek = now()->startOfWeek()->format('Y-m-d');
        $sheetName = 'Week-' . $startOfWeek;

        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheetName;
            }
        }

        // Create new sheet tab
        $request = new Request([
            'addSheet' => [
                'properties' => [
                    'title' => $sheetName,
                ],
            ],
        ]);

        $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
            'requests' => [$request],
        ]);

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);

        // Insert headers
        $this->appendHeaders($sheetName);

        return $sheetName;
    }

    /**
     * Insert header row into a given sheet tab
     */
    protected function appendHeaders(string $sheetName): void
    {
        $headers = [[
            'Date',
            'Customer No',
            'Name',
            'Barber',
            'Booking Type',
            'Time',
            'Service',
            'Amount',
            'MOP'
        ]];

        $body = new ValueRange([
            'values' => $headers,
        ]);

        $params = ['valueInputOption' => 'USER_ENTERED'];
        $range = $sheetName . '!A1:J1';

        $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    /**
     * Append a row of data to the current week's tab
     */
    public function appendRow(array $values): void
    {
        $sheetName = $this->createWeeklySheetIfNotExists();
        $range = $sheetName . '!A:J';

        $body = new Sheets\ValueRange([
            'values' => [$values],
        ]);

        $params = ['valueInputOption' => 'USER_ENTERED'];

        $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );

        // Update the summary after new data is added
        $this->appendSummary();
    }


    /**
     * Read all rows from the current week's tab
     */
    public function readSheet(): array
    {
        $sheetName = $this->createWeeklySheetIfNotExists();
        $range = $sheetName . '!A:J';

        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        return $response->getValues() ?? [];
    }

    public function appendSummary(): void
    {
        $sheetName = $this->createWeeklySheetIfNotExists();
        $range = $sheetName . '!A:J';

        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        $rows = collect($response->getValues() ?? []);

        if ($rows->count() < 2) return;

        $headers = $rows->first();
        $data = $rows->skip(1);

        $barberIndex  = array_search('Barber', $headers);
        $dateIndex    = array_search('Date', $headers);
        $amountIndex  = array_search('Amount', $headers);
        $serviceIndex = array_search('Service', $headers);

        if ($barberIndex === false || $dateIndex === false || $amountIndex === false || $serviceIndex === false) {
            return;
        }

        $today = now()->format('Y-m-d');
        $commissionRate = 0.10;

        $summary = [];

        foreach ($data as $row) {
            $date = $row[$dateIndex] ?? '';
            if ($date !== $today) continue;

            $barber = $row[$barberIndex] ?? 'Unknown';
            $service = $row[$serviceIndex] ?? 'Unknown Service';
            $amount = isset($row[$amountIndex]) ? floatval($row[$amountIndex]) : 0;

            if (!isset($summary[$barber])) {
                $summary[$barber] = [];
            }

            if (!isset($summary[$barber][$service])) {
                $summary[$barber][$service] = 0;
            }

            $summary[$barber][$service] += $amount;
        }

        // Prepare rows
        $values = [];

        foreach ($summary as $barber => $services) {
            $values[] = ['Barber'];
            $values[] = [$barber];
            $values[] = ['Service', 'Total Amount', 'Commission'];

            foreach ($services as $service => $total) {
                $commission = $total * $commissionRate;
                $values[] = [
                    $service,
                    number_format($total, 2),
                    number_format($commission, 2)
                ];
            }

            // Blank row between barbers
            $values[] = [''];
        }

        $body = new Sheets\ValueRange([
            'values' => $values,
        ]);

        // Paste starting from column L
        $summaryRange = $sheetName . '!L1';
        $params = ['valueInputOption' => 'USER_ENTERED'];

        $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $summaryRange,
            $body,
            $params
        );
    }
}
