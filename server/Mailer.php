<?php

// Namespace to define room
namespace Valerian\ChatApp\Server;

// include PHPMailer libraries
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Mailer class to send verification code 
class Mailer
{
    private static $mailer = null;

    // Setup configs phpmailer
    private static function configMailer ()
    {
        self::$mailer = new PHPMailer();

        self::$mailer->isSMTP();
        self::$mailer->Host = Config::HOST_Mailer;
        self::$mailer->SMTPAuth = true;
        self::$mailer->Username = Config::USRN_Mailer;
        self::$mailer->Password = Config::PASS_Mailer;
        self::$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        self::$mailer->Port = Config::PORT_Mailer;

        self::$mailer->isHTML(true);
        self::$mailer->CharSet = Config::CHARSET_Mailer;
    }

    // To send verification code for user
    public static function sendVerificationCode (string $email) : bool
    {
        if (self::$mailer === null) {
            self::configMailer();
        }

        self::$mailer->setFrom(Config::USRN_Mailer, Config::APP_NAME);
        self::$mailer->addAddress($email);

        // If you have an admin account and want a copy of all messages sent to it, delete the comment from this code.
        // self::$mailer->addBCC(ADMIN_MAIL);

        self::$mailer->Subject = "Verification Code " . Config::APP_NAME;
        $code = random_int(100000, 999999);

        $content = Router::VerificationMessage($code);
        if (!$content) {
            return false;
        }
        self::$mailer->Body = $content;

        if (!Database::newVerificationCode($email, $code)) {
            return false;
        }

        try {
            self::$mailer->send();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
        
    }
}

?>