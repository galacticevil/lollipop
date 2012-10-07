<?php

namespace Lollipop {

    class Error extends \Exception {

        public $status_code = 500;
        public $title = 'Server Error';
        public $default_message = '';
        public $exception = ''; // allows us to determine the class if necessary
        public $request = false;

        // Redefine the exception so message isn't optional
        public function __construct($message = null, \Lollipop\Request $request = null, $code = 0, Exception $previous = null) {
            if (is_null($message)) {
                $message = $this->default_message;
            }
            $this->exception = get_class();
            $this->request = $request;
            // make sure everything is assigned properly
            parent::__construct($message, $code, $previous);
        }

    }

}
