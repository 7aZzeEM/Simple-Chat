<?php

// Namespace to define room
namespace Valerian\ChatApp\Server;

// Get Swoole Class to make application and make websocket server and get Router Class to Control with Requests
use Swoole\WebSocket\Server as SwooleServer;

// WebSocket Class to Control with application
class WebSocket
{
    // server property (server application)
    private static $server = null;

    private static $count = 0;

    // start function to start application
    public static function start () : void
    {
        // If connection DB faild
        if (!Database::DB_Connect()) {
            $contentErrDB = Router::ErrDB();
            echo $contentErrDB;
            return;
        }

        // set timezone
        date_default_timezone_set('Africa/Cairo');

        // generate swoole server
        self::$server = new SwooleServer ('0.0.0.0', 80);

        // when server start print this
        self::$server->on ('start', function (SwooleServer $server) {
            self::$count = 0;
            echo "Chat Application Starting Successfully!💬\nGo To http://127.0.0.1\n\n";
        });

        // When connect user with WebSocket
        self::$server->on ('open', function (SwooleServer $server) {
            self::$count++;
            echo "\n\nUsers online is: " . self::$count . "\n\n";
            foreach ($server->connections as $fd) :
                if ($server->isEstablished($fd)) {
                    $data = (object) ['count' => self::$count, 'Dtype' => 'num'];
                    $server->push($fd, json_encode($data));
                }
            endforeach;
        });

        // When close connection user with WebSocket
        self::$server->on ('close', function (SwooleServer $server, $fd) {
            self::$count--;
            echo "\n\nUser $fd is logout\n\n";
            foreach ($server->connections as $fd) :
                if ($server->isEstablished($fd)) :
                    $data = (object) ['count' => self::$count, 'Dtype' => 'num'];
                    $server->push($fd, json_encode($data));
                endif;
            endforeach;
        });

        // when user send request do that
        self::$server->on('request', function ($request, $response) {
            // get IP address, HTTP method and URL path
            $ip = $request->server['remote_addr'] ?? 'UNKNOWN';
            $method = $request->server['request_method'] ?? 'GET';
            $uri = $request->server['request_uri'] ?? '/';
        
            // if HTTP method is GET do that
            if ($method === 'GET') {
                
                // get extension path
                $path = self::getPath($uri);
                $ext = pathinfo($path, PATHINFO_EXTENSION);
        
                // if extension file is css do that
                if ($ext === 'css') {
                    // use style function in router class to get css code
                    $response->header('Content-Type', 'text/css');
                    $content = Router::style($path);

                    // if css file is exist return it
                    if ($content) {
                        $response->status(200);
                        $response->end($content);
                        self::access($ip, $path, $method, 200);
                        return;
                    }
                }

                // if extension path is js do that
                if ($ext === 'js') {
                    
                    // get js code with router class
                    $response->header('Content-Type', 'text/javascript');
                    $content = Router::script($path);

                    // if js file is exist return it
                    if ($content) {
                        $response->status(200);
                        $response->end($content);
                        self::access($ip,$path, $method, 200);
                        return;
                    }
                }
        
                // if not found extension this mean browser request page not js or css file
                if ($ext === '') {

                    // get route array from router array because router array is array nested array
                    $response->header('Content-Type', 'text/html; charset=utf-8');
                    $route = Router::route($path);
        
                    // if found array with path === this request path this mean this page is exist
                    if ($route !== null) {

                        /*
                        
                        if found path page so u must be confirming authentication before return content,
                        then return content or redirect user

                        */ 
                        $logged = isset($request->cookie['JWT']);
                        $redirect = ControllerMethod::CheckAuth($logged, $route);
        
                        if ($logged) {
                            $verifyJWT = ControllerMethod::verifyJWT($request->cookie['JWT']);
                            if (!$verifyJWT) {
                                $response->cookie('JWT', "STOP", time() - 60 * 60, '/', '', false, false);
                                $redirect = '/';
                            }
                        }

                        /*
                        
                        if redirect variable is not null this mean u can access this page
                        so must be redirect

                        if redirect ecual null this mean u can access this page
                        
                        */

                        // redirect user
                        if ($redirect !== null) {
                            $response->status(302);
                            $response->header('Location', $redirect);
                            $response->end();
                            self::access($ip, $path, $method, 302);
                            return;
                        }
        
                        // user have authentication to access this file so return content page
                        $response->header('Content-Type', 'text/html; charset=utf-8');
                        $response->end(Router::get($path, $route));
                        self::access($ip, $path, 'GET', 200);
                        return;
                    }
                }
        
                // this code will be execute if path not found (css, js, html) any extension
                $response->status(404);
                $response->end(Router::get($path, null));
                self::access($ip, $path, $method, 404);
                return;
            }

            // if HTTP method is POST do that
            if ($method === 'POST') {
                $path = self::getPath($uri);
                
                // Register a new account or Resend verification code
                if ($path === '/server/api/register' || $path === '/server/api/resend-code') {
                    $response->status(200);
                    $userData = json_decode($request->getContent());
                    if (!is_object($userData)) {
                        $response->end(json_encode( (object) ['status' => false, 'message' => 'Invalid object.'] ));
                        self::access($ip, $path, $method, 200);
                        return;
                    }
                    $process = Database::processingRegisterData($userData);

                    if (!$process->status) {
                        $response->end(json_encode($process));
                        self::access($ip, $path, $method, 200);
                        return;
                    }
                    
                    // Send verification code
                    if (!Mailer::sendVerificationCode(trim($userData->Email))) {
                        $response->end(json_encode((object) ['status' => false, 'message' => 'Send verification code is faild, try again later.']));
                        self::access($ip, $path, $method, 200);
                        return;
                    }

                    $response->end(json_encode((object) ['status' => true, 'message' => 'Verification code sent to your email.']));
                    self::access($ip, $path, $method, 200);
                    return;
                }

                // Verify Code
                if ($path === '/server/api/verify-code') {
                    $response->status(200);
                    $userData = json_decode($request->getContent());
                    if (!is_object($userData)) {
                        $response->end(json_encode( (object) ['status' => false, 'message' => 'Invalid object.'] ));
                        self::access($ip, $path, $method, 200);
                        return;
                    }
                    if (!is_numeric( (int) $userData->code)) {
                        $response->end(json_encode( (object) ['status' => false, 'message' => 'Invalid Numeric.'] ));
                        self::access($ip, $path, $method, 200);
                        return;
                    }
                    
                    if (strlen($userData->code) !== 6) {
                        $response->end(json_encode( (object) ['status' => false, 'message' => 'Invalid Length.'] ));
                        self::access($ip, $path, $method, 200);
                        return;
                    }
                    if (!Database::verifyCode($userData)) {
                        $response->end(json_encode( (object) ['status' => false, 'message' => 'Invalid Code.'] ));
                        self::access($ip, $path, $method, 200);
                        return;
                    }

                    $addedUser = Database::addNewUser($userData);

                    if (!$addedUser->status) {
                        $response->end(json_encode($addedUser));
                        self::access($ip, $path, $method, 200);
                        return;
                    }

                    // Authentication done
                    $userData->id = $addedUser->id;
                    $jwt = ControllerMethod::generateJWT($userData);
                    $response->cookie('JWT', $jwt, time() + 60 * 60 * 24, '/', '', false, false);

                    // successfully added user
                    $response->end(json_encode( (object) ['status' => true, 'message' => 'Verification code successfully.'] ));
                    self::access($ip, $path, $method, 200);
                    return;
                }

                // Login user
                if ($path === '/server/api/login') {
                    $response->status(200);
                    $userData = json_decode($request->getContent());
                    if (!is_object($userData)) {
                        $response->end(json_encode( (object) ['status' => false, 'message' => 'Invalid object.'] ));
                        self::access($ip, $path, $method, 200);
                        return;
                    }

                    $login = Database::loginUser($userData);
                    if (!$login->status) {
                        $response->end(json_encode($login));
                        self::access($ip, $path, $method, 200);
                        return;
                    }

                    // Authentication done
                    $jwt = ControllerMethod::generateJWT($login->payload);
                    $response->cookie('JWT', $jwt, time() + 60 * 60 * 24, '/', '', false, false);

                    // successfully login user
                    $response->end(json_encode( (object) ['status' => true, 'message' => $login->message] ));
                    self::access($ip, $path, $method, 200);
                    return;
                }

                // Logout user
                if ($path === '/server/api/logout') {
                    $response->cookie('JWT', "STOP", time() - 60 * 60, '/', '', false, false);
                    $response->header('Content-Type', 'application/json');
                    $response->status(200);
                    $response->end(json_encode( (object) ['status' => true]));
                    self::access($ip, $path, $method, 200);
                    return;
                }

                // Get messages from DataBase
                if ($path === '/server/api/get-messages') {
                    $logged = isset($request->cookie['JWT']);
                    if ($logged) {
                        $verifyJWT = ControllerMethod::verifyJWT($request->cookie['JWT']);
                        if (!$verifyJWT) {
                            $response->cookie('JWT', "STOP", time() - 60 * 60, '/', '', false, false);
                            $response->status(302);
                            $response->end(json_encode( (object) ['status' => false, 'message' => 'U must login after see messages.'] ));
                            return;
                        }
                    }

                    $messages = Database::getMessages();
                    if (!$messages->status) {
                        $response->status(400);
                        $response->end(json_encode( (object) ['status' => false, 'message' => $messages->message] ));
                        return;
                    }

                    $response->status(200);
                    $response->end(json_encode( (object) ['status' => true, 'messages' => $messages->messages] ));
                    return;
                }

                // This path is not exist
                $response->status(404);
                $response->end(Router::get($path, null));
                self::access($ip, $path, $method, 404);
                return;
            }

            // this method will be execute if request method not allowed
            $response->status(405);
            $response->end('Method Not Allowed');
            self::access($ip, $uri, $method, 405);
        });

        // if websocket server have a message do that
        self::$server->on ('message', function (SwooleServer $server, $frame) {
            $created_at = Database::saveMessage($frame->data);
            if ($created_at) {
                $message = json_decode($frame->data, true);
                echo "New Message: " . (new \DateTime())->format('Y-m-d H:i:s') . "\n";
                echo "Sender ID: " . $message['id'] . "\n";
                echo "Username: " . $message['username'] . "\n";
                echo "Message: " . urldecode($message['message']) . "\n\n\n\n";
        
                $newMessage = (object) [
                    "sender_id" => $message['id'],
                    "user_message" => $message['message'],
                    "username" => $message['username'],
                    "created_at" => $created_at
        
                ];
                foreach ($server->connections as $fd) :
                    if ($server->isEstablished($fd)) :
                        $server->push($fd, json_encode($newMessage));
                    endif;
                endforeach;
                return;
            }
            echo "Somethink went wrong when save new message in DB.";
            return false;
        });

        // run application
        self::$server->start();
    }

    // Function getPath take a URI then return extension file
    private static function getPath ($uri) : string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path =  rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    // Function access to print every request in terminal
    private static function access ($ip, $path, $method, $status) : void
    {
        echo "$ip -> $path | $method -> $status\n";
    }
}

?>
