<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;

class CallFlutter {

    protected $pdo;
    protected $secretKey;
    protected $userId;
    protected $clientId;

    public function __construct(){
        $this->pdo = DB::connection();
        $this->secretKey = "FLWSECK_TEST-76fca9105670eb0ded6852bc4785f25b-X";
        $this->userId = $_SESSION['user']['user_id'];
        $this->clientId = "200642152";
    }

    public function FlutterwaveFirstCall(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['cardName']) || empty($data['cardNum']) || empty($data['expDate']) || empty($data['cvv'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            return;
        }
        
        if (!preg_match('/^\d{13,19}$/', $data['cardNum'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid card number']);
            return;
        }
        // Validate expiry date format (MM/YY)
        if (!preg_match('/^\d{2}\/\d{2}$/', $data['expDate'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid expiry date format (MM/YY)']);
            return;
        }
        // Validate CVV (3-4 digits)
        if (!preg_match('/^\d{3,4}$/', $data['cvv'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid CVV']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            return;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $cardName = $data['cardName'];
        $cardNum = $data['cardNum'];
        $cardExp = $data['expDate'];
        $cardCvv = $data['cvv'];
        $address = $data['address'];
        $city = $data['city'];
        $state = $data['state'];
        $country = $data['country'];
        $zip = $data['postalCode'];
        $saveCard = $data['saveCard'];
        $email = $row['email'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            return;
        }

        $parts = preg_split('/\s+/', trim($cardName));
        $count = count($parts);

        $first = "";
        $middle = "";
        $last = "";

        if ($count === 1) {
            $first = $parts[0];
        } elseif ($count === 2) {
            $first = $parts[0];
            $last  = $parts[1];
        } elseif ($count >= 3) {
            $first  = $parts[0];
            $last   = $parts[$count - 1];
            // everything in between becomes middle name(s)
            $middle = implode(" ", array_slice($parts, 1, $count - 2));
        }

        $customerUrl = "https://api.flutterwave.cloud/developersandbox/customers";

        $customerData = [
            "address" => [
                "city" => $city,
                "country" => $country,
                "line1" => $address,
                "line2" => "",
                "postal_code" => $zip,
                "state" => $state
            ],
            "name" => [
                "first" => $first,
                "middle" => $middle,
                "last" => $last
            ],
            "email" => $email,
        ];

        $traceId = uniqid();
        $idempotencyKey = uniqid();

        $ch = curl_init($customerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->secretKey}", // Directly use secret key
            "Content-Type: application/json",
            "X-Trace-Id: {$traceId}",
            "X-Idempotency-Key: {$idempotencyKey}"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($customerData));
       
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        }

        print_r($response);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "HTTP CODE: " . $httpcode . "\n";
        print_r(json_decode($response, true));

    }
}