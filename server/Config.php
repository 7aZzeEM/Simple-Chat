<?php

// Namespace to define room 
namespace Valerian\ChatApp\Server;

// Config Class to set basic variables value
class Config {

    public const APP_NAME = 'ChatAPP';                      // put your app name here

    public const MAIN = __DIR__ . '/../main.html';          // Main HTML file
    public const VIEW = __DIR__ . '/../src/Views/';         // Views Directory
    public const PATH = 'path';                             // static array key for path
    public const TEMPLATE = 'template';                     // static array key for template
    public const AUTH = 'auth';                             // static array key for authentication
    public const DB = __DIR__ . '/../database/database.db'; // DB file path

    // configuration PHPMailer
    public const HOST_Mailer = 'smtp.gmail.com';            // u can use any mailer services without gmail
    public const USRN_Mailer = ''; // here write your ( mailer ) account
    public const PASS_Mailer = '';       // here your app password ( u must verify app password in your gmail account then write app password here, not your password login )
    public const PORT_Mailer = 587;                     // this is basic port SMTP service if u use TLS encryption, if u use SSL encryption use port 465
    public const CHARSET_Mailer = "UTF-8";              // for allowed all languages

    // public const ADMIN_MAIL = '';                        // add your admin account here and remove comment if u want take copy of all messages

    public const SECRET_KEY = 'r+MVD/8US7WKsJzu0+xYKhId/LtHnyEO2x5xBX16CE0=';   // the secret key for JWT authentication
    public const JWT_ALGORITHM = 'HS256';                                       // Encryption algorithm
}

?>
