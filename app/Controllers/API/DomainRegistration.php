<?php

namespace App\Controllers\API;

class DomainRegistration{

    private $myKey;
    private $url;
    public function __construct(){
        $this->myKey = "3079601359d46e924bfbab85"; 
        $this->url = "https://www.namesilo.com";
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

        $tdl = substr($data, strpos($data, '.') + 1);
        $sld = substr($data, 0, strpos($data, '.'));
        
        $api = "$this->url/api/checkRegisterAvailability?version=1&type=xml&key=$this->myKey&domains=$data,$sld.net,$sld.org,$sld.com,$sld.biz,$sld.ai,$sld.me,$sld.tech";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        
         echo json_encode([
            'status' => 'success',
            'requested_domain' => $data,
            'response' => $response,
        ]);
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

        $api = "https://www.namesilo.com/api/checkRegisterAvailability?version=1&type=xml&key=$this->myKey&domains=$domainName";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        
         echo json_encode([
            'status' => 'success',
            'requested_domain' => $domainName,
            'response' => $response,
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

        $api = "https://www.namesilo.com/api/whoisInfo?version=1&type=xml&key=$this->myKey&domain=$domainName";

    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        
         echo json_encode([
            'status' => 'success',
            'requested_domain' => $domainName,
            'response' => $response,
        ]);
    }

    public function getDomainPrices(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $api = "https://www.namesilo.com/api/getPrices?version=1&type=xml&key=$this->myKey";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $data = [];

        $dotCom = $xml->reply->com;
        $dotOrg = $xml->reply->org;
        $dotNet = $xml->reply->net;

        if ($xml && isset($xml->reply)) {
            foreach ($xml->reply->children() as $tld) {
                $name = "." . $tld->getName();

                // Try attributes first
                $reg      = (string) $tld['registration'];
                $renew    = (string) $tld['renew'];
                $transfer = (string) $tld['transfer'];

                // If attributes are empty, try child nodes
                if ($reg === "" && isset($tld->registration)) {
                    $reg = (string) $tld->registration;
                }
                if ($renew === "" && isset($tld->renew)) {
                    $renew = (string) $tld->renew;
                }
                if ($transfer === "" && isset($tld->transfer)) {
                    $transfer = (string) $tld->transfer;
                }

                $data[] = [
                    "tld" => $name,
                    "registration" => $reg,
                    "renewal"      => $renew,
                    "transfer"     => $transfer,
                ];
            }
        }

        header("Content-Type: application/json");
        echo json_encode([
            "status" => "success", 
            "prices" => $data, 
            "dotcom" => $dotCom,
            "dotnet" => $dotNet,
            "dotorg" => $dotOrg
        ], JSON_PRETTY_PRINT);
    }
}