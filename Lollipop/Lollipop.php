<?php

//// define our directory seperator
//            define('MDS', DIRECTORY_SEPARATOR);
//            // define our secured constant
//            define('MSECURED', 1);
//            // automatically set error reporting off for safety
//            //error_reporting(-1);
//            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
//            ini_set('display_errors', 'Off');
//            ini_set('log_errors ', 'On');
//            // docroot
//            $trace = debug_backtrace();
//            // will automatically find the doc root, no matter where it lives
//            // will be the very last element in our trace
//            $doc_root = array_pop($trace);
//            $doc_root = dirname($doc_root['file']);
//            if (substr($doc_root, 0, 1) !== MDS) {
//                // make sure we're not talking about a Windows path
//                if (!preg_match('/^[a-zA-Z]\:/', $doc_root)) {
//                    $doc_root = MDS . $doc_root;
//                }
//            }
//            define('MROOT', $doc_root);
//
//            unset($trace, $doc_root);
// global namespace
    include('Functions.php');
    include('Autoloader.php');
    
    class Lollipop {

        const SIGNATURE = 'Lollipop Micro Framework';
        const VERSION = '1.0 RC1';
        const POST = 'post';
        const PUT = 'put';
        const GET = 'get';
        const DEL = 'delete';
        const OPT = 'options';
        const HEAD = 'head';
        const CLI = 'cli';
        const HTTP = 'http';
        const AJAX = 'ajax';
        const MM_HEADERS = 'MM_';
        const BASIC = 'basic';
        const DIGEST = 'digest';
        const SIGNED = 'signed'; // preferred method of authenticating via a RESTful service
        const AUTH_SCHEMA = 'MEGA'; // used for http authentication
        const HTTPS_NEVER = 'never'; // never switch https
        const HTTPS_SWITCH = 'switch'; // honour https switching
        const HTTPS_ALWAYS = 'always'; // always use https
        const SECURED = "DEFINED('MSECURED') or header('location: 404');";

        public static $Instance = null; // holds our Lollipop registry
        public $debug_output = null; 
        
        public $Debug = false;  // our debugging flag
        public $Autoloader = array(); // our autoloader registry
        public $App = null; // holds our App         
        public $Env = array(
            'server_name' => '',
            'url' => null,
            'query_string' => null,
            'https' => 'off',
            'mode' => 'http',
            'method' => 'get',
            'content_type' => 'text/html',
            'accept_content_type' => null,
            'accept_charset' => null,
            'accept_language' => null,
            'accept_encoding' => null,
            'custom_headers' => array(),
            'browser' => array(),
            'apc' => false,
            'raw_input' => null
        );
        public $Config = array(
            'APP' => array(
                'bindings' => array('Vanilla', 'Downloader', 'Yaml'),
                'view_wrapper' => 'Mustache',
                'db_wrapper' => false, // instantiable db connector would be noted here
                'https' => self::HTTPS_SWITCH
            ),
            'PATH' => array(),
            // this can be freely customised as needed
            'Mustache' => array(
                'tmp_path' => '/tmp/cache/markup',
                'views_path' => '/templates/views',
                'partials_path' => 'templates/partials'
            ),
            'Redbean' => array(
                "type" => "mysql", // Type of database used
                "host" => "localhost", // 9 times of of 10, this will be "localhost"
                "port" => "", // The port number, if any
                "database" => "whirl", // The name of the database
                "username" => "SiteAdmin", // The username used to access the database
                "password" => "Roswell1947", // The password used to access the database
                "prefix" => "", // The database prefix, if any - do not underscore, the engine will do so
                "use_utf8" => true, // Force UTF-8 of data
                "freeze" => false, // Setting to force the database to "freeze" the ORM, preventing scaffolding
                "options" => array(
                    /* The Shared Hosting duckpunch should be true for databases running on a shared hosting environment */
                    "shared_hosting" => false,
                    /* SSL Encyption on should be true and the mysql attributes should be set if your database is using SSL encryption */
                    "SSL_encryption" => array(
                        "on" => false,
                        "mysql_attr_ssl_key" => "",
                        "mysql_attr_ssl_cert" => "",
                        "mysql_attr_ssl_ca" => ""
                    )
                )
            )
        );

        // pass it the path to your app
        // if you don't pass a path, it will look for a folder in the root that matches the server name
        // on construction, the app will grab all the environmental variables necessary
        // we can also pass in a mock environmental array for unit testing
        private function __construct() {
            // register our autoloader and our error handler first and foremost
            $this->Autoloader['Lollipop'] = new \Lollipop\Autoloader('Lollipop', '/libs/Lollipop');
            set_error_handler(array('\Lollipop', 'errorToException'));
            register_shutdown_function(array('\Lollipop', 'handleFatalError'));

            $env = array();
            // are we using APC?
            $env['apc'] = extension_loaded('apc');

            if (isset($_SERVER['SERVER_NAME'])) {
                $env['server_name'] = str_replace('www.', '', $_SERVER['SERVER_NAME']);
            }

            // look for custom headers used by Sitch
            $custom_headers = array();
            foreach ($_SERVER as $k => $v) {
                // custom headers
                $pos = stripos($k, '_X_');
                // does it exist, and is there a value there?
                if ($pos !== false && $v) {
                    // clean up for mm headers ie. [X_][custom]
                    $k = str_replace(\Lollipop::MM_HEADERS, '', $k);
                    $custom_headers[strtolower(substr($k, ($pos + 3)))] = $v;
                }
            }
            $env['custom_headers'] = ($custom_headers) ? $custom_headers : false;
            unset($k, $v, $custom_headers);

            // is this an https request?
            $env['https'] = (isset($_SERVER['HTTPS'])) ? $_SERVER['HTTPS'] : 'off';
            // get our request method
            $env['method'] = (isset($_SERVER['REQUEST_METHOD'])) ? strtolower($_SERVER['REQUEST_METHOD']) : \Lollipop::GET;
            // if our request method comes through as none, use get as the default
            if ($env['method'] == 'none') {
                $env['method'] = \Lollipop::GET;
            }
            // what type of data is the request expecting as a response - empty values will default to those used by Chrome
            $env['accept_content_type'] = (isset($_SERVER['HTTP_ACCEPT'])) ? self::accepts($_SERVER['HTTP_ACCEPT'], 'text/html') : 'text/html';
            $env['accept_charset'] = (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) ? self::accepts($_SERVER['HTTP_ACCEPT_CHARSET'], 'utf-8') : 'utf-8';
            $env['accept_language'] = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? self::accepts($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'en') : 'en';
            $env['accept_encoding'] = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : false;

            // what format is the request
            if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE']) {
                $env['content_type'] = $_SERVER['CONTENT_TYPE'];
            } else if (isset($env['custom_headers']['content_type']) && $env['custom_headers']['content_type']) {
                // use our custom fall back
                $env['content_type'] = $env['custom_headers']['content_type'];
            } else {
                // last chance
                $env['content_type'] = 'text/html';
            }

            // we also want to know whether this was via http, cli or ajax
            $sapi = php_sapi_name();
            if (in_array($sapi, array('cli', 'cgi')) && empty($_SERVER['REMOTE_ADDR'])) {
                $env['mode'] = \Lollipop::CLI;
                $env['browser'] = 'none';
                // our accept content type needs to change
                $env['accept_content_type'] = 'text/plain';
            } else if (isset($env['custom_headers']['requested_with']) && $env['custom_headers']['requested_with'] == 'XMLHttpRequest') {
                $env['mode'] = \Lollipop::AJAX;
                $env['browser'] = self::browserInfo();
            } else {
                $env['mode'] = \Lollipop::HTTP;
                $env['browser'] = self::browserInfo();
            }
            // we now need to determine the data that came in 
            switch ($env['mode']) {
                case \Lollipop::CLI:
                    /*
                     * -sn:[val] = site/server name
                     * -[p]:[val] = param:val
                     * -r:[route] = url route
                     */
                    $sn = false;
                    $p = false;
                    $r = false;
                    // is the first equal to index.php?
                    if (basename($_SERVER['argv'][0]) == 'index.php') {
                        // remove it
                        array_shift($_SERVER['argv']);
                        foreach ($_SERVER['argv'] as $params) {
                            $split = explode(':', $params);
                            switch ($split[0]) {
                                case '-sn':
                                    $sn = $split[1];
                                    break;

                                case '-r':
                                    $r = $split[1];
                                    break;

                                default:
                                    $p[$split[0]] = $split[1];
                                    break;
                            }
                        }
                        if ($sn && $r) {
                            // everything looks right, so map 'em
                            // fake the server name
                            $_SERVER['SERVER_NAME'] = $sn;
                            $env['server_name'] = $sn;
                            $env['url'] = $r;
                        } else {
                            die('Invalid command');
                        }
                    }
                    $env['raw_input'] = '';
                    // some gc
                    unset($sn, $p, $r);
                    break;

                // all other requests go through default
                default:
                    // to get out uri, we need to do some gymnastics
                    $env['query_string'] = $_SERVER['QUERY_STRING'];
                    if (isset($_SERVER['REDIRECT_URL'])) {
                        $env['url'] = $_SERVER['REDIRECT_URL'];
                    } else if (isset($_SERVER['REQUEST_URI'])) {
                        $env['url'] = str_replace('?' . $env['query_string'], '', $_SERVER['REQUEST_URI']);
                    } else {
                        $env['url'] = '/';
                    }
                    // right, now to get our data depending on the method
                    switch ($env['method']) {
                        // gets are easy...
                        case \Lollipop::GET:
                            $env['raw_input'] = '';
                            break;
                        
                        case \Lollipop::POST:
                            $env['raw_input'] = $_POST;
                            break;

                        // here's the tricky bit...
                        default:
                            // basically, we read a string from PHP's special input location,
                            $env['raw_input'] = trim(file_get_contents('php://input'));
                            break;
                    }
                    break;
            }

            $this->Env = $env;
            unset($env);
        }

        // sets everything up - never needs to be called directly
        public static function init() {
            //ob_start();
            // define our directory seperator
            define('MDS', DIRECTORY_SEPARATOR);
            // define our secured constant
            define('MSECURED', 1);
            // automatically set error reporting off for safety
            //error_reporting(-1);
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', 'Off');
            ini_set('log_errors ', 'On');
            // docroot
            $trace = debug_backtrace();
            // will automatically find the doc root, no matter where it lives
            // will be the very last element in our trace
            $doc_root = array_pop($trace);
            $doc_root = dirname($doc_root['file']);
            if (substr($doc_root, 0, 1) !== MDS) {
                // make sure we're not talking about a Windows path
                if (!preg_match('/^[a-zA-Z]\:/', $doc_root)) {
                    $doc_root = MDS . $doc_root;
                }
            }
            define('MROOT', $doc_root);

            unset($trace, $doc_root);
//            // initialise the singleton
            if (is_null(self::$Instance)) {
                self::$Instance = new self();
            }
        }

        public static function errorToException($error_no, $error = '', $file = '', $line = '') {
            //we don't want strict to trip us up - 
            if ($error_no != E_STRICT) {
                $error .= ' in ' . $file . ' line ' . $line;
                $e = new ErrorException($error, $error_no, null, $file, $line);
                self::halt($e);
                exit;
            }
        }

        public static function handleFatalError() {
            $last_error = error_get_last();
            if (!is_null($last_error)) {
                $message = $last_error['message'] . ' in ' . $last_error['file'] . ' line ' . $last_error['line'];
                $e = new ErrorException($message, $last_error['type'], null, $last_error['file'], $last_error['line']);
                self::halt($e);
                exit;
            }
        }

        public static function getInstance() {
            if (is_null(self::$Instance)) {
                self::$Instance = new self();
            }
            return self::$Instance;
        }

        /**
         * Gets the static instance of the App.
         * @return object The Lollipop application instance.
         */
        public static function app() {
            if (is_null(self::$Instance->App)) {
                trigger_error('No Lollipop Application has been instantiated');
            }
            return self::$Instance->App;
        }

        public static function env($input = null) {
            if (is_null(self::$Instance)) {
                self::$Instance = new self();
            }
            if (is_null($input)) {
                return self::$Instance->Env;
            } else if (is_array($input)) {
                return self::$Instance->Env = array_merge(self::$Instance->Env, $input);
            } else {
                if (isset(self::$Instance->Env[$input])) {
                    return self::$Instance->Env[$input];
                } else {
                    return null;
                }
            }
        }

        /**
         * Shortcut to get to the environmental config data.
         * @param null|array|string $input Passing a null will return the entire config array, passing in an array will extend 
         * the existing array, and passing a key will return the value.
         * @return array|string|null Either an array, string or null if not found.
         */
        public static function config($input = null) {
            if (is_null(self::$Instance)) {
                self::$Instance = new self();
            }
            if (is_null($input)) {
                return self::$Instance->Config;
            } elseif (is_array($input)) {
                return self::$Instance->Config = \Lollipop\extend(self::$Instance->Config, $input);
            } else {
                return \Lollipop\dot(self::$Instance->Config, $input);
            }
        }

        public static function debug($data = null, $label = null) {
            $self = self::getInstance();
            if (is_null($data)) {
                // we're just toggling debug on and off
                if ($self->Debug) {
                    $self->Debug = false;
                    //error_reporting(-1);
                    // ini_set('display_errors', 'Off');
                    // ini_set('log_errors ', 'On');
                } else {
                    $self->Debug = true;
                    // error_reporting(-1);
                    // ini_set('display_errors', 'On');
                    //ini_set('log_errors ', 'On');
                }
                return $self->Debug;
            }
            if ($self->Debug) {
                $trace = debug_backtrace();
                $trace = $trace[0]['file'] . ' on line ' . $trace[0]['line'];
                $label = (is_null($label)) ? 'Output : ' . $trace : $label . ' : ' . $trace;
                if (isset($data)) {
                    $out = '<fieldset style="margin-bottom: 8px; font: 12px/14px arial; border-radius: 5px; padding: 8px; background: transparent; border: 0px;">';
                    $out .= '<legend style="width: auto; margin: 0; font: 12px/14px arial; color: #468847; font-weight: bold; text-shadow: 1px 1px 0px rgba(255, 255, 255, 0.75); border-radius: 4px; border: 1px solid #D6E9C6; padding: 4px 16px; background: #DFF0D8;">';
                    $out .= 'Lollipop Debug > ' . $label . '</legend>';
                    $out .= '<pre style="margin: 0; background: #DFF0D8; padding: 8px; border-radius: 4px; border: 1px solid #D6E9C6; color: #468847;">' . print_r($data, 1) . '</pre>';
                    $out .= '</fieldset>';
                    $self->debug_output[] = $out;
                    unset($out, $trace, $label);
                }
            }
        }

        public static function path($path) {
            // have we passed a full path?
            $path = (substr($path, 0, strlen(MROOT)) == MROOT) ? $path : MROOT . MDS . $path;
            // do a replace on these if they're in the path - also reduce the number of SDS to 1 between folders
            $path = preg_replace('{\\' . MDS . '+}', MDS, str_replace(array("/", "\\"), MDS, $path));
            return $path;
        }

        /**
         * header redirect
         * @param type $url
         * @param type $status
         */
        public static function redirect($url, $status = 302) {
            ob_start();
            \Lollipop\Http::statusHeader($status);
            header('Location: ' . $url);
            ob_clean();
            exit;
        }

        public static function halt($e = null) {
            $self = self::getInstance();
            $app = (is_null($self->App)) ? new \Lollipop\App() : $self->App;
            if (is_null($e) || is_string($e)) {
                $status = 500;
                $title = 'Server Error';
                $message = (!is_null($e)) ? $e : 'Oops - something has gone wrong somewhere.';
            } else {
                $status = (isset($e->status_code)) ? $e->status_code : 500;
                $title = (isset($e->title)) ? $e->title : 'Server Error';
                // we want to hide what the error was if debug is off
                $message = ($self->Debug) ? $e->getMessage() : 'Oops - something has gone wrong somewhere.';
            }
            $app->respond($title, $status);
            $app->message = $message;
            $app->View->uses('error');
            $app->render();
            exit;
        }

        /**
         * marker will determine the server path to the folder containing the file in which the method was called.
         * This path becomes available as an environmental path variable accessible through Lollipop::env('path.[key]');
         * @param string $key The key under which the path will be stored.
         * @return string|null The path as stored in Env.
         */
        public static function markPath($key) {
            $self = self::getInstance();
            $trace = debug_backtrace();
            // will automatically find the doc root, no matter where it lives
            $path = dirname($trace[0]['file']);
            unset($trace);
            // adds the path to the Config Path tree
            return $self->Config['PATH'][$key] = $path;
        }

        ////// TEST //////
        // general version for all accept types
        // HTTP ACCEPTS headers differ greatly across the various browsers. At the end of the day, it cannot be trusted
        // to determine the type of content to spit out to the requestor. This has been noted frequently in various articles and posts
        // around the interwebs.
        // Solution? User agents need to explicitly request a specific content type via HTTP ACCEPTS
        // Browsers etc will set several options, and their associated q scores. So we look for those, and if found, don't allow
        // HTTP ACCEPTS to be negotiated.

        public static function accepts($input, $default) {
            // determine whether the value has quantifiers
            if (strpos($input, ';q=') === false) {
                // we're cool, it is an explicit accepts
                //determine whether it's a valid content type
                if (\Lollipop\Http::getContentType($input)) {
                    return trim($input);
                }
            }
            return $default; // we want to use our own default
        }

        public static function browserInfo() {
            // determine the info first
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            $browser = array();

            if (strchr($useragent, "MSIE")) {
                $browser['browser'] = 'IE';
                preg_match('|MSIE ([0-9]\.[0-9]); |', $useragent, $match);
                $browser['full_version'] = $match[1];
            } else if (strchr($useragent, "Firefox")) {
                $browser['browser'] = 'Firefox';
                preg_match('|Firefox/(.*)|', $useragent, $match);
                $browser['full_version'] = $match[1];
            } else if (strchr($useragent, "Opera")) {
                $browser['browser'] = 'Opera';
                preg_match('|Opera/(.*) \(|', $useragent, $match);
                $browser['full_version'] = $match[1];
            } else if (strchr($useragent, "Chrome")) {
                $browser['browser'] = 'Chrome';
                preg_match('|Chrome/(.*) |', $useragent, $match);
                $browser['full_version'] = $match[1];
            } else if (strchr($useragent, "Safari")) {
                $browser['browser'] = 'Safari';
                preg_match('|Version/(.*) |', $useragent, $match);
                $browser['full_version'] = $match[1];
            } else {
                $browser['browser'] = 'Unknown';
                $browser['full_version'] = '1.0';
            }
            unset($match, $useragent);
            $return = $browser['browser'] . ' ' . $browser['full_version'];
            unset($browser);
            return $return;
        }

    }
        Lollipop::init();