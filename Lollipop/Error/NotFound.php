<?php

namespace Lollipop\Error {

    class NotFound extends \Lollipop\Error {

        public $status_code = 404;
        public $title = 'Not Found';
        public $default_message = 'Sorry, the resource you were looking for either could not be found, or does not exist.';

    }
}
