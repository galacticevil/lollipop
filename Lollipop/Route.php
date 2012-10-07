<?php

namespace Lollipop {

    /**
     * The Route class is a container for information about this route
     */
    class Route {

        // store our actual route expression for convenience
        public $regex = null;
        // the expression as defined when the route was creates
        public $expr = null;
        // our alias
        public $alias = null;
        // SSL route?
        public $https = false;
        // allow pass through for non-matching params?
        public $passthru = false;
        // types of authentication to be used if any
        public $auth = false;
        public $allowed_methods = array(
            \Lollipop::POST,
            \Lollipop::GET,
            \Lollipop::PUT,
            \Lollipop::DEL
        );
        public $allowed_modes = array(
            \Lollipop::CLI,
            \Lollipop::HTTP,
            \Lollipop::AJAX
        );
        public $callback = null;

        public function __construct($alias, $expr, $route, $callback) {
            $mm = \Lollipop::getInstance();
            switch ($mm->Config['APP']['https']) {
                case \Lollipop::HTTPS_SWITCH:
                    $this->https = false;
                    break;

                case \Lollipop::HTTPS_ALWAYS:
                    $this->https = true;
                    break;

                case \Lollipop::HTTPS_NEVER:
                    $this->https = false;
                    break;
            }
            $this->alias = $alias;
            $this->regex[] = $route;
            $this->expr[] = $expr;
            $this->callback = $callback;
            $mm->App->Routes[$alias] = $this;
        }

        // this route must use https
        public function https() {
            $config = \Lollipop::getInstance()->Config;
            switch ($config['APP']['https']) {
                case \Lollipop::HTTPS_SWITCH:
                    $this->https = true;
                    break;

                case \Lollipop::HTTPS_ALWAYS:
                    $this->https = true;
                    break;

                case \Lollipop::HTTPS_NEVER:
                    $this->https = false;
                    break;
            }
            return $this;
        }

        // this route must use http
        public function http() {
            $config = \Lollipop::getInstance()->Config;
            switch ($config['APP']['https']) {
                case \Lollipop::HTTPS_SWITCH:
                    $this->https = false;
                    break;

                case \Lollipop::HTTPS_ALWAYS:
                    $this->https = true;
                    break;

                case \Lollipop::HTTPS_NEVER:
                    $this->https = false;
                    break;
            }
            return $this;
        }

        /**
         * The route cannot be accessed unless verified and authenticated
         */
        public function auth() {
            $args = func_get_args();
            if (!$args) {
                // empty means clear ALL auth bindings
                $this->auth = false;
            } else {
                $this->auth = $args;
            }
            return $this;
        }

        // if this route is found, but doesn't match the method or mode, allow it to try find the next matching route
        public function nextOnFail() {
            $this->passthru = true;
            return $this;
        }

        // turns passthru off
        public function stopOnFail() {
            $this->passthru = false;
            return $this;
        }

        /**
         * Creates an alternate route that also matches the regex of the parent route
         * ie. $app->route('home',  '/', function() use ($app) {))->also('/home'); will match '/' and '/home'
         * NOTE : buirlUrl will always match the first route defined this way
         */
        public function also() {
            $args = func_get_args();
            foreach ($args as $arg) {
                $pattern = $replace = array();

                // matches a valid PHP variable name wrapped by curly braces
                $pattern[] = App::GREEDY_PATTERN;
                // greedy as hell
                $replace[] = App::GREEDY_REPLACE;
                // matches a valid PHP variable name
                $pattern[] = App::VAR_PATTERN;
                // matches a slug, which can be upper, lower, can have hyphens, underscores, periods and numbers only
                $replace[] = App::VAR_REPLACE;

                // replace braces for optional params
                $route = str_replace(')', ')?', $arg);
                $route = preg_replace($pattern, $replace, $route);
                $route = "`^(?P<_base_>{$route})(|\/)$`i";
                unset($pattern, $replace, $args);

                $this->regex[] = $route;
                $this->expr[] = $arg;
            }
            return $this;
        }

        // allows us to "rename" route aliases - useful for building urls via the alias
        public function rename($alias) {
            $app = \Lollipop::getInstance()->App;
            unset($app->Routes[$this->alias]);

            $this->alias = $alias;
            $app->Routes[$alias] = $this;
            return $this;
        }

        /**
         * restricts the route to certain methods, ie. post, get, delete, put
         */
        public function allow() {
            $args = func_get_args();
            $methods = array();
            if ($args) {
                foreach ($args as $arg) {
                    switch (strtolower(trim($arg))) {
                        case \Lollipop::POST:
                        case \Lollipop::GET:
                        case \Lollipop::PUT:
                        case \Lollipop::DEL:
                            $methods[] = $arg;
                            break;

                        default:
                        // throw an exception here
                    }
                }
            }
            $this->allowed_methods = $methods;
            unset($methods, $args, $arg);
            return $this;
        }

        /**
         * restricts the route to certain modes, ie. cli, http and/or ajax
         */
        public function by() {
            $args = func_get_args();
            $modes = array();
            if ($args) {
                foreach ($args as $arg) {
                    switch (strtolower(trim($arg))) {
                        case \Lollipop::CLI:
                        case \Lollipop::AJAX:
                        case \Lollipop::HTTP:
                            $modes[] = $arg;
                            break;

                        default:
                        // throw an exception here
                    }
                }
            }
            $this->allowed_modes = $modes;
            unset($modes, $args, $arg);
            return $this;
        }

    }

}
