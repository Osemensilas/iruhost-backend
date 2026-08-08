<?php

namespace App\Controllers\API;
use App\Core\DB;
use Exception;

class PanelEmailOperations{

    private $pdo;
    private $userId;
    private $panelUser;

    public function __construct()
    {
        $this->pdo = DB::connection();
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->panelUser = $_SESSION['panel_user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
    }

    public function createEmailDb()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($this->panelUser)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $username = $data["username"] ?? "";
        $domain   = $data["domain"] ?? "";
        $password = $data["password"] ?? "";

        if (!$username || !$domain || !$password) {
            echo json_encode(["status" => false, "message" => "Required fields are missing"]);
            exit;
        }

        $email = $username . "@". $domain;

        $userId = $this->panelUser;

        $getDomain = $this->pdo->prepare("SELECT * FROM `user_domains` WHERE domain = ? AND user_id = ?");
        $getDomain->execute([$domain, $userId]);

        if (!$getDomain->rowCount() > 0){
            echo json_encode(["status" => 'error', "message" => "Domain do not exist"]);
            exit;
        }

        $domainRow = $getDomain->fetch();

        // Generate Dovecot-compatible password hash
        $salt = bin2hex(random_bytes(16));
        $hashedPassword = "{SHA512-CRYPT}" . crypt($password, '$6$rounds=5000$'.$salt.'$');

        $mailboxBase = __DIR__ . '/../../mail/vhosts';  // Adjusted relative to this PHP file

        // Full path for the new mailbox
        $mailboxPath = "$mailboxBase/$domain/$username";

        if (!is_dir($mailboxPath)) {
            mkdir($mailboxPath, 0775, true);
        }

        mkdir("$mailboxPath/cur", 0775, true);
        mkdir("$mailboxPath/new", 0775, true);
        mkdir("$mailboxPath/tmp", 0775, true);


        $checkMail = $this->pdo->prepare("SELECT * FROM `mailboxes_user` WHERE email = ?");
        $checkMail->execute([$email]);

        if ($checkMail->rowCount() > 0){
            echo json_encode(["status" => 'error', "message" => "Email already exist"]);
            exit;
        }

        // Step 4: Insert user
        $insert = $this->pdo->prepare("INSERT INTO mailboxes_user (email, domain_id, password) VALUES (?, ?, ?)");
        $insert->execute([$email, $domainRow["id"], $hashedPassword]);

        echo json_encode([
            "status" => "successful",
            "message" => "Mailbox created successfully",
            "email" => $email
        ]);
    }

    public function openUserDatabase(){

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($this->panelUser)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $databaseName = $_GET['panelId'] ?? null;
        $panelUserId = $this->panelUser;

        $stmt = $this->pdo->prepare("SELECT * FROM panel_users_database WHERE panel_id = ?");
        $stmt->execute([$panelUserId]);
        $data = $stmt->fetch();

        if ($data && strtotime($data['expires_at']) > time()) {
            $token = $data['pma_token'];
        } else {

            $token = bin2hex(random_bytes(32));

            $stmt = $this->pdo->prepare("
                UPDATE panel_users_database
                SET pma_token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                WHERE panel_id = ?
            ");
            $stmt->execute([$token, $panelUserId]);
        }

        $link = "http://localhost/irupanel/pma-signon.php?token=$token";

        echo json_encode([
            'status' => 'successful',
            'link' => $link
        ]);
    }

    public function createEmailAccount(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $username = $data["username"] ?? "";
        $domain   = $data["domain"] ?? "";
        $password = $data["password"] ?? "";
        $productId = $data["id"] ?? "";

        print_r($data);
    }
}