<?php

namespace Lollipop {

    /**
     * Our App instance, acctually does all the hard work
     */
    class App {
        // used in routing
        // matches a valid PHP variable name wrapped by curly braces
        const GREEDY_PATTERN = '/{?\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}}?/';
        // greedy as hell
        const GREEDY_REPLACE = '(?P<\1>(.+)+?)';
        // matches a valid PHP variable name
        const VAR_PATTERN = '/{?\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)}?/';
        // matches a slug, which can be upper, lower, can have hyphens, underscores, periods and numbers only
        const VAR_REPLACE = '(?P<\1>([a-zA-Z0-9_\-\.\@]+)+?)';

        public $Request = false; // holds an instance of the SitchRequest object
        public $Response = false; // holds an instance of the SitchResponse object
        public $Routes = array();
        public $View = null; // holds our template engine instance
        public $Db = null; // holds our database connector
        public $render_callback = null; // our render callback
        // our process/event-patterned stack
        public $event_stack = array();
        // variable that holds a function used to determine the markup used when an error is triggered
        public $error_stack = array(
            0 => null, // same as 404
            404 => null
        );
        public $output_stack = array();
        // variable that holds a custom callback to be used for each of the authetication requests
        // comes wqith a default for signed requests as an example
        public $auth_stack = array(
            \Lollipop::BASIC => null,
            \Lollipop::DIGEST => null,
            \Lollipop::SIGNED => null
        );
        
        private $Globals = array(); // holds any globals we'd like to storer, which are accessible throughout the app

        public function __construct() {
            // store our singleton instance
            \Lollipop::$Instance->App = $this;            
            //$this->Request = new \Lollipop\Request();
            $this->Response = new \Lollipop\Response();
            // set up our stack bindings
            $bindings = \Lollipop::config('APP.bindings');
            if($bindings) {
                foreach($bindings as $binding) {
                    $binding = "\Lollipop\Wrappers\Bindings\\" . $binding;
                    $binding::bindStack($this);
                }
                unset($binding);
            }

            $view_instance = "\Lollipop\Wrappers\View\\" . \Lollipop::config('APP.view_wrapper');
            $this->View = new $view_instance();
            $db_instance = \Lollipop::config('APP.db_wrapper');
            if($db_instance) {
                    $db_instance =  "\Lollipop\Wrappers\Db\\" . $db_instance;
                    $this->Db = new $db_instance();
            }
            
            unset($view_instance, $db_instance, $bindings);
            return $this;
        }

        public function __isset($var) {
            return isset($this->Response->data[$var]);
        }

        public function __set($var, $val) {
            $this->Response->data[$var] = $val;
        }

        public function __get($var) {
            if (isset($this->Response->data[$var])) {
                return $this->Response->data[$var];
            }
            return null;
        }

        public function __unset($var) {
            if (isset($this->Response->data[$var])) {
                unset($this->Response->data[$var]);
            }
        }

        // removes a global variable
        public function trash($key) {
            if(isset($this->Globals[$key])) {
                unset($this->Globals[$key]);
            }
        }
        
        // has two functions, if key and value is passed, will set it
        // if just the key is passed, will check whether the variable is set
        public function set($var, $val = null) {
            if(is_null($val)) {
                return isset($this->Globals[$var]);
            } else {
                $this->Globals[$var] = $val;
            }
        }

        public function get($var) {
            if (isset($this->Globals[$var])) {
                return $this->Globals[$var];
            }
            return null;
        }

        // routing is built into the app - returns the route object
        // all routes have to have an alias, which is the first argument, to make use it is defined
        public function route() {
            $args = func_get_args();
            // our first arg will be our alias
            $alias = array_shift($args);
            // our second arg will be our route
            $route = $expr = array_shift($args);
            // our third arg will be our callback
            $callback = array_shift($args);
            $pattern = $replace = array();

            // matches a valid PHP variable name wrapped by curly braces
            $pattern[] = self::GREEDY_PATTERN;
            // greedy as hell
            $replace[] = self::GREEDY_REPLACE;
            // matches a valid PHP variable name
            $pattern[] = self::VAR_PATTERN;
            // matches a slug, which can be upper, lower, can have hyphens, underscores, periods and numbers only
            $replace[] = self::VAR_REPLACE;

            // replace curly braces for optional params
            $route = str_replace(')', ')?', $route);
            $route = preg_replace($pattern, $replace, $route);
            $route = "`^(?P<_base_>{$route})(|\/)$`i"; // match optional trailing slash for canonical urls
            unset($pattern, $replace, $args);
            return $this->Routes[$alias] = new \Lollipop\Route($alias, $expr, $route, $callback);
        }

