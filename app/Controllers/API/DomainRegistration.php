<?php

namespace App\Controllers\API;

class DomainRegistration{

    private $myKey = "3079601359d46e924bfbab85";
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
        
        $api = "https://www.namesilo.com/api/checkRegisterAvailability?version=1&type=xml&key=$this->myKey&domains=$data,$sld.net,$sld.org,$sld.com,$sld.biz,$sld.ai,$sld.me,$sld.tech";

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
}