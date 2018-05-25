<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Goutte\Client as GoutteClient;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use \Mailjet\Resources;
use \Mailjet\Client as MailClient;

class APIController extends BaseController
{

    protected $client = null;

    protected function getClient()
    {
        if (!$this->client) {
            $client = new \Google_Client();
            $client->setApplicationName("AMPROICalculator");
            $client->setScopes(implode(' ', array(
                    \Google_Service_Sheets::SPREADSHEETS, \Google_Service_Slides::PRESENTATIONS)
            ));
            $client->setAuthConfig(realpath(__DIR__ . '/../../../client_secret.json'));
            $client->setAccessType('offline');
            $credentialsPath = realpath(__DIR__ . '/../../../credentials/credentials.json');
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
            $client->setAccessToken($accessToken);
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
            }
            $this->client = $client;
        }
        return $this->client;
    }

    protected function generateReport($url, $averagePages = null, $averageVisits = null, $uniqueVisitors = null)
    {
        if (empty($url)) {
            abort(400, "Missing required params");
        }
        $gClient = new GoutteClient();
        $gClient->followRedirects();
        $guzzleClient = new \GuzzleHttp\Client(array(
            'curl' => array(
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ),
        ));
        $gClient->setClient($guzzleClient);
        $gClient->setHeader('User-Agent', "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)");
        $crawler = $gClient->request('GET', 'https://www.similarweb.com/fr/website/' . $url);

        $sturl=$crawler->filter(".websiteHeader-screenImg");
        if($sturl->count()>0&&$sturl->first()->attr("src")){
            $screenShotUrl=$sturl->first()->attr("src");
        } else {
            $screenShotUrl="http://via.placeholder.com/240x140";
        }

        if (empty($uniqueVisitors) || empty($averageVisits) || empty($averagePages)) {
            $averageVisits = $crawler->filter(".engagementInfo-valueNumber")->first()->text();
            $averagePages = $crawler->filter(".engagementInfo-value .engagementInfo-valueNumber")->eq(2)->text();

            if (empty($averageVisits) || empty($averagePages)) {
                abort(500, "No data found on website");
            }
            $multiplier = 1;
            if (strpos($averageVisits, "M") !== false) {
                $multiplier = 1000000;
            } else if (strpos($averageVisits, "K") !== false) {
                $multiplier = 1000;
            }
            $averageVisits = floatval($averageVisits) * $multiplier;

            $uniqueVisitors = intval($averageVisits / $averagePages);
            $averagePages = (string)$averagePages;
            $averageVisits = (string)$averageVisits;
            $uniqueVisitors = (string)$uniqueVisitors;
        }
        if (empty($uniqueVisitors) || empty($averageVisits) || empty($averagePages)) {
            abort(400, "Missing required params");
        }
        $client = $this->getClient();
        $sheetsService = new \Google_Service_Sheets($client);
        $slidesService = new \Google_Service_Slides($client);
        $spreadSheetModelId = "1HqEblVk-6pCX8caa90zL6uVOYDepC792Mh76TI1OcXA";
        $slidesModelId = "1ACrSlNHwX-S-wPG5NP-ZHjRDRR4etEdlUd-9f0AXRDo";
        $driveService = new \Google_Service_Drive($client);
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => [
                [
                    $uniqueVisitors
                ], [
                    $averageVisits
                ], [
                    $averagePages
                ]
            ]
        ]);
        $range = 'C3:C5';
        $sheetsService->spreadsheets_values->update($spreadSheetModelId, $range,
            $body, [
                'valueInputOption' => 'USER_ENTERED'
            ]);

        $copiedFile = new \Google_Service_Drive_DriveFile();
        $copiedFile->setName('AMP ROI on ' . $url);
        $newFile2 = $driveService->files->copy($slidesModelId, $copiedFile);
        $newSlidesId = $newFile2->getId();
        $rezRanges = [
            "'Cash Flow Table (Risk-Adjusted)'!G5",
            "'Cash Flow Table (Risk-Adjusted)'!G32",
            "'Cash Flow Table (Risk-Adjusted)'!G31",
            "'Profit Growth From Increased Conversion Rate'!E6",
            "'Profit Growth From Increased AMP Site Traffic'!E7",
            "'Metrics'!C3",
            "'Metrics'!C6",
        ];
        $newValues = $sheetsService->spreadsheets_values->batchGet($spreadSheetModelId, ["ranges" => $rezRanges]);
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
                'replaceText' => date("F", strtotime("last month"))
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{roi1}}',
                    'matchCase' => true
                ),
                'replaceText' => $newValues->getValueRanges()[0]->getValues()[0][0]
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{roi2}}',
                    'matchCase' => true
                ),
                'replaceText' => $newValues->getValueRanges()[1]->getValues()[0][0]
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{benefitsPV}}',
                    'matchCase' => true
                ),
                'replaceText' => $newValues->getValueRanges()[2]->getValues()[0][0]
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{PV1}}',
                    'matchCase' => true
                ),
                'replaceText' => $newValues->getValueRanges()[3]->getValues()[0][0]
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{PV2}}',
                    'matchCase' => true
                ),
                'replaceText' => $newValues->getValueRanges()[4]->getValues()[0][0]
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{uniqueVisitors}}',
                    'matchCase' => true
                ),
                'replaceText' => $newValues->getValueRanges()[5]->getValues()[0][0]
            )
        ));
        $requests[] = new \Google_Service_Slides_Request(array(
            'replaceAllText' => array(
                'containsText' => array(
                    'text' => '{{percentageAmp}}',
                    'matchCase' => true
                ),
                'replaceText' => $newValues->getValueRanges()[6]->getValues()[0][0]
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
        $pub = $driveService->revisions->update($newSlidesId, 1, new \Google_Service_Drive_Revision(["published" => true, "publishAuto" => true]));
        return $newSlidesId;
    }

    protected function hasReport($url)
    {
        $client = $this->getClient();
        $driveService = new \Google_Service_Drive($client);
        $files = $driveService->files->listFiles([
            "q" => "name='AMP ROI on " . $url . "'"
        ]);
        if (!empty($files->files) && !empty($files->files[0])) {
            return $files->files[0]->id;
        }
        return null;
    }

    protected function deleteReport($id)
    {
        $client = $this->getClient();
        $driveService = new \Google_Service_Drive($client);
        $driveService->files->delete($id);
    }


    public function getReport(Request $request)
    {
        $url = $request->input("url", null);
        $averagePages = $request->input("pageviewsPerSession", null);
        $averageVisits = $request->input("pageViews", null);
        $uniqueVisitors = $request->input("users", null);
        if (empty($url)) {
            abort(400, "Missing required params");
        }
        $reportId = $this->hasReport($url);
        if ($reportId) {
            $this->deleteReport($reportId);
        }
        $newEeportId = $this->generateReport($url, $averagePages, $averageVisits, $uniqueVisitors);
        return response()->json([
            "id" => $newEeportId,
            "success" => true

        ]);
    }

    public function sendReportEmail(Request $request)
    {
        $url = $request->input("url", null);
        $email = $request->input("email", null);
        if (empty($url)||empty($email)) {
            abort(400, "Missing required params");
        }
        $reportId = $this->hasReport($url);
        if (!$reportId) {
            abort(404, "Report not found");
        }
        $publicKey=getenv("MJ_APIKEY_PUBLIC");
        $privateKey=getenv("MJ_APIKEY_PRIVATE");
        if (empty($publicKey)||empty($privateKey)) {
            abort(500, "Mailer not configured");
        }
        $mailJet=new MailClient($publicKey, $privateKey);
        $body = [
            'FromEmail' => 'info@ampize.me',
            'FromName' => 'Hanna at AMPize',
            'Subject' => 'AMP ROI Report for '.$url,
            'Text-part' => '
                Hi
                Thank you for trying out our ROI calculator, we’ve attached your PDF to this email. Impressed by the results? We’d like to offer you a completely free, no strings-attached trial of AMPize for 1 month, so you can see how easy it is to use, and start reaping the rewards of AMP technology right away.
                The approximate set-up time is 30 minutes, and will start working immediately, giving you fast load-time, instant SEO, and mobile-friendly visibility for your readers. 
                You can download your report at https://'.$_SERVER["HTTP_HOST"].'/api/dl-roi-report?url='.$url.'
                If you have any questions, please simply respond to this email, or for personalized support, on my calendar at https://app.hubspot.com/meetings/hanna-johnson and I will be happy to help you! 
                Kind regards,
                Hanna Johnson
            ',
            'Html-part' => '
            <!doctype html>
                <html>
                <head>
                    <title>AMP ROI Report for '.$url.'</title>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                </head>
                <body>
                    <p>Hi</p>
                    <p>Thank you for trying out our ROI calculator, we’ve attached your PDF to this email. Impressed by the results? We’d like to offer you a completely free, no strings-attached trial of AMPize for 1 month, so you can see how easy it is to use, and start reaping the rewards of AMP technology right away. </p>
                    <p>The approximate set-up time is 30 minutes, and will start working immediately, giving you fast load-time, instant SEO, and mobile-friendly visibility for your readers. </p>
                    <p>Click <a href="https://'.$_SERVER["HTTP_HOST"].'/api/dl-roi-report?url='.$url.'">here</a> to download your report.</p>
                    <p>If you have any questions, please simply respond to this email, or for personalized support, <a href="https://app.hubspot.com/meetings/hanna-johnson">pick a time</a> on my calendar and I will be happy to help you! </p>
                    <p>Kind regards,</p>
                    
                    <p>Hanna Johnson</p>
                </body>
            </html>
            ',
            'Recipients' => [['Email' => $email]]
        ];
        $mailJet->post(Resources::$Email, ['body' => $body]);
        return response()->json([
            "success" => true
        ]);
    }

    public function downloadReportPDF(Request $request)
    {
        $url = $request->input("url", null);
        if (empty($url)) {
            abort(400, "Missing required params");
        }
        $reportId = $this->hasReport($url);
        if (!$reportId) {
            abort(404, "Report not found");
        }
        $client = $this->getClient();
        $driveService = new \Google_Service_Drive($client);
        $export = $driveService->files->export($reportId, 'application/pdf', array(
            'alt' => 'media'));
        $content = $export->getBody()->getContents();
        return response($content)
            ->withHeaders([
                'Content-Type' => 'application/pdf',
            ]);
    }


    public function testUpdate(Request $request)
    {
        $url = $request->input("url", null);
        if (empty($url)) {
            abort(400, "Missing required params");
        }
        var_dump($this->hasReport($url));
        die("test");

    }
}