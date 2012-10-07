<?php

namespace Lollipop\Error {

    class MethodNotAllowed extends \Lollipop\Error {

        public $status_code = 405;
        public $title = 'Method Not Allowed';
        public $default_message = 'Method Not Allowed.';
        
        public function __construct($message = null, \Lollipop\Request $request = null, $code = 0, \Lollipop\Exception $previous = null) {
            $this->request = $request;
            if (is_null($message)) {
                // get allowed methods
                $allowed_methods = $this->request->route->allowed_methods;
                $allow = 'The resource you are trying to access only accepts the following methods: </p><ul>';
                foreach ($allowed_methods as $allowed) {
                    $allow .= '<li>' . strtoupper($allowed) . '</li>';
                }
                $allow .= '</ul>';
                // get allowed methods
                $allowed_methods = $this->request->route->allowed_modes;
                $allow .= '<p>In addition, the resource is restricted to the following requests:</p><ul>';
                foreach ($allowed_methods as $allowed) {
                    $allow .= '<li>' . strtoupper($allowed) . '</li>';
                }
                $allow .= '</ul><p>';
                $this->message = $allow;
            }
            parent::__construct($this->message, $request, $code, $previous);
        }

    }
}
