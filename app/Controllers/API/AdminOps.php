<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
use PHPMailer\PHPMailer\Exception;
use Resend;
use Dotenv\Dotenv;

class AdminOps{
    protected $adminId;
    protected $pdo;
    protected $resend;
    protected $resendApiCode;
    protected $encryptionKey;
    protected $encryptionIV;
    public function __construct(){
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->adminId = $_SESSION['admin']['user_id'];
        $this->pdo =  DB::connection();
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        $this->resend = Resend::client($this->resendApiCode);
        $this->encryptionKey = hash('sha256', $_ENV['ENCRYPTION_KEY']);
        $this->encryptionIV = substr(hash('sha256', $_ENV['ENCRYPTION_IV']), 0, 16);
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

        foreach ($tickets as &$ticket) {

            // default
            $ticket['new_message'] = false;

            $stmt = $this->pdo->prepare(
                "SELECT 1 
                FROM support_chats 
                WHERE ticket_id = ? 
                AND status = ? 
                LIMIT 1"
            );
            $stmt->execute([$ticket['ticket_id'], 'not opened']);

            if ($stmt->rowCount() > 0) {
                $ticket['new_message'] = true;
            }
        }
        unset($ticket);

        echo json_encode([
            'status' => 'success',
            'result' => $tickets
        ]);
    }

