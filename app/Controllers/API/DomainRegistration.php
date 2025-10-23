<?php

namespace App\Controllers\API;
use Dotenv\Dotenv;

class DomainRegistration{
    protected $enomUserId;
    protected $enomApiToken;
    public function __construct(){

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->enomUserId = getenv('ENOM_USER_ID');;
        $this->enomApiToken = getenv('ENOM_USER_API_TOKEN');
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

    public function updateDns(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $dns1 = $data["nameserver1"];
        $dns2 = $data["nameserver2"];
        $dns3 = $data["nameserver3"];
        $dns4 = $data["nameserver4"];
        $domainName = $data["domain"];
        $provider =  $data["dnsProvide"];
        if ($provider === "iruhost"){
            $dns1 = $data["nameserver5"];
            $dns2 = $data["nameserver6"];
        }

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=ModifyNS&uid=$this->enomUserId&pw=$this->enomApiToken&sld=$sld&tld=$tdl&ns1=$dns1&ns2=$dns2&ns3=$dns3&ns4=$dns4&responsetype=xml";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $rrpCode = (int) $xml->RRPCode;
        $rrpText = (string) $xml->RRPText;

        if ($rrpCode === 200){
            echo json_encode([
                'msg' => 'success',
                'value' => $rrpText
            ]);
        }else{
            echo json_encode([
                'msg' => 'unsuccess',
                'value' => $rrpText
            ]);
        }
    }

    public function updateHost(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data["domain"];
        $type = $data["type"];
        $host = $data["host"];
        $value = $data["value"];
        $type2 = $data["type2"];
        $host2 = $data["host2"];
        $value2 = $data["value2"];

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=SetHosts&uid=$this->enomUserId&pw=$this->enomApiToken&SLD=$sld&TLD=$tdl&HostName1=$host&RecordType1=$type&Address1=$value&HostName2=$host2&RecordType2=$type2&Address2=$value2&&responsetype=xml";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $errorCount = (int) $xml->ErrCount;

        if ($errorCount === 0){
            echo json_encode([
                'msg' => 'success',
                'value' => $errorCount
            ]);
        }
    }

    public function getHost(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data["domain"];

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=GetHosts&uid=$this->enomUserId&pw=$this->enomApiToken&SLD=$sld&TLD=$tdl&ResponseType=XML";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        if (isset($xml->host)) {
            foreach ($xml->host as $host) {
                $name = (string) $host->name;
                $type = (string) $host->type;
                $address = (string) $host->address;

                $hostRecords[] = [
                    'name' => $name,
                    'type' => $type,
                    'address' => $address
                ];
            }

            echo json_encode([
                "status" => "success",
                "response" => $hostRecords
            ]);
        }
    }

    public function getDomainDetails(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data["domain"] ?? null;

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=GetDomainInfo&uid=$this->enomUserId&pw=$this->enomApiToken&SLD=$sld&TLD=$tdl&responsetype=xml";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $expiration = (string) $xml->GetDomainInfo->status->expiration;
        $registration = (string) $xml->RequestDateTime;
        
        $expirationDate = explode(' ', $expiration)[0];
        $registrationDate = explode(' ', $registration)[0];

        $currentDate = date('m/d/Y');

        if ($currentDate >= $expirationDate) {
            $status = "Active";
        } elseif ($currentDate == $expirationDate) {
            $status = "Expired";
        } else {
            $status = "Expired";
        }

        $results = [
            'expiration' => $expirationDate,
            'registration' => $registrationDate,
            'status' => $status
        ];
        
        echo json_encode([
            'msg' => 'success',
            'value' => $results
        ]);
    }

    public function test(){
        $url = "https://reseller.enom.com/interface.asp?command=GetPOPBundleList&uid=osemen&pw=WMGTAYX54FS4WL4MWVIC4SMSHGCQWTWKTJKUE64R&responsetype=xml";
    }
}