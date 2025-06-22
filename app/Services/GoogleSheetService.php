<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetService
{
    protected $spreadsheetId;
    protected $sheetName;

    protected $service;

    public function __construct()
    {
        $this->spreadsheetId = env('GOOGLE_SHEET_ID'); // Set this in your .env
        $this->sheetName = 'Sales'; // Or whatever your sheet/tab name is
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
     * Append a row of data to the Google Sheet
     */
    public function appendRow(array $values): void
    {
        $range = $this->sheetName . '!A:J'; // Adjust range if more/less columns

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
    }

    /**
     * Read all rows from the sheet
     */
    public function readSheet(): array
    {
        $range = $this->sheetName . '!A:J';

        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        return $response->getValues() ?? [];
    }
}
