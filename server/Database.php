<?php

// Namespace to define room
namespace Valerian\ChatApp\Server;

use PDO;                                        // if u will use PDO and PDOException write
use PDOException;

class Database
{

    // DB object property
    private static $DB;

    public static function DB_Connect () : bool
    {
        if (!file_exists(Config::DB)) return false;

        try {

            self::$DB = new PDO ("sqlite:" . Config::DB);
            self::$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$DB->exec("PRAGMA foreign_keys = ON");

        } catch (PDOException $e) {
            return false;
        }

        return true;
    }

    // processing register data
    public static function processingRegisterData (object $user) : object
    {
        $valid = self::validateRegister($user);

        if (!$valid->status) {
            return (object) ['status' => false, 'message' => $valid->message];
        }

        $email = trim($user->Email);

        // if u use MySQL replace :email with ?
        $stmt = self::$DB->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
        // if u use MySQL replace this code with $stmt->execute([$user->Email]);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return (object) ['status' => false, 'message' => 'This email is already exist.'];
        }

        return (object) ['status' => true];
    }

    // Check register data valid or invalid
    private static function validateRegister (object $user) : object
    {
        // Fields confirm
        if (!isset($user->Fname) || !isset($user->Email) || !isset($user->Password) || !isset($user->ConfirmPassword))
        {
            return (object) ['status' => false, 'message' => 'Missing required fields.'];
        }

        // Remove spaces
        $name = trim($user->Fname);
        $email = trim($user->Email);
        $pass = trim($user->Password);
        $Cpass = trim($user->ConfirmPassword);

        // Regex rules
        $regexName = '/^(?!.*(<script|<iframe|<img|<svg|javascript:|vbscript:|on\w+\s*=|<\?|<%|<!\[CDATA)).*$/';
        $regexEmail = '/^[a-zA-Z0-9_.-]+@(gmail|yahoo|hotmail)+\.[a-z]{2,}$/';
        $regexPass = '/^[a-zA-Z0-9_.@!~$%&*()\s-]{10,}$/';

        // Validate data
        if (!preg_match($regexName, $name)) {
            return (object) ['status' => false, 'message' => 'Invalid name.'];
        }
        if (!preg_match($regexEmail, $email)) {
            return (object) ['status' => false, 'message' => 'Invalid email. gmail, yahoo and hotmail just only.'];
        }
        if (strlen($pass) < 10) {
            return (object) ['status' => false, 'message' => 'Password must be at least 10 characters.'];
        }
        if (!preg_match($regexPass, $pass)) {
            return (object) ['status' => false, 'message' => 'Invalid Password, this characters is not allowed.'];
        }
        if ($pass !== $Cpass) {
            return (object) ['status' => false, 'message' => 'Confirming password not same basic password.'];
        }

        return (object) ['status' => true];
    }

    // save a new verification code in DB
    public static function newVerificationCode ($email, $code) : bool
    {
        $stmt = self::$DB->prepare("SELECT email from verification_code WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $time = time() + 600;

        if ($stmt->fetch()) {
            $stmt = self::$DB->prepare("UPDATE verification_code SET code = :code, expires_at = :expires_at WHERE email = :email");
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':code', $code, PDO::PARAM_STR);
            $stmt->bindValue(':expires_at', $time, PDO::PARAM_INT);
        } else {
            $stmt = self::$DB->prepare("INSERT INTO verification_code (email, code, expires_at) VALUES (:email, :code, :expires_at)");
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':code', $code, PDO::PARAM_STR);
            $stmt->bindValue(':expires_at', $time, PDO::PARAM_INT);
        }

        try {
            $stmt->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Verify Code
    public static function verifyCode (object $user) : bool
    {
        $stmt = self::$DB->prepare("SELECT * FROM verification_code WHERE email = :email AND code = :code LIMIT 1");
        $stmt->bindValue(':email', $user->Email, PDO::PARAM_STR);
        $stmt->bindValue(':code', $user->code, PDO::PARAM_STR);
        $stmt->execute();

        $reg = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$reg) {
            return false;
        }

        if ($reg->expires_at < time()) {
            return false;
        }

        try {
            $stmt = self::$DB->prepare("DELETE FROM verification_code WHERE email = :email");
            $stmt->bindValue(':email', $user->Email, PDO::PARAM_STR);
            $stmt->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
        
    }

    // Add User in DB
    public static function addNewUser (object $user) : object
    {
        if (!self::$DB) {
            self::DB_Connect();
        }
        $process = Database::processingRegisterData($user);

        if (!$process->status) {
            return $process;
        }

        try {
            $stmt = self::$DB->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :ph)");
            $stmt->bindValue(':u', $user->Fname, PDO::PARAM_STR);
            $stmt->bindValue(':e', $user->Email, PDO::PARAM_STR);
            $stmt->bindValue(':ph', password_hash($user->Password, PASSWORD_BCRYPT), PDO::PARAM_STR);
            $stmt->execute();

            $id = self::$DB->lastInsertId();
            return (object) ['status' => true, 'message' => 'Successfully add new user.', 'id' => (int) $id];
        } catch (PDOException $e) {
            return (object) ['status' => false, 'message' => 'Falid add new user.'];
        }
    }

    // User login
    public static function loginUser ($userData) : object
    {
        if (!self::$DB) {
            self::DB_Connect();
        }

        $stmt = self::$DB->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
        $stmt->bindValue(':e', $userData->email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$user) {
            return (object) ['status' => false, 'message' => 'User not found.'];
        }

        if (!password_verify($userData->password, $user->password_hash)) {
            return (object) ['status' => false, 'message' => 'Password is wrong.'];
        }

        return (object) [
            'status' => true,
            'message' => 'Welcome MR.' . $user->username,
            'payload' => (object) [
                'id' => $user->id,
                'Fname' => $user->username,
                'Email' => $user->email
            ]
        ];
    }

    // Get messages from Database
    public static function getMessages () : object
    {
        if (!self::$DB) {
            self::DB_Connect();
        }

        $stmt = self::$DB->prepare("SELECT m.id, m.sender_id, m.user_message, m.created_at, u.username FROM messages m INNER JOIN users u WHERE u.id = m.sender_id ORDER BY m.id");
        try {
            $stmt->execute();
            return (object) ['status' => true, 'messages' => json_encode($stmt->fetchAll(PDO::FETCH_ASSOC))];
        } catch (PDOException $e) {
            return (object) ['status' => false, 'message' => 'Falid get messages from DB: ' . $e];
        }
    }

    // Save new message in DB
    public static function saveMessage (string $data) : false | string
    {
        try {
            $message = json_decode($data, true);
            if (!$message || !isset($message['id']) || !isset($message['message'])) {
                return false;
            }

            $stmt = self::$DB->prepare("INSERT INTO messages (sender_id, user_message) VALUES (:id, :m)");
            $stmt->bindValue(':id', $message['id'], PDO::PARAM_INT);
            $stmt->bindValue(':m', $message['message'], PDO::PARAM_STR);
            $stmt->execute();

            $newMessage = self::$DB->prepare("SELECT created_at FROM messages ORDER BY id DESC LIMIT 1");
            $newMessage->execute();
            $row = $newMessage->fetch();
            return $row ? $row['created_at'] : false;
        } catch (PDOException $e) {
            return false;
        }
    }
}

?>