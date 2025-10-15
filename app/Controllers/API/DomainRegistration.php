<?php

namespace App\Controllers\API;

class DomainRegistration{

    private $myKey;
    private $url;
    protected $enomUserId;
    protected $enomApiToken;
    public function __construct(){
        $this->myKey = "3079601359d46e924bfbab85"; 
        $this->url = "https://www.namesilo.com";
        $this->enomUserId = "osemen";
        $this->enomApiToken = "WMGTAYX54FS4WL4MWVIC4SMSHGCQWTWKTJKUE64R";
    }
    public function domainSearch(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}(?<!\-)\.)+[a-zA-Z]{2,}$/', $data)){
            echo json_encode([
                'status' => 'error',
                'requested_domain' => $data,
                'response' => 'Invalid domain name'
            ]);
            return;
        }

        $tdls = [substr($data, strpos($data, '.') + 1), 'com', 'org', 'net', 'xyz', 'io', 'co', 'ai', 'info', 'us', 'me'];
        $sld = substr($data, 0, strpos($data, '.'));
        $myCharge = 3;

        foreach($tdls as $tdl){
        
            $api = "https://reseller.enom.com/interface.asp?command=check&sld=$sld&tld=$tdl&uid=$this->enomUserId&pw=$this->enomApiToken&responsetype=xml&version=2&includeprice=1";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

            $xml = simplexml_load_string($response);

            $rrpCode = (int) $xml->Domains->Domain->RRPCode;
            $regPrice = (float) $xml->Domains->Domain->Prices->Registration + $myCharge;
            $renewPrice = (float) $xml->Domains->Domain->Prices->Renewal + $myCharge;
            $domain = (string) $xml->Domains->Domain->Name;

             echo json_encode([
                'status' => 'success',
                'rrpCode' => $rrpCode,
                'regPrice' => $regPrice,
                'renew' => $renewPrice,
                'domain' => $domain
            ]);
        }
    }

    public function singleSearch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}(?<!\-)\.)+[a-zA-Z]{2,}$/', $data['action'])){
            echo json_encode([
                'status' => 'error',
                'requested_domain' => $data['action'],
                'response' => 'Invalid domain name'
            ]);
            return;
        }

        $domainName = $data['action'];

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));
        $myCharge = 3;

        $api = "https://reseller.enom.com/interface.asp?command=check&sld=$sld&tld=$tdl&uid=$this->enomUserId&pw=$this->enomApiToken&responsetype=xml&version=2&includeprice=1";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $rrpCode = (int) $xml->Domains->Domain->RRPCode;
        $regPrice = (float) $xml->Domains->Domain->Prices->Registration + $myCharge;
        $renewPrice = (float) $xml->Domains->Domain->Prices->Renewal + $myCharge;
        
        echo json_encode([
            'status' => 'success',
            'requested_domain' => $domainName,
            'rrpCode' => $rrpCode,
            'regPrice' => $regPrice,
            'renewPrice' => $renewPrice
        ]);
    }

    public function existingCheck(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}(?<!\-)\.)+[a-zA-Z]{2,}$/', $data['action'])){
            echo json_encode([
                'status' => 'error',
                'requested_domain' => $data['action'],
                'response' => 'Invalid domain name'
            ]);
            return;
        }

        $domainName = $data['action'];

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $api = "https://reseller.enom.com/interface.asp?command=check&sld=$sld&tld=$tdl&uid=$this->enomUserId&pw=$this->enomApiToken&responsetype=xml";

    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $rrpCode = (int) $xml->RRPCode;
        
         echo json_encode([
            'status' => 'success',
            'requested_domain' => $domainName,
            'rrpCode' => $rrpCode,
        ]);
    }

    public function getDomainPrices(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $api = "https://resellert.enom.com/interface.asp?command=gettldlist&uid=$this->enomUserId&pw=$this->enomApiToken&responsetype=xml&version=2&includeprice=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        //$xml = simplexml_load_string($response);

        print_r($response);

        // $data = [];

        // $dotCom = $xml->reply->com;
        // $dotOrg = $xml->reply->org;
        // $dotNet = $xml->reply->net;

        // if ($xml && isset($xml->reply)) {
        //     foreach ($xml->reply->children() as $tld) {
        //         $name = "." . $tld->getName();

        //         // Try attributes first
        //         $reg      = (string) $tld['registration'];
        //         $renew    = (string) $tld['renew'];
        //         $transfer = (string) $tld['transfer'];

        //         // If attributes are empty, try child nodes
        //         if ($reg === "" && isset($tld->registration)) {
        //             $reg = (string) $tld->registration;
        //         }
        //         if ($renew === "" && isset($tld->renew)) {
        //             $renew = (string) $tld->renew;
        //         }
        //         if ($transfer === "" && isset($tld->transfer)) {
        //             $transfer = (string) $tld->transfer;
        //         }

        //         $data[] = [
        //             "tld" => $name,
        //             "registration" => $reg,
        //             "renewal"      => $renew,
        //             "transfer"     => $transfer,
        //         ];
        //     }
        // }

        // header("Content-Type: application/json");
        // echo json_encode([
        //     "status" => "success", 
        //     "prices" => $data, 
        //     "dotcom" => $dotCom,
        //     "dotnet" => $dotNet,
        //     "dotorg" => $dotOrg
        // ], JSON_PRETTY_PRINT);
    }
}