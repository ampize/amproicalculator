<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Goutte\Client as GoutteClient;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class APIController extends BaseController
{

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
        $client->setHeader('User-Agent', "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
        $crawler = $client->request('GET', 'https://www.similarweb.com/fr/website/'.$url);
        $averageVisits=$crawler->filter(".engagementInfo-valueNumber")->first()->text();
        $screenShotUrl=$crawler->filter(".stickyHeader-screenshot")->first()->attr("src");
        $averagePages=$crawler->filter(".engagementInfo-value .engagementInfo-valueNumber")->eq(2)->text();
        $multiplier=1;
        if(strpos($averageVisits,"M")!==false){
            $multiplier=1000000;
        } else if(strpos($averageVisits,"K")!==false){
            $multiplier=1000;
        }
        $averageVisits=floatval($averageVisits)*$multiplier;
        return response()->json([
            "averageVisits" => $averageVisits,
            "averagePagesPerVisit" => str_replace('.',',',$averagePages),
            "screenShotUrl" => $screenShotUrl,
        ]);
    }

    public function getReport(Request $request)
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
        $client->setHeader('User-Agent', "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
        $crawler = $client->request('GET', 'https://www.similarweb.com/fr/website/'.$url);
        $averageVisits=$crawler->filter(".engagementInfo-valueNumber")->first()->text();
        $screenShotUrl=$crawler->filter(".stickyHeader-screenshot")->first()->attr("src");
        $averagePages=$crawler->filter(".engagementInfo-value .engagementInfo-valueNumber")->eq(2)->text();

        if(empty($averageVisits)||empty($averagePages)||empty($screenShotUrl)){
            abort(500, "No data found on website");
        }
        $multiplier=1;
        if(strpos($averageVisits,"M")!==false){
            $multiplier=1000000;
        } else if(strpos($averageVisits,"K")!==false){
            $multiplier=1000;
        }
        $averageVisits=floatval($averageVisits)*$multiplier;

        $uniqueVisitors = intval($averageVisits/$averagePages);
        $averagePages=(string) $averagePages;
        $averageVisits=(string) $averageVisits;
        $uniqueVisitors=(string) $uniqueVisitors;
        $averagePages=str_replace('.',',',$averagePages);

        if (empty($uniqueVisitors)||empty($averageVisits)||empty($averagePages)||empty($url)) {
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
        $sheetsService->spreadsheets_values->update($spreadSheetModelId, $range,
            $body,[
                'valueInputOption' => 'USER_ENTERED'
            ]);

        $copiedFile = new \Google_Service_Drive_DriveFile();
        $copiedFile->setName('AMP ROI on '.$url);
        $newFile2=$driveService->files->copy($slidesModelId, $copiedFile);
        $newSlidesId=$newFile2->getId();
        $requests = array();
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{siteUrl}}',
                    'matchCase' => true
                ),
                'replaceText' => $url
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{date}}',
                    'matchCase' => true
                ),
                'replaceText' => date('F Y')
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{uniqueVisitors}}',
                    'matchCase' => true
                ),
                'replaceText' => $uniqueVisitors
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllShapesWithImage' => array(
                'imageUrl' => $screenShotUrl,
                'replaceMethod' => 'CENTER_CROP',
                'containsText' => array(
                    'text' => '{{site-screenshot}}',
                    'matchCase' => true
                )
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'refreshSheetsChart' => array(
                'objectId' => "g2a46cec464_0_2"
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'refreshSheetsChart' => array(
                'objectId' => "g2a46cec464_0_3"
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'refreshSheetsChart' => array(
                'objectId' => "g2c17572a23_0_14"
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'refreshSheetsChart' => array(
                'objectId' => "g2c17572a23_0_10"
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'refreshSheetsChart' => array(
                'objectId' => "g2c17572a23_0_15"
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'refreshSheetsChart' => array(
                'objectId' => "g2c17572a23_0_23"
            )
        ));
        $batchUpdateRequest = new \Google_Service_Slides_BatchUpdatePresentationRequest(array(
            'requests' => $requests
        ));
        $slidesService->presentations->batchUpdate($newSlidesId, $batchUpdateRequest);
        $export = $driveService->files->export($newSlidesId, 'application/pdf', array(
            'alt' => 'media'));
        $content = $export->getBody()->getContents();
        return response($content)
            ->withHeaders([
                'Content-Type' => 'application/pdf',
            ]);
    }

    public function testUpdate(Request $request)
    {
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
        $driveService = new \Google_Service_Drive($client);
        $slidesId="1ACrSlNHwX-S-wPG5NP-ZHjRDRR4etEdlUd-9f0AXRDo";
        $slides=$slidesService->presentations->get($slidesId);
        return response()->json($slides->toSimpleObject());
    }
}