        /**
         * Lookup a matching route
         * @param type $expr
         * @return type Either an instance of Request if successful, or an error instance if not
         */
        public function lookup($expr) {
            try {
                // iterate over our routes
                if ($this->Routes) {
                    // create a new instance of Request, but hold it in Lookup
                    $lookup = new \Lollipop\Request();

                    foreach ($this->Routes as $alias => $route) {

                        foreach ($route->regex as $rk => $rv) {
                            preg_match($rv, $expr, $match);

                            if ($match && isset($match['_base_'])) {
                                // we have a match
                                $lookup->url = $match['_base_'];
                                unset($match['_base_']);
                                $params = array();
                                if ($match) {
                                    foreach ($match as $k => $v) {
                                        if (!is_int($k) && !empty($v)) {
                                            $params[$k] = $v;
                                        }
                                    }
                                }
                                $lookup->params = $params;
                                unset($match, $params);
                                // get our matching route object
                                $lookup->route = $this->Routes[$alias];
                                // check whether the allowed methods are acceptable
                                if (in_array(\Lollipop::env('method'), $this->Routes[$alias]->allowed_methods) &&
                                        in_array(\Lollipop::env('mode'), $this->Routes[$alias]->allowed_modes)) {
                                    unset($alias, $match, $expr, $route, $k, $v);

                                    // successfully matched
                                    return $lookup;
                                } else if (!$this->Routes[$alias]->passthru) {
                                    unset($alias, $match, $expr, $route, $k, $v, $return);
                                    // return a 405 Method not allowed
                                    throw new \Lollipop\Error\MethodNotAllowed(null, $lookup);
                                    //return 405;
                                }
                            }
                        }
                    }
                }
                unset($alias, $match, $expr, $route, $k, $v, $return);
                //return 0;
                throw new \Lollipop\Error\NotFound(null, $lookup);
            } catch (\Lollipop\Error $e) {
                return $e;
            }
        }

        /**
         * Creates a url based on an aliased route, and accepts an array of named pairs as params
         * @param type $alias
         * @param type $params
         */
        public function url($alias, $params = false) {
            $url = false;

            if (isset($this->Routes[$alias])) {
                $route = $this->Routes[$alias];
                // first thing to do, replace any params that exist in the expr
                $parse = str_replace(array('(', ')', '{', '}'), array('|', null, null, null), $route->expr[0]);
                if ($params && is_array($params)) {
                    foreach ($params as $k => $v) {
                        $parse = preg_replace(self::VAR_PATTERN, (string) $v, $parse, 1, $count);
                        // replace the value
                        if ($count) {
                            unset($params[$k]);
                        }
                    }
                }
                // check for optional params, and whether they're parsed
                $parts = explode('|', $parse);
                if ($parts) {
                    foreach ($parts as $part) {
                        // look for variable placeholders
                        if (strpos($part, '$') === false) {
                            $url[] = $part;
                        }
                    }
                } else {
                    $url[] = $parse;
                }
                $qs = null;
                // leftovers?
                if ($params && is_array($params)) {
                    foreach ($params as $k => $v) {
                        $qs[] = $k . '=' . $v;
                    }
                    if ($qs !== null) {
                        $qs = (count($qs) > 1) ? implode('&', $qs) : $qs[0];
                        $qs = '?' . $qs;
                    }
                }
                unset($part, $parts, $parse, $route, $params, $alias, $count);
                $url = (count($url) > 1) ? implode('', $url) : $url[0];
                return $url . $qs;
            }
            // not found
            unset($part, $parts, $parse, $route, $params, $alias);
            return false;
        }

        public function fullUrl($alias, $params = false) {
            $url = false;

            if (isset($this->Routes[$alias])) {
                $route = $this->Routes[$alias];
                $url = ($route->https) ? 'https://' . \Lollipop::env('server_name') : 'http://' . \Lollipop::env('server_name');
                $url .= $this->url($alias, $params);
                unset($route, $params, $alias);
                return $url;
            }
            unset($params, $alias);
            return false;
        }

        /**
         * Clears the internal response object
         * Clears vars set via set() 
         */
        public function clear() {
            $args = func_get_args();
            if ($args) {
                foreach ($args as $arg) {
                    unset($this->Response->data[$arg]);
                }
            } else {
                $this->Response = new \Lollipop\Response();
            }
            return $this;
        }

