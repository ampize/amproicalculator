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
        $email = $request->input("email", null);
        $url = $request->input("url", null);
        $uniqueVisitors = $request->input("uniqueVisitors", null);
        $averageVisits = $request->input("averageVisits", null);
        $averagePages = $request->input("averagePages", null);
        if (empty($email)||empty($uniqueVisitors)||empty($averageVisits)||empty($averagePages)||empty($url)) {
            abort(400, "Missing required params");
        }
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
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        $sheetsService = new \Google_Service_Sheets($client);
        $slidesService = new \Google_Service_Slides($client);
        $spreadSheetModelId = "1HqEblVk-6pCX8caa90zL6uVOYDepC792Mh76TI1OcXA";
        $slidesModelId ="1ACrSlNHwX-S-wPG5NP-ZHjRDRR4etEdlUd-9f0AXRDo";
        $driveService = new \Google_Service_Drive($client);

        $copiedFile = new \Google_Service_Drive_DriveFile();
        $copiedFile->setName('AMP economic impact on '.$url);
        $newFile=$driveService->files->copy($spreadSheetModelId, $copiedFile);
        $newId=$newFile->getId();
        $body= new \Google_Service_Sheets_ValueRange([
            'values' => [
                [
                    $uniqueVisitors
                ],[
                    $averageVisits
                ],[
                    $averagePages
                ]
            ]
        ]);
        $range = 'C3:C5';
        $sheetsService->spreadsheets_values->update($newId, $range,
            $body,[
                'valueInputOption' => 'USER_ENTERED'
            ]);

        $copiedFile2 = new \Google_Service_Drive_DriveFile();
        $copiedFile2->setName('AMP ROI on '.$url);
        $newFile2=$driveService->files->copy($slidesModelId, $copiedFile2);
        $newSlidesId=$newFile2->getId();
        $requests = array();
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => 'http://www.centre-presse.fr',
                    'matchCase' => true
                ),
                'replaceText' => $url
            )
        ));
        $batchUpdateRequest = new \Google_Service_Slides_BatchUpdatePresentationRequest(array(
            'requests' => $requests
        ));
        $slidesService->presentations->batchUpdate($newSlidesId, $batchUpdateRequest);
        die("ok");



    }
}