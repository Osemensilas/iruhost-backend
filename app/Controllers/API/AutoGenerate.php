<?php

namespace App\Controllers\API;

use App\Core\DB;
use PDO;
use Dotenv\Dotenv;
use Resend;
use DateTime;

class AutoGenerate
{
    protected $pdo;
    protected $resend;
    protected $resendApiCode;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->pdo = DB::connection();
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        $this->resend = Resend::client($this->resendApiCode);
    }

    public function getExpiringDomain()
    {
        $thresholdDays = 30;
        $today = new DateTime(date('Y-m-d'));

        // fetch only domains
        $getDomains = $this->pdo->prepare("SELECT * FROM product WHERE product_name = ?");
        $getDomains->execute(['domain']);

        $domains = $getDomains->fetchAll(PDO::FETCH_ASSOC);

        if (count($domains) === 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'No domain available'
            ]);
            return;
        }

        foreach ($domains as $domain) {

            // Ensure expiry_date exists
            if (!isset($domain['expiry_date']) || empty($domain['expiry_date'])) {
                continue;
            }

            $expiryDate = new DateTime(date('Y-m-d', strtotime($domain['expiry_date'])));


            // Days remaining
            $interval = $today->diff($expiryDate);
            $daysLeft = (int)$interval->format("%r%a");

            if ($daysLeft <= $thresholdDays && $daysLeft >= 0) {
                $this->generateExpiryMessage(
                    $domain['product_name'],
                    $daysLeft,
                    $domain['expiry_date'],
                    $domain['user_id']
                );
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Notifications generated'
        ]);
    }

    private function generateExpiryMessage($productName, $daysLeft, $expiryDate, $userId)
    {
        $user = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $user->execute([$userId]);

        $userRow = $user->fetch();

        $email = $userRow['email'];
        $name = $userRow['name'];

        if ($daysLeft == 0) {
            try {
                $this->resend->emails->send([
                    'from' => 'IruHost <contact@iruhost.com>',
                    'to' => [$email],
                    'subject' => 'Domain name expires today',
                    'html' => "
                    <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>

                        <h2 style='color: #b30000; text-align: center; margin-bottom: 20px;'>Your Domain Expires Today</h2>

                        <p style='color: #333;'>Hello <strong>{$name}</strong>,</p>

                        <p style='color:#333; line-height:1.6;'>
                        This is a reminder that your domain <strong style='color:#0056b3;'>{$productName}</strong> expires <strong>today ({$expiryDate})</strong>.
                        To avoid interruption to your website and email services, please renew immediately.
                        </p>

                        <div style='text-align:center; margin-top:30px;'>
                            <a href='https://iruhost.com' 
                            style='display:inline-block; background-color:#007bff; color:#fff; text-decoration:none;
                            padding:12px 25px; border-radius:6px; font-weight:bold;'>Renew Domain</a>
                        </div>

                        <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                        Need help? Contact us at  
                        <a href='mailto:contact@iruhost.com' style='color:#007bff;'>contact@iruhost.com</a>
                        </p>

                    </div>
                    </div>
                    "
                ]);

                return;

            } catch (\Exception $e) {
                
            }
        } elseif ($daysLeft == 1) {
            try {
                $this->resend->emails->send([
                    'from' => 'IruHost <contact@iruhost.com>',
                    'to' => [$email],
                    'subject' => 'Domain name expires tomorrow',
                    'html' => "
                    <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>

                        <h2 style='color: #e67e22; text-align: center; margin-bottom: 20px;'>Your Domain Expires Tomorrow</h2>

                        <p style='color: #333;'>Hello <strong>{$name}</strong>,</p>

                        <p style='color:#333; line-height:1.6;'>
                        This is a notice that your domain <strong style='color:#0056b3;'>{$productName}</strong> will expire 
                        <strong>tomorrow ({$expiryDate})</strong>.  
                        Renew now to prevent downtime and service disruption.
                        </p>

                        <div style='text-align:center; margin-top:30px;'>
                            <a href='https://iruhost.com' 
                            style='display:inline-block; background-color:#007bff; color:#fff; text-decoration:none;
                            padding:12px 25px; border-radius:6px; font-weight:bold;'>Renew Domain</a>
                        </div>

                        <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                        Need help? Contact us at  
                        <a href='mailto:contact@iruhost.com' style='color:#007bff;'>contact@iruhost.com</a>
                        </p>

                    </div>
                    </div>
                    "
                ]);

                return;

            } catch (\Exception $e) {
                
            }
            return;
        }

        try {
            $this->resend->emails->send([
                'from' => 'IruHost <contact@iruhost.com>',
                'to' => [$email],
                'subject' => "Domain name expires {$daysLeft} days time",
                'html' => "
                    <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>

                        <h2 style='color: #007bff; text-align: center; margin-bottom: 20px;'>Your Domain Expires in {$daysLeft} Days</h2>

                        <p style='color: #333;'>Hello <strong>{$name}</strong>,</p>

                        <p style='color:#333; line-height:1.6;'>
                        This is a reminder that your domain <strong style='color:#0056b3;'>{$productName}</strong> will expire in 
                        <strong>{$daysLeft} days</strong> on <strong>{$expiryDate}</strong>.  
                        Renew ahead of time to maintain uninterrupted service.
                        </p>

                        <div style='text-align:center; margin-top:30px;'>
                            <a href='https://iruhost.com' 
                            style='display:inline-block; background-color:#007bff; color:#fff; text-decoration:none;
                            padding:12px 25px; border-radius:6px; font-weight:bold;'>Renew Domain</a>
                        </div>

                        <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                        Need help? Contact us at  
                        <a href='mailto:contact@iruhost.com' style='color:#007bff;'>contact@iruhost.com</a>
                        </p>

                    </div>
                    </div>
                    "
            ]);

            return;

        } catch (\Exception $e) {
            
        }
    }

    public function getExpiringHosting()
    {
        $thresholdDays = 30;
        $today = new DateTime(date('Y-m-d'));

        $getHosting = $this->pdo->prepare("SELECT * FROM product WHERE product_name = ?");
        $getHosting->execute(['hosting']);

        $hostings = $getHosting->fetchAll(PDO::FETCH_ASSOC);

        if (count($hostings) === 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'No hosting available'
            ]);
            return;
        }

        foreach($hostings as $hosting){
            if ($hosting['billing'] === 'year'){
                $thresholdDays = 30;
            }
            if ($hosting['billing'] === 'quarter'){
                $thresholdDays = 21;
            }
            if ($hosting['billing'] === 'month'){
                $thresholdDays = 14;
            }

            if (!isset($hosting['expiry_date']) || empty($hosting['expiry_date'])) {
                continue;
            }

            $expiryDate = new DateTime(date('Y-m-d', strtotime($hosting['expiry_date'])));

            $interval = $today->diff($expiryDate);
            $daysLeft = (int)$interval->format("%r%a");

            if ($daysLeft <= $thresholdDays && $daysLeft >= 0) {
                $this->generateExpiryMessage(
                    $hosting['product_name'],
                    $daysLeft,
                    $hosting['expiry_date'],
                    $hosting['user_id']
                );
            }
        }
    }
}