        // adds data to the response object
        // we can respond with an integer representing an http status code
        // we can respond with an array of data variables to be passed to the view
        // we can respond with a content type
        public function respond() {
            $args = func_get_args();

            if ($args) {
                foreach ($args as $arg) {
                    switch ($arg) {
                        case (is_int($arg) && strlen($arg) == 3) :
                            if (\Lollipop\Http::statusMessage($arg)) {
                                $this->Response->status = (int) $arg;
                            }
                            break;

                        case is_string($arg):
                            // determine whether what it is
                            if (\Lollipop\Http::getContentType($arg)) {
                                // content type
                                $this->Response->content_type = trim($arg);
                            } else {
                                // set it as the title string
                                $this->Response->title = trim($arg);
                            }
                            break;
                    }
                }
            }
            return $this;
        }

        // binds a function/class::method to a specific event in the stack
        public function onEvent($event, $function) {
            if (is_string($function)) {
                $this->event_stack[$event][$function] = $function;
            } else {
                $this->event_stack[$event][] = $function;
            }
            return $this;
        }

        /**
         * unsets any event bindings for a particular event
         * 
         */
        public function unbindEvent($event) {
            if (isset($this->event_stack[$event])) {
                $this->event_stack[$event] = null;
            }
            return $this;
        }

        // triggers an event in the stack, causing it to execute all bound functions/class methods it contains
        // passing the optional child event will only trigger that child
        // note : when triggering an event, the remainder of the stack down will also trigger
        // important : your function cannot return anything, or it will halt the stack
        public function trigger($event = false, $child_event = false) {
            // no event, use the very first one
            if (!$event) {
                // we want the key of the very first one
                $event = \Lollipop\array_key_by_index($this->event_stack, 0);
            }
            if ($child_event) {
                if (isset($this->event_stack[$event][$child_event])) {
                    if (strpos($child_event, '_') !== false) {
                        $split = explode('_', $child_event);

                        $method = new \ReflectionMethod($split[0], $split[1]);
                        if ($method->isStatic()) {
                            $method->invokeArgs($split[0], array());
                        } else {
                            $class = new $split[0];
                            $method->invokeArgs($class, array());
                        }
                    } else {
                        return $this->event_stack[$event][$child_event]();
                    }
                }
            } else {
                $event_stack = \Lollipop\array_slice_assoc($this->event_stack, $event);

                foreach ($event_stack as $ev) {
                    if ($ev) {
                        foreach ($ev as $e) {
                            if (is_string($e) && strpos($e, '_') !== false) {
                                $split = explode('_', $e);

                                // is this a static method or not?
                                $method = new \ReflectionMethod($split[0], $split[1]);
                                if ($method->isStatic()) {
                                    $method->invokeArgs($split[0], array());
                                } else {
                                    $class = new $split[0];
                                    $method->invokeArgs($class, array());
                                }
                            } else {
                                $e();
                            }
                        }
                    }
                }
            }
        }

        // registers a dynamic event and inserts it into the event stack
        // passing in an event will register the event either before or after that event
        // if neither is passed, the event will be registered at the end of the stack
        public function afterEvent($item, $event = false) {
            if ($event) {
                // find our key
                $key = 1;
                foreach ($this->event_stack as $k => $v) {
                    if ($event == $k) {
                        break;
                    }
                    $key++;
                }

                $slice = array_slice($this->event_stack, $key);
                $start = array_slice($this->event_stack, 0, $key);
                $start[$item] = false;

                $this->event_stack = \Lollipop\extend($start, $slice);
                return $this;
            }
            $this->event_stack[$item] = false;
            return $this;
        }

        public function beforeEvent($item, $event = false) {
            if ($event) {
                // find our key
                $key = 0;
                foreach ($this->event_stack as $k => $v) {
                    if ($event == $k) {
                        break;
                    }
                    $key++;
                }

                $slice = array_slice($this->event_stack, $key);
                $start = array_slice($this->event_stack, 0, $key);
                $start[$item] = false;

                $this->event_stack = \Lollipop\extend($start, $slice);
                return $this;
            }
            $this->event_stack = \Lollipop\extend(array($item => false), $this->event_stack);
            return $this;
        }

        // binds an error function to App::$__on_error for use as a general error handler
        // comes with a default error handler
        // binds a function/class::method to a specific event in the stack
        // note : will create new errors on the fly
        public function onError($event, $function) {
            // duplicate the error event handler for 404
            if ($event == 404) {
                $this->error_stack[0] = $function;
            }
            $this->error_stack[$event] = $function;
            return $this;
        }

