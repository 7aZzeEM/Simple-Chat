<?php

// Namespace to define room
namespace Valerian\ChatApp\Server;

// ControllerMethod For All Method Application
Class ControllerMethod {

    // Function to get Content Page
    public function getContent ($path, $route) : string
    {
        // Confirming path page 
        if ($path === '') $path = '/';
        $path = preg_replace('#/{2,}#', '/', $path);

        // get content main file 
        $mainContent = file_get_contents(Config::MAIN, true);
        if ($mainContent === false) {
            return "<h1>Error: Main template not found!</h1>";
        }

        // Confirming this page exist or no then get Content file then replace @content in main file with content page
        if ($route) {
            if (strtolower($route[Config::PATH]) === strtolower($path)) {
                $viewFile = Config::VIEW . $route[Config::TEMPLATE];
                $content = file_get_contents($viewFile, true);
                if ($content === false) {
                    return "<h1>Error: View file not found: " . htmlspecialchars($viewFile) . "</h1>";
                }
                return str_replace('@content', $content, $mainContent);
            }
        // This path is not exist so return Not Found Page
        } else {
            $notFoundFile = Config::VIEW . "NotFound.html";
            if (file_exists($notFoundFile)) {
                return file_get_contents($notFoundFile, true);
            }
            return "<h1>404 - Page Not Found</h1>";
        }
        
    }

    // Function to get Content Style file
    public function getStyle ($path) : string | false
    {
        // Confirming exist file then return it or return false
        $StylePath = __DIR__ . '/../src/Style/' . $path;
        if (file_exists ($StylePath)) {
            return file_get_contents($StylePath, true);
        }
        return false;
    }

    // Function to get content JS file
    public function getScript ($path) : string | false
    {
        // Confirming exist file then return it or return false
        $ScriptPath = __DIR__ . '/../src/Script/' . $path;
        if (file_exists ($ScriptPath)) {
            return file_get_contents($ScriptPath, true);
        }
        return false;
    }

    // Method to Check u have authentication to access this path or no
    public static function CheckAuth (bool $loged, array $route) : ?string
    {
        /*
            if this auth page is true and u loged in => u can access it
            if this auth page is false and u not loged in => u can access it
            if this auth page is true and u not loged in => u can't access it so redirect to home page          
            if this auth page is false and u loged in => u can't access it so redirect to chat page
        */
        if ($route[Config::AUTH] && $loged) {
            return null;
        } else if (!$route[Config::AUTH] && !$loged) {
            return null;
        } else {
            if ($loged) {
                return '/Chat';
            } else {
                return '/';
            }
        }
    }

    // Generate content error connection DB page
    public static function ErrorDB () : string | false
    {
        if (!file_exists(Config::MAIN)) return false;

        $mainContent = file_get_contents(Config::MAIN, true);
        return str_replace('@content', "Connection DB Faild, try again later.", $mainContent);
    }

    // function generate verification message
    public function verificationContent (int $code) : string | false
    {
        $VCodeFile = Config::VIEW . "VCode.html";

        if (!file_exists($VCodeFile)) {
            return false;
        }

        $content = file_get_contents($VCodeFile, true);

        return str_replace('@VERIFICATION_CODE', $code, $content);
    }

    // Generate JWT for authentication
    public static function generateJWT($user) : string
    {
        $payload = (object) [
            'id' => (int) $user->id,
            'username' => $user->Fname,
            'email' => $user->Email,
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24
        ];

        // JSON encode header and payload
        $headerEncoded = json_encode(['typ' => 'JWT', 'alg' => Config::JWT_ALGORITHM]);
        $payloadEncoded = json_encode($payload);

        // Base64 encode header and payload
        $headerBase64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($headerEncoded));
        $payloadBase64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadEncoded));

        // generate signature
        $signature = hash_hmac('sha256', $headerBase64 . '.' . $payloadBase64, Config::SECRET_KEY, true);

        // Base64 encode signature
        $signatureBase64 = str_replace(['+', '/', '='], ['-','_',''], base64_encode($signature));

        // return JWT
        return $headerBase64 . '.' . $payloadBase64 . '.' . $signatureBase64;
    }

    // Verify JWT
    public static function verifyJWT(string $jwt) : bool
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) return false;
        
        // Base64 Decode payload
        $payloadBase64Decode = base64_decode(str_replace(['-','_'], ['+','/'], $parts[1]));

        // payload JSON decode
        $jsonDecodePayload = json_decode($payloadBase64Decode);
        if (!isset($jsonDecodePayload->iat) || !isset($jsonDecodePayload->exp) || !isset($jsonDecodePayload->email) || !isset($jsonDecodePayload->username)) {
            return false;
        }

        if ($jsonDecodePayload->exp < time()) {
            return false;
        }

        // new signature then encode base64
        $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], Config::SECRET_KEY, true);
        $signatureBase = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return ($signatureBase === $parts[2]);
    }

}

?>