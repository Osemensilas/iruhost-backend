    private function sendPasswordCode($name, $randomCode, $email){

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->emailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->emailUsername;
            $mail->Password = $this->emailPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use constant instead of string
            $mail->Port = 465;
            $mail->Timeout = 30;
            
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Email Headers
            $mail->setFrom('contact@iruhost.com', 'IruHost');
            $mail->addAddress($email);
            $mail->addReplyTo('contact@iruhost.com', 'IruHost Support');
            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code - IruHost';
            
            $greeting = !empty($name) ? "Dear {$name}," : "Hello,";
            $mail->Body = "
            <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden;'>
                            <!-- Header -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>🔐 Password Reset</h1>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <p style='font-size: 16px; color: #333; margin: 0 0 20px 0;'>{$greeting}</p>
                                    
                                    <p style='font-size: 16px; color: #333; margin: 0 0 30px 0; line-height: 1.6;'>
                                        We received a request to reset your password. Use the code below to complete the process:
                                    </p>
                                    
                                    <!-- Code Box -->
                                    <table width='100%' cellpadding='0' cellspacing='0' style='margin: 30px 0;'>
                                        <tr>
                                            <td align='center' style='background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 30px; border-radius: 8px; border-left: 5px solid #667eea;'>
                                                <p style='margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Your Reset Code</p>
                                                <p style='margin: 0; font-size: 48px; font-weight: bold; color: #667eea; letter-spacing: 8px; font-family: monospace;'>{$randomCode}</p>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- Warning Box -->
                                    <table width='100%' cellpadding='15' cellspacing='0' style='background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; margin: 25px 0;'>
                                        <tr>
                                            <td>
                                                <p style='margin: 0 0 10px 0; color: #856404; font-weight: bold; font-size: 14px;'>⚠️ Important Information:</p>
                                                <ul style='margin: 0; padding-left: 20px; color: #856404; font-size: 14px;'>
                                                    <li>This code expires in <strong>15 minutes</strong></li>
                                                    <li>If you didn't request this, please ignore this email</li>
                                                    <li>Never share this code with anyone</li>
                                                </ul>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <p style='font-size: 14px; color: #666; margin: 30px 0 0 0; line-height: 1.6;'>
                                        If you didn't request a password reset, someone may be trying to access your account. 
                                        Please contact us immediately at <a href='mailto:support@iruhost.com' style='color: #667eea;'>support@iruhost.com</a>
                                    </p>
                                    
                                    <p style='font-size: 16px; color: #333; margin: 30px 0 0 0;'>
                                        Best regards,<br>
                                        <strong style='color: #667eea;'>The IruHost Team</strong>
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                                    <p style='margin: 0; color: #999; font-size: 12px;'>© " . date('Y') . " IruHost. All rights reserved.</p>
                                    <p style='margin: 5px 0 0 0; color: #999; font-size: 11px;'>This is an automated message. Please do not reply to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
            ";

            $mail->AltBody = "
Password Reset Request

{$greeting}

We received a request to reset your password.

Your reset code: {$randomCode}

This code expires in 15 minutes.

If you didn't request this, please ignore this email.

Best regards,
The IruHost Team
        ";

            // Send Mail
            if ($mail->send()) {

                return[
                    'status' => 'successful',
                    'msg' => 'Message Sent'
                ];
            } else {
                return[
                    'status' => 'error',
                    'msg' => 'Message not sent. Check connection'
                ];
            }
        } catch (Exception $e) {
            return[
                'status' => 'error',
                'msg' => "Email failed: {$mail->ErrorInfo}"
            ];
        }
    }
