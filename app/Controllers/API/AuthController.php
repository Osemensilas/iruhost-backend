<?php

namespace App\Controllers\API;

require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Resend;

use App\Core\DB;
use Dotenv\Dotenv;

class AuthController{

    protected $pdo;
    protected $emailHost;
    protected $emailPassword;
    protected $emailEnct;
    protected $emailPort;
    protected $emailUsername;
    protected $resend;
    protected $resendApiCode;

    public function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->emailHost = $_ENV['MAIL_HOST'] ?? null;
        $this->emailUsername = $_ENV['MAIL_USERNAME'] ?? null;
        $this->emailPassword = $_ENV['MAIL_PASSWORD'] ?? null;
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;

        $this->resend = Resend::client('re_FVsN1EMi_HfYQBxpWw88paP1WH53ctZSh');
    }

    public function register(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $name = $data['fullname'] ?? null;
        $email = $data['email'] ?? null;
        $password1 = $data['password1'] ?? null;
        $password2 = $data['password2'] ?? null;
        $role = 'user';
        $permission = 'none';

        if (empty($name) || empty($email) || empty($password1) || empty($password2)) {
            //http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z|| ]+$/', $name)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid name'
            ]);
            return;
        }

        if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]);
            return;
        }

        if (strlen($password1) < 8){
            echo json_encode([
                'status' => 'error',
                'message' => 'Password should be at least 8 characters'
            ]);
            return;
        }

        if (!preg_match('/[A-Z]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one uppercase'
            ]);
            return;
        }
        if (!preg_match('/[a-z]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one lowercase'
            ]);
            return;
        }
        if (!preg_match('/[0-9]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one number'
            ]);
            return;
        }
        if (!preg_match('/[\W]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one special character'
            ]);
            return;
        }

        if ($password2 != $password1){
            echo json_encode([
                'status' => 'error',
                'message' => 'Passwords do not match'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);

        if ($stmt->fetch()){
            echo json_encode([
                'status' => 'error',
                'message' => 'Email already exist'
            ]);
            return;
        }

        $userId = uniqid("iru_");
        $password = password_hash($password1, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("INSERT INTO `users`(`user_id`, `role`, `permission`, `name`, `email`, `password`) 
        VALUES (?, ?, ?, ?, ?, ?)");

        try{

            $stmt->execute([$userId, $role, $permission, $name, $email, $password]);

            $_SESSION['user'] = [
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
            ];

            $userSession = $_SESSION['user'];
            $this->checkCart($userSession);
            $this->checkChat($userSession);

            session_regenerate_id(true);

            $balance = 0;
            $userId = $_SESSION['user']['user_id'];
            
            $stmt = $this->pdo->prepare("INSERT INTO `account_balance`(`user_id`, `balance`) VALUES (?,?)");
            $stmt->execute([$userId, $balance]);

            echo json_encode([
                'status' => 'success',
                'message' => 'successful'
            ]);

        }catch(Exception $err){

            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);

        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $pdo = DB::connection();

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if (!$stmt->rowCount() > 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'User do not exist'
            ]);
            return;
        }
        
        $rows = $stmt->fetch();

        if (!password_verify($password, $rows['password'])){
            echo json_encode([
                'status' => 'error',
                'message' => 'Wrong password'
            ]);
            return;
        }
        
        $_SESSION['user'] = [
            'user_id' => $rows['user_id'],
            'name' => $rows['name'],
            'email' => $email,
        ];

        $userSession = $_SESSION['user'];

        session_regenerate_id(true);
        $this->checkCart($userSession);
        $this->checkChat($userSession);

        echo json_encode([
            'status' => 'success',
            'message' => 'successful'
        ]);
    }

    public function forgetPassword(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $email = strtolower($data['email']);

        if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if (!$stmt->rowCount() > 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'User do not exist'
            ]);
            return;
        }

        $rows = $stmt->fetch();

        $name = $rows['name'];

        $randomCode = rand(100000, 999999);

        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $passwordCode = $this->sendPasswordCode($name, $randomCode, $email);
        $codeStatus = $passwordCode['status'] ?? 'unknown';
        $codeMessage = $passwordCode['msg'] ?? 'unknown';

        if ($codeStatus === "successful"){
            $stmt = $this->pdo->prepare("SELECT * FROM `forget_password` WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0){
                $stmt = $this->pdo->prepare("UPDATE forget_password SET code = ? WHERE email = ?");
                $result = $stmt->execute([$expiresAt, $email]);

                if ($result){
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Message Sent Successfullly'
                    ]);
                }
            }else{
                $stmt = $this->pdo->prepare("INSERT INTO `forget_password`(`email`, `code`) VALUES (?,?)");
                $result = $stmt->execute([$email, $expiresAt]);

                if ($result){
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Message Sent Successfullly'
                    ]);
                }
            }
        }else{
            echo json_encode([
                'status' => 'error',
                'message' => $codeMessage
            ]);
        }
    }

    private function sendPasswordCode($name, $randomCode, $email){
        try {
            $result = $this->resend->emails->send([
                'from' => 'Your App <contact@iruhost.com>',
                'to' => [$email],
                'subject' => 'Password Reset Code',
                'html' => "<p>Hello {$name},</p><p>Your reset code is <strong>{$randomCode}</strong>.</p>"
            ]);

            return [
                'status' => 'successful',
                'msg' => $result
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'msg' => $e->getMessage()
            ];
        }
    }

    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'];
        $password = $data['formData']['password'];
        $confirmPassword = $data['formData']['confirmPassword'];

        if($password === "" || $confirmPassword === "" || $email === ""){
            echo json_encode(['status' => 'error', 'message' => 'All field rquired']);
            return;
        }

        if (strlen($password) < 8){
            echo json_encode([
                'status' => 'error',
                'message' => 'Password should be at least 8 characters'
            ]);
            return;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one uppercase'
            ]);
            return;
        }
        if (!preg_match('/[a-z]/', $password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one lowercase'
            ]);
            return;
        }
        if (!preg_match('/[0-9]/', $password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one number'
            ]);
            return;
        }
        if (!preg_match('/[\W]/', $password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one special character'
            ]);
            return;
        }

        if ($confirmPassword != $password){
            echo json_encode([
                'status' => 'error',
                'message' => 'Passwords do not match'
            ]);
            return;
        }

        $enctPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$enctPassword, $email]);

        if ($result){
            echo json_encode([
                'status' => 'success',
                'message' => 'Passwords updated'
            ]);
        }
    }

    public function passResetCode(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $pdo = DB::connection();
        
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'];
        $code = $data['code'];

        if ($code === '' || $email === ''){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM forget_password WHERE email = ? AND code = ?");
        $stmt->execute([$email, $code]);

        if ($stmt->rowCount() > 0){
            echo json_encode([
                'status' => 'success',
                'message' => 'code correct'
            ]);
        }else{
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid reset code'
            ]);
        }
    }

    private function checkCart($userSession){
        $userId = $_SESSION['user']['user_id'];
        $guestId = $_SESSION['guest']['id'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$guestId]);

        if ($stmt->rowCount() > 0){
            $stmt = $this->pdo->prepare("UPDATE cart SET user_id = ? WHERE user_id = ?");
            $stmt->execute([$userId, $guestId]);
        }
    }

    private function checkChat($userSession){
        $userId = $_SESSION['user']['user_id'];
        $guestId = $_SESSION['guest']['id'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM chats WHERE user_id = ?");
        $stmt->execute([$guestId]);

        if ($stmt->rowCount() > 0){
            $stmt = $this->pdo->prepare("UPDATE chats SET user_id = ? WHERE user_id = ?");
            $stmt->execute([$userId, $guestId]);
        }
    }

    public function adminLogin(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($email) || empty($password)) {
            //http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? OR user_id = ?");
        $stmt->execute([$email, $email]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            if($row['role'] === 'admin'){
                if (!password_verify($password, $row['password'])){
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Incorrect password'
                    ]);
                    return;
                }else{
                    $_SESSION['admin'] = [
                        'user_id' => $row['user_id'],
                        'name' => $row['name'],
                        'email' => $email,
                    ];

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'valid admin'
                    ]);
                }
            }else{
                echo json_encode([
                    'status' => 'error',
                    'message' => 'You do not have permission'
                ]);
                return;
            }
        }
    }

    public function currency(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $currency = $data['currency'];
        $amount = $data['value'];

        if (!preg_match('/^[0-9||.]+$/', $data['value'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid currency value']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM currency WHERE currency = ?");
        $stmt->execute([$currency]);

        if ($stmt->rowCount() > 0){

            $row = $stmt->fetch();

            $stmt = $this->pdo->prepare("UPDATE `currency` SET `currency`=?,`value`=? WHERE currency = ?"); 
            $stmt->execute([$currency, $amount, $currency]);


            echo json_encode([
                'status' => 'success',
                'message' => 'Currency value Updated'
            ]);
            return;
        }
        $stmt = $this->pdo->prepare("INSERT INTO `currency`(`currency`, `value`) VALUES (?, ?)");
        try{
            $stmt->execute([$currency, $amount]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Currency value added'
            ]);
        }catch(Exception $err){

            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function getNaira(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $currency = 'naira';

        $stmt = $this->pdo->prepare("SELECT * FROM currency WHERE currency = ?");
        $stmt->execute([$currency]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            echo json_encode([
                'status' => 'success',
                'value' => $row['value']
            ]);
        }
    }

    public function addWeb(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }
        
        $category = htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8');
        $price = htmlspecialchars($_POST['price'] ?? '', ENT_QUOTES, 'UTF-8');
        $webName = htmlspecialchars($_POST['webName'] ?? '', ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $image = null;

        if (!$image) {
            $image = '';
        }

        if (empty($category) || empty($price) || empty($webName) || empty($description) || empty($_FILES['image']['name'])){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if ($_FILES['image']['name']) {
            $uploadDir = __DIR__ . "../../../../public/uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = time() . "_" . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // store relative path (backend will serve it later)
                $image = $filename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
                return;
            }
        }

        $webId = uniqid("web_");

        $stmt = $this->pdo->prepare("INSERT INTO `websites`(`web_id`, `category`, `web_name`, `image`, `description`, `price`) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$webId, $category, $webName, $image, $description, $price]);

        echo json_encode([
            'status' => 'success',
            'message' => "Website added successfully"
        ]);
    }

    public function updateEmail(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data['email'];
        $password = $data['password'];
        $user = $_SESSION['user']['user_id'];

        if (!isset($user)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();
        }

        if (!password_verify($password, $row['password'])){
            echo json_encode([
                'status' => 'error',
                'message' => 'Wrong password'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE `users` SET `email`=? WHERE user_id = ?");
        $stmt->execute([$email, $user]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Updated Successfully'
        ]);
    }

    public function updateAddress(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $address1 = $data['address1'];
        $address2 = $data['address2'];
        $city = $data['city'];
        $state = $data['state'];
        $zip = $data['zip'];
        $country = $data['country'];
        $cCode = $data['cCode'];
        $phone = $data['phone'];
        $user = $_SESSION['user']['user_id'];

        if (!isset($user)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        if (empty($address1) || empty($city) || empty($state) || empty($zip) || empty($country) ||
        empty($cCode) || empty($phone)
        ){
            echo json_encode(['status' => 'error', 'message' => 'Fill all required field']);
            return;
        }

        if (!preg_match('/^[+][0-9]{1,3}$/', $cCode)){
            echo json_encode(['status' => 'error', 'message' => 'Ivalid country code']);
            return;
        }

        if (!preg_match('/^[0-9]{7,12}$/', $phone)){
            echo json_encode(['status' => 'error', 'message' => 'Ivalid phone']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `address` WHERE user_id = ?");
        $stmt->execute([$user]);

        if ($stmt->rowCount() > 0){
            $stmt = $this->pdo->prepare("UPDATE `address` SET `address1`=?,`address2`=?,`city`=?,`state`=?,`country`=?,`zip`=?,`cCode`=?,`phone`=? WHERE user_id = ?");
            $stmt->execute([$address1, $address2, $city, $state, $country, $zip, $cCode, $phone, $user]);
        
            echo json_encode(['status' => 'success', 'message' => 'Address updated']);
        }else{
            $stmt = $this->pdo->prepare("INSERT INTO `address`(`user_id`, `address1`, `address2`, `city`, `state`, `country`, `zip`, `cCode`, `phone`) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$user, $address1, $address2, $city, $state, $country, $zip, $cCode, $phone]);
        
            echo json_encode(['status' => 'success', 'message' => 'Address Added']);
        }
    }
}