        // triggers an event in the stack, causing it to execute all bound functions/class methods it contains
        public function error() {
            $args = func_get_args();
            $event = (isset($args[0])) ? array_shift($args) : 404;
            $args = (isset($args[0])) ? $args : array();

            $e = $this->error_stack[$event];

            if (is_string($e) && strpos($e, '_') !== false) {
                $split = explode('_', $e);

                // is this a static method or not?
                $method = new \ReflectionMethod($split[0], $split[1]);
                if ($method->isStatic()) {
                    $method->invokeArgs($split[0], $args);
                } else {
                    $class = new $split[0];
                    $method->invokeArgs($class, $args);
                }
            } else {
                call_user_func_array($e, $args);
            }
            // jump straight to rendering the output
            $this->trigger('render');
            // nothing more must execute
            $this->stop();
        }

        // binds an error function to App::$__Auth for use as a general auth handler
        // binds a function/class::method to a specific auth in the stack
        // note : will create new auth events on the fly
        public function onAuth($event, $function) {
            $this->auth_stack[$event] = $function;
            return $this;
        }

        // triggers an event in the stack, causing it to execute all bound functions/class methods it contains
        public function auth() {
            $args = func_get_args();
            $event = (isset($args[0])) ? array_shift($args) : die('The Authentication method used does not exist.');
            $args = (isset($args[0])) ? $args : array();

            $e = $this->auth_stack[$event];

            if (is_string($e) && strpos($e, '_') !== false) {
                $split = explode('_', $e);

                // is this a static method or not?
                $method = new \ReflectionMethod($split[0], $split[1]);
                if ($method->isStatic()) {
                    $method->invokeArgs($split[0], $args);
                } else {
                    $class = new $split[0];
                    $method->invokeArgs($class, $args);
                }
            } else {
                call_user_func_array($e, $args);
            }
        }

        public function onOutput() {
            $content_types = func_get_args();
            $function = array_pop($content_types); // function is always the last element
            if ($content_types) {
                foreach ($content_types as $content_type) {
                    $this->output_stack[$content_type] = $function;
                }
            }
            return $this;
        }

        public function output() {
            $args = func_get_args();
            $content_type = (isset($args[0])) ? array_shift($args) : 'default';
            $args = (isset($args[0])) ? $args : array();

            $e = $this->output_stack[$content_type];

            if (is_string($e) && strpos($e, '_') !== false) {
                $split = explode('_', $e);

                // is this a static method or not?
                $method = new \ReflectionMethod($split[0], $split[1]);
                if ($method->isStatic()) {
                    return $method->invokeArgs($split[0], $args);
                } else {
                    $class = new $split[0];
                    return $method->invokeArgs($class, $args);
                }
            } else {
                return call_user_func_array($e, $args);
            }
        }
        
        // defines our render method
        public function onRender($callback) {
            $this->render_callback = $callback;
            return $this;
        }
        
        public function render() {
            if(defined('MDONE')) {
                exit;
            }
            $args = func_get_args();
            if (is_string($this->render_callback) && strpos($this->render_callback, '_') !== false) {
                $split = explode('_', $this->render_callback);

                // is this a static method or not?
                $method = new \ReflectionMethod($split[0], $split[1]);
                if ($method->isStatic()) {
                    $method->invokeArgs($split[0], $args);
                } else {
                    $class = new $split[0];
                    $method->invokeArgs($class, $args);
                }
            } else {
                call_user_func($this->render_callback, $args);
            }
            echo (string) $this->Response;
                    if(\Lollipop::getInstance()->Debug && \Lollipop::getInstance()->debug_output) {
                    foreach(\Lollipop::getInstance()->debug_output as $each) {
                        echo $each . PHP_EOL;
                    }
                }
            // prevent repeat performances
            define('MDONE', true);
        }

        /**
         * This will stop the application immediately and send the response to the browser if flush is true
         * If flush is false, nothing will happen
         * TODO: change to halt
         */
        public function stop($flush = true) {
            if ($flush) {
                echo (string) $this->Response;
            }
            exit;
        }

        // for those who insist on it
        public function run() {
            try {
                // trigger our event stack
                $this->trigger();
                $this->render();
                exit;
            } catch (\Exception $e) {
                \Lollipop::halt($e);
            }
        }

    }

}
