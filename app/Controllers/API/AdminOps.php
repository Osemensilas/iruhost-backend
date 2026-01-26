<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;

class AdminOps{
    protected $adminId;
    protected $pdo;

    public function __construct(){
        $this->adminId = $_SESSION['admin']['user_id'];
        $this->pdo =  DB::connection();
    }

    public function addBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $category = htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars($_POST['author'] ?? '', ENT_QUOTES, 'UTF-8');
        $allowedTags = '<p><br><b><strong><i><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><a>';
        $content = strip_tags($_POST['content'] ?? '', $allowedTags);
        $image = null;

        $blogId = uniqid("blog_");

        $image = null;

        if (!$image) {
            $image = '';
        }

        if (empty($category) || empty($title) || empty($author) || empty($content) || empty($_FILES['image']['name'])){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if ($_FILES['image']['name']) {
            $uploadDir = __DIR__ . "/../../../public/uploads/";
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $filename = time() . "_" . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // store relative path (backend will serve it later)
                $image = $filename;
            } else {
                var_dump($uploadDir);
                var_dump($targetFile);
                var_dump($_FILES['image']);
                echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
                return;
            }
        }

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE title = ?");
        $stmt->execute([$title]);

        if ($stmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Blog already exist']);
            return;
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

        $stmt = $this->pdo->prepare("INSERT INTO `blogs`(`blog_id`, `title`, `slug`, `content`, `image`, `writer`, `category`) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$blogId, $title, $slug, $content, $image, $author, $category]);

        echo json_encode(['status' => 'success', 'message' => 'Blog added successfully']);
    }

    public function getChats(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT c.*
            FROM chats c
            INNER JOIN (
                SELECT user_id, MAX(id) AS last_id
                FROM chats
                WHERE reciever_id = 'admin'
                GROUP BY user_id
            ) latest ON c.id = latest.last_id
            ORDER BY c.id DESC");
        $stmt->execute();
        $rows = '';

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row){
 
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$row['user_id']]);

                if ($stmt->rowCount() > 0){
                    $user = $stmt->fetch();

                    $userChats[] = [
                        'chat' => $row,
                        'name' => $user['name']
                    ]; 
                }
            }
            echo json_encode([
                'status' => 'success',
                'result' => $userChats
            ]);
        }
    }

    public function getVisitorChats(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT c.*
            FROM chats c
            INNER JOIN (
                SELECT user_id, MAX(id) AS last_id
                FROM chats
                WHERE reciever_id = 'admin'
                GROUP BY user_id
            ) latest ON c.id = latest.last_id
            ORDER BY c.id DESC");
        $stmt->execute();
        $rows = '';

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row){
 
                $stmt = $this->pdo->prepare("SELECT * FROM chats_reg WHERE user_id = ?");
                $stmt->execute([$row['user_id']]);

                if ($stmt->rowCount() > 0){
                    $user = $stmt->fetch();

                    $userChats[] = [
                        'chat' => $row,
                        'name' => $user['fullname']
                    ]; 
                }
            }
            echo json_encode([
                'status' => 'success',
                'result' => $userChats
            ]);
        }
    }

    public function getSupportTickets(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $getUnresolvedTickets = $this->pdo->prepare("SELECT * FROM `support` WHERE status = ?");
        $getUnresolvedTickets->execute(['unresolved']);

        if ($getUnresolvedTickets->rowCount() === 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'No unresolved ticket'
            ]);
            return;
        }

        $tickets = $getUnresolvedTickets->fetchAll(PDO::FETCH_ASSOC);

        foreach($tickets as $ticket){
            $getMessageStat = $this->pdo->prepare("SELECT * FROM `support_chats` WHERE ticket_id = ? AND status = ?");
            $getMessageStat->execute([$ticket['ticket_id'], 'not opened']);

            if ($getMessageStat->rowCount() > 0){
                $messageRow = $getMessageStat->fetchAll(PDO::FETCH_ASSOC);

                if ($messageRow['status'] === 'opened'){
                    $ticket['new_message'] = false;
                } else {
                    $ticket['new_message'] = true;
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'result' => $tickets
        ]);
    }

    public function getChat(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $clientId = $_GET['user'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM `chats` WHERE user_id = ? OR reciever_id = ? ORDER BY id");
        $stmt->execute([$clientId, $clientId]);

        if ($stmt->rowCount() > 0){
            $userChat = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($userChat);
        }
    }

    public function sendChat() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $recieverId = $data['reciever'];
        $message = $data["msg"];

        $stmt = $this->pdo->prepare("INSERT INTO `chats`(`user_id`, `reciever_id`, `message`, `status`, `image`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute(['admin', $recieverId, $message, 'new', '']);

        if ($result){
            echo json_encode([
                'status' => 'success',
                'messgae' => 'message sent'
            ]);
        }
    }

    public function updateChats() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $user = $data["user"];

        echo $user;

        $stmt = $this->pdo->prepare("UPDATE chats SET status = ? WHERE user_id = ?");
        $result = $stmt->execute(['', $user]);

        if ($result){
            echo json_encode([
                "status" => "Updated"
            ]);
        }
    }

    public function updateMigrations(){
    // --- Security: Require a key from query string ---
        $key = $_GET['key'] ?? '';
        $envKey = $_ENV['APP_KEY'] ?? 'create1p'; // fallback if .env missing

        if ($key !== $envKey) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
            return;
        }

        // --- Path setup ---
        $phpPath = '/usr/local/bin/php'; // cPanel PHP CLI path (usually this works)
        $migrateScript = __DIR__ . '/../../../commands/migrate.php';
        $logDir = __DIR__ . '/../../../storage';

        if (!file_exists($migrateScript)) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Migration script not found'
            ]);
            return;
        }

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // --- Execute the migrations ---
        ob_start(); // start capturing output
        include $migrateScript;
        $output = ob_get_clean(); // get everything the script printed

        // --- Log the result ---
        $logFile = $logDir . '/migrate.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . $output . "\n\n", FILE_APPEND);

        // --- Respond ---
        echo json_encode([
            'status' => 'success',
            'message' => 'Migrations executed successfully',
            'output' => trim($output)
        ]);
    }

    public function addTld(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $tld = $data['tld'];
        $reg = $data['registration'];
        $renew = $data['renewal'];
        $transfer = $data['transfer'];

        if (empty($tld) || empty($reg) || empty($renew) || empty($transfer)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z]+$/', $tld)){
            echo json_encode([
                'status' => 'error',
                'message' => 'TLD should be only text'
            ]);
            return;
        }

        if (!preg_match('/^[0-9||.]+$/', $reg)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All price should be float'
            ]);
            return;
        }

        if (!preg_match('/^[0-9||.]+$/', $renew)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All price should be float'
            ]);
            return;
        }

        if (!preg_match('/^[0-9||.]+$/',  $transfer)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All price should be float'
            ]);
            return;
        }

        $stmtCheck = $this->pdo->prepare("SELECT * FROM tlds WHERE tld = ?");
        $stmtCheck->execute([$tld]);

        if ($stmtCheck->rowCount() > 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'TLD already exist'
            ]);
            return;
        }

        $tldId = uniqid('tld_');

        $stmtInsert = $this->pdo->prepare("INSERT INTO `tlds`(`tld_id`, `tld`, `registration`, `renewal`, `transfer`) VALUES (?,?,?,?,?)");
        $insertResult = $stmtInsert->execute([$tldId, $tld, $reg, $renew, $transfer]);

        if (!$insertResult){
            echo json_encode([
                'status' => 'error',
                'message' => 'Error occured'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'TLD added successfully'
        ]);
    }

    public function adminTest(){
        
    }
}