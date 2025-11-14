<?php

// Namespace to define room
namespace Valerian\ChatApp\Server;

// Router Class to control with Requests and paths
class Router {

    // Write Page path and template file and authentication if u must be loged so true else is false
    public static array $router =
    [
        [Config::PATH => '/', Config::TEMPLATE => 'index.html', Config::AUTH => false],
        [Config::PATH => '/Login', Config::TEMPLATE => 'login.html', Config::AUTH => false],
        [Config::PATH => '/Register', Config::TEMPLATE => 'register.html', Config::AUTH => false],
        [Config::PATH => '/Verification_code', Config::TEMPLATE => 'verifyCode.html', Config::AUTH => false],
        [Config::PATH => '/Chat', Config::TEMPLATE => 'chat.html', Config::AUTH => true]
    ];

    // Function route to search about this path in router array (return target array or null)
    public static function route (string $path) : ?array
    {
        foreach (self::$router as $route) :
            if ($route[Config::PATH] === $path) return $route;
        endforeach;
        return null;
    }

    // Function Get to use getContent method in ControllerMethod to get content page
    public static function get (string $path, ?array $route = null) : string
    {
        $methods = new ControllerMethod();
        return $methods->getContent($path, $route);
    }

    // Function Get to use getStyle method in ControllerMethod to get style page
    public static function style ($path) : string | false
    {
        $methods = new ControllerMethod();
        return $methods->getStyle($path);
    }

    // Function Get to use getScript method in ControllerMethod to get JS-code page
    public static function script ($path) : string | false
    {
        $methods = new ControllerMethod();
        return $methods->getScript($path);
    }

    // Function to return content error connection DB
    public static function ErrDB () : string | false
    {
        $content = ControllerMethod::ErrorDB();
        if (!$content) {
            die ("Not Found Main File");
        }
        return $content;
    }

    // Function to return content verification message
    public static function VerificationMessage(int $code) : string | false
    {
        $methods = new ControllerMethod();
        return $methods->verificationContent($code);
    }
}

?>