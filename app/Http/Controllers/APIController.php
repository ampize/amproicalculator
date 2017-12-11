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
        $email = $request->input("url", null);
    }

}