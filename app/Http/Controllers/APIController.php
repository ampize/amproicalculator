<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Goutte\Client as GoutteClient;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class APIController extends BaseController
{
    protected $viewsXPath=".engagementInfo-valueNumber";

    public function getSWMetrics(Request $request)
    {
        $url = $request->input("url", null);
        if (empty($url)) {
            abort(400, "Missing required params");
        }
        $client = new GoutteClient();
        $client->followRedirects();
        $guzzleClient = new \GuzzleHttp\Client(array(
            'curl' => array(
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ),
        ));
        $client->setClient($guzzleClient);
        $client->setHeader('User-Agent', "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36");
        $crawler = $client->request('GET', 'https://www.similarweb.com/fr/website/'.$url);
        $nbViews=$crawler->filter($this->viewsXPath)->first()->text();
        return response()->json([
            "nbViews" => $nbViews,
        ]);
    }

    public function getReport(Request $request)
    {
//        $email = $request->input("email", null);
//        $uniqueVisitors = $request->input("uniqueVisitors", null);
//        $averageVisits = $request->input("averageVisits", null);
//        $averagePages = $request->input("averagePages", null);
//        if (empty($email)||empty($uniqueVisitors)||empty($averageVisits)||empty($averagePages)) {
//            abort(400, "Missing required params");
//        }
        $client=new \Google_Client();
        $client->setApplicationName("AMPROICalculator");
        $client->setScopes(implode(' ', array(
                \Google_Service_Sheets::SPREADSHEETS,\Google_Service_Slides::PRESENTATIONS)
        ));
        $client->setAuthConfig(realpath(__DIR__.'/../../../client_secret.json'));
        $client->setAccessType('offline');
        $credentialsPath=realpath(__DIR__.'/../../../credentials/credentials.json');
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        $service = new \Google_Service_Sheets($client);
        $spreadSheetModelId = "1HqEblVk-6pCX8caa90zL6uVOYDepC792Mh76TI1OcXA";
        $range = 'B3:C13';
        $response = $service->spreadsheets_values->get($spreadSheetModelId, $range);
        $modelValues = $response->getValues();



        $requestBody = new \Google_Service_Sheets_Spreadsheet();
        $response = $service->spreadsheets->create($requestBody);
        $newId=$response->getSpreadsheetId();
        $body= new \Google_Service_Sheets_ValueRange([
            'values' => $modelValues
        ]);
        $undocumentedCrap = $service->spreadsheets_values->update($newId, $range,
            $body,[
                'valueInputOption' => 'USER_ENTERED'
            ]);
        var_dump($undocumentedCrap);
        die("ok");



    }



}