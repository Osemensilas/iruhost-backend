<?php

namespace App\Controllers\API;
use Dotenv\Dotenv;
use App\Core\DB;
use PDO;

class DomainRegistration{

    protected $enomUserId;
    protected $enomApiToken;

    protected $pdo;

    public function __construct(){

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        if (!isset($_ENV['ENOM_USER_ID'])) {
            die("Dotenv failed to load. Path: " . __DIR__ . '/../../../');
        }

        $this->enomUserId = $_ENV['ENOM_USER_ID'] ?? null;
        $this->enomApiToken = $_ENV['ENOM_USER_API_TOKEN'] ?? null;
        $this->pdo = DB::connection();

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

        $stmt = $this->pdo->prepare("SELECT * FROM tlds");
        $stmt->execute();

        $dotCom = null;
        $dotOrg = null;
        $dotNet = null;

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                switch ($row['tld']) {
                    case 'com':
                        $dotCom = $row;
                        break;
                    case 'org':
                        $dotOrg = $row;
                        break;
                    case 'net':
                        $dotNet = $row;
                        break;
                }
            }

            echo json_encode([
                'status' => 'success',
                'result' => $rows,
                'dotCom' => $dotCom ? $dotCom['registration'] : null,
                'dotOrg' => $dotOrg ? $dotOrg['registration'] : null,
                'dotNet' => $dotNet ? $dotNet['registration'] : null,
            ]);
        }
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

    public function getDomainLockStatus(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];

        $tld = substr($domain, strpos($domain, '.') + 1);
        $sld = substr($domain, 0, strpos($domain, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=GetDomainInfo&uid=$this->enomUserId&pw=$this->enomApiToken&sld=$sld&tld=$tld&responsetype=xml";
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $transferLock = (string) $xml->GetDomainInfo->services;

        print_r($transferLock);
    }

    public function changeOwnership () {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];
        $newEmail = $data['email'];

        if (empty($domain) || empty($newEmail)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$newEmail, 'user']);

        if (!$stmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'User do not exist']);
            return;
        }

        $row = $stmt->fetch();

        $sessionId = $_SESSION['user']['user_id'];
        $newUser = $row['user_id'];

        $domainStmt = $this->pdo->prepare("SELECT * FROM products WHERE domain = ?");
        $domainStmt->execute([$domain]);

        if (!$domainStmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Domain do not exist']);
            return;
        }

        $domainRow = $domainStmt->fetch();

        if ($_SESSION['user']['user_id'] != $domainRow['user_id']){
            echo json_encode(['status' => 'error', 'message' => 'Domain not yours']);
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE products SET user_id = ? WHERE domain = ?");
        $result = $stmt->execute([$newUser, $domain]);

        if ($result){
            echo json_encode([
                'status' => 'success',
                'message' => 'Ownership Changed Successfully'
            ]);
        }
    }

    public function domainManager(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];
        $newEmail = $data['email'];

        if (empty($domain) || empty($newEmail)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$newEmail, 'user']);

        if (!$stmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'User do not exist']);
            return;
        }

        $row = $stmt->fetch();

        $sessionId = $_SESSION['user']['user_id'];
        $newUser = $row['user_id'];

        $domainStmt = $this->pdo->prepare("SELECT * FROM products WHERE domain = ? AND domain = ?");
        $domainStmt->execute([$domain, $domain]);

        if (!$domainStmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Domain do not exist']);
            return;
        }

        $domainRow = $domainStmt->fetch();

        if ($_SESSION['user']['user_id'] != $domainRow['user_id']){
            echo json_encode(['status' => 'error', 'message' => 'You can not add manager']);
            return;
        }

        $productId = $domainRow['product_id'];
        $product = $domainRow['product'];
        $url = $domainRow['url'];
        $text = $domainRow['text'];
        $exp = $domainRow['expiry_date'];


        $stmtCheck = $this->pdo->prepare("SELECT * FROM manager WHERE domain = ? AND manager_id = ?");
        $stmtCheck->execute([$domain, $newUser]);

        if ($stmtCheck->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'User already a manager']);
            return;
        }

        $stmtAdd = $this->pdo->prepare("INSERT INTO `manager`(`user_id`, `product_id`, `manager_id`, `product`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?)");
        $addResult = $stmtAdd->execute([$sessionId, $productId, $newUser, $product, $domain, $url, $text, $exp]);

        if (!$addResult){
            echo json_encode(['status' => 'error', 'message' => 'Error adding manager']);
        }
        echo json_encode(['status' => 'success', 'message' => 'User now a manager']);
    }

    public function unlockDomain(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];

        $lockUrl = "https://resellertest.enom.com/interface.asp?command=setreglock&uid=resellid&pw=resellpw&sld=resellerdocs&tld=com&unlockregistrar=0&responsetype=xml";
        $unlockUrl = "https://resellertest.enom.com/interface.asp?command=setreglock&uid=resellid&pw=resellpw&sld=resellerdocs&tld=com&unlockregistrar=1&responsetype=xml";
        print_r($data);
    }
}