    public function unresolvedTickets(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `support` WHERE status = ? AND ticket_id = ?");
        $stmt->execute(['unresolved', $_GET['ticket_id']]);

        if ($stmt->rowCount() > 0){
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'message' => $ticket
            ]);
        }
    }

    public function supportChats(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $ticketId = $_GET['ticket_id'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM `support_chats` WHERE ticket_id = ? ORDER BY id");
        $result = $stmt->execute([$ticketId]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'no message found'
            ]);
            return;
        }

        $rows = [];

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    public function postSupportChats(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $ticketId = htmlspecialchars($_POST['ticket_id'] ?? '', ENT_QUOTES, 'UTF-8');;
        $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $image = '';

        if (empty($ticketId) || empty($message)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "../../../../public/uploads/";

            $filename   = time() . "_" . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $filename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
                return;
            }
        }

        $userStmt = $this->pdo->prepare("SELECT * FROM `users` WHERE user_id = ? AND role = ?");
        $userStmt->execute([$this->adminId, 'admin']);

        if ($userStmt->rowCount() === 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found'
            ]);
            return;
        }

        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        $name = $userRow['name'] ?? 'User';
        $email = $userRow['email'] ?? '';

        $parts = preg_split('/\s+/', trim($name));

        $avatar = strtoupper(
            substr($parts[0], 0, 1) . 
            (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
        );

        $getUserStmt = $this->pdo->prepare("SELECT * FROM `support` WHERE ticket_id = ?");
        $getUserStmt->execute([$ticketId]);

        if ($getUserStmt->rowCount() === 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'Ticket not found'
            ]);
            return;
        }

        $ticketRow = $getUserStmt->fetch(PDO::FETCH_ASSOC);
        $userId = $ticketRow['user_id'];

        $stmtTickets = $this->pdo->prepare("INSERT INTO `support_chats`(`ticket_id`, `sender_id`, `sender`, `reciever_id`, `message`, `status`, `image`, `avatar`) VALUES (?,?,?,?,?,?,?,?)");
        $result = $stmtTickets->execute([$ticketId, $this->adminId, 'admin', $userId, $message, 'not opened', $image, $avatar]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'no message found'
            ]);
            return;
        }


        $this->sendEmail($message, $email, $name);

        echo json_encode([
            'status' => 'success',
            'message' => 'message sent'
        ]);
    }

    public function updateChatsStatus(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $ticketId = $_GET['ticket_id'];

        $checkStmt = $this->pdo->prepare("SELECT * FROM support_chats WHERE ticket_id = ? AND status = ?");
        $checkStmt->execute([$ticketId, 'not opened']);

        if ($checkStmt->rowCount() < 1){
            json_encode([
                'status' => 'no new message'
            ]);
            return;
        }

        $rows = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($rows as $row){
            if ($row['status'] === 'not opened'){
                $stmtUpdate = $this->pdo->prepare("UPDATE `support_chats` SET `status`= ? WHERE ticket_id = ?");
                $stmtUpdate->execute(['opened', $ticketId]);

                if ($stmtUpdate->rowCount() < 1){
                    return;
                }

                return;
            }
        }
    }

    public function closeSupportChat(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $ticketId = $data['ticket_id'];

        $stmtUpdate = $this->pdo->prepare("UPDATE `support` SET `status`= ? WHERE ticket_id = ?");
        $result = $stmtUpdate->execute(['resolved', $ticketId]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'status could not be updated'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'status',
            'message' => 'status updated successfully'
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

        $getReciver = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $getReciver->execute([$recieverId]);

        if ($getReciver->rowCount() < 1){
            echo json_encode(['status' => 'error', 'message' => 'Error fetching reciever']);
            return;
        }

        $recieverRow = $getReciver->fetch(PDO::FETCH_ASSOC);

        $recieverEmail = $recieverRow['email'];

        $stmt = $this->pdo->prepare("INSERT INTO `chats`(`user_id`, `reciever_id`, `message`, `status`, `image`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute(['admin', $recieverId, $message, 'new', '']);

        $this->sendMailToReciever($recieverEmail, $message);

        if ($result){
            echo json_encode([
                'status' => 'success',
                'messgae' => 'message sent'
            ]);
        }
    }

    private function sendMailToReciever($recieverEmail, $message){
        try {
            $this->resend->emails->send([
                'from' => 'IruHost <contact@iruhost.com>',
                'to' => [$recieverEmail],
                'subject' => 'New Message from IruHost',
                'html' => "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                
                        <p style='color: #333; line-height: 1.6;'>
                        {$message}
                        </p>

                        <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                        Thank you for contacting <strong>IruHost</strong>.<br>
                        Need help? Contact us at <a href='mailto:contact@iruhost.com' style='color:#007bff;'>contact@iruhost.com</a>
                        </p>
                    </div>
                </div>
                "
            ]);

        } catch (\Exception $e) {
            
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

    private function sendEmail($message, $email, $name){
         try {
            $this->resend->emails->send([
                'from' => 'IruHost <support@iruhost.com>',
                'to' => [$email, 'osemensilas@gmail.com'],
                'subject' => 'Support Ticket Message',
                'html' => "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                    
                    <p style='color: #333; line-height: 1.6;'>
                    Your support ticket with the below message has been received. our support team will get back to you as soon as possible.
                    </p>

                    <p style='color:#333; line-height:1.6;'>
                    <em>{$message}</em>
                    </p>

                    <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                    Thank you for choosing <strong>IruHost</strong>.<br>
                    Need help? Contact us at <a href='mailto:support@iruhost.com' style='color:#007bff;'>support@iruhost.com</a>
                    </p>
                </div>
                </div>
                "
            ]);
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
        }
    }

    public function addNewAdmin(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $email = htmlspecialchars($data['email']);
        $role = htmlspecialchars($data['role']);
        $firstname = htmlspecialchars($data['firstname']);
        $lastname = htmlspecialchars($data['lastname']);
        $password = $this->generateSecurePassword();
        $tempPassword = "Orange$101";
        $userId = uniqid("sup_");
        $permission = 'support';
        $name = $firstname . " " . $lastname;

        $encryptedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

        if (empty($email) || empty($role) || empty($firstname) || empty($lastname)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            return;
        }

        $getAdmin = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $getAdmin->execute([$email, $role]);

        if ($getAdmin->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Already Support']);
            return;
        }

        $addAdmin = $this->pdo->prepare("INSERT INTO `users`(`user_id`, `role`, `permission`, `name`, `email`, `password`) 
        VALUES (?, ?, ?, ?, ?, ?)");
        $addAdmin->execute([$userId, $role, $permission, $name, $email, $encryptedPassword]);

        $this->sendSupportMessage($email, $name, $password);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Admin added successfully'
        ]);
    }

    public function messageClients(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $message = htmlspecialchars($data['message']);
        $subject = htmlspecialchars($data['subject']);

        if (empty($message)){
            echo json_encode(['status' => 'error', 'message' => 'Message field is required']);
            return;
        }

        $this->sendAdMsg($message, $subject);
    }

    private function sendAdMsg($message, $subject){

        $getUsers = $this->pdo->prepare("SELECT * FROM users WHERE role = ?");
        $getUsers->execute(['user']);

        if ($getUsers->rowCount() > 0){
            $users = $getUsers->fetchAll(PDO::FETCH_ASSOC);

            foreach ($users as $user){
                $email = $user['email'];
                $name = $user['name'];

                echo json_encode([
                    'status' => 'success',
                    'message' => $message,
                    'subject' => $subject,
                    'email' => $email,
                    'name' => $name
                ]);
            }
        }
    }

    private function sendSupportMessage($email, $name, $password) {
        try {
            $this->resend->emails->send([
                'from' => 'Iruap Tech Studio Limited',
                'to' => $email,
                'subject' => 'Admin Account Creation',
                'html' => "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                    
                    <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>Admin Access Granted</h2>
                    
                    <p style='color: #333;'>Hello <strong>{$name}</strong>,</p>
                    
                    <p style='color: #333; line-height: 1.6;'>
                    You have been successfully added as an <strong>Administrator</strong> on the IruHost platform.
                    </p>

                    <p style='color: #333; line-height: 1.6;'>
                    As an admin, you now have access to manage platform content, users, and system-related activities based on the permissions assigned to your account.
                    </p>

                    <table cellpadding='8' cellspacing='0' style='width:100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr style='background-color:#f0f4ff;'>
                        <td style='width:35%; font-weight:bold; color:#333;'>Login URL:</td>
                        <td><a href='https://support.iruhost.com' style='color:#007bff; text-decoration:none;'>https://support.iruhost.com</a></td>
                    </tr>
                    <tr>
                        <td style='font-weight:bold; color:#333;'>Email:</td>
                        <td style='color:#555;'>{$email}</td>
                    </tr>
                    <tr style='background-color:#f0f4ff;'>
                        <td style='font-weight:bold; color:#333;'>Password:</td>
                        <td style='color:#555;'>{$password}</td>
                    </tr>
                    </table>
                    
                </div>
                </div>
                "
            ]);


        } catch (\Exception $e) {
            
        }
    }

    private function generateSecurePassword($length = 12){
        // Define character sets
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers   = '0123456789';
        $symbols   = '!@#$%^&*()-_=+<>?';

        // Combine all character sets
        $allChars = $uppercase . $lowercase . $numbers . $symbols;

        // Ensure password contains at least one of each type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the rest of the password length with random characters
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle to avoid predictable placement
        $password = str_shuffle($password);

        return $password;
    }
}