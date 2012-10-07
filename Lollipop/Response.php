<?php

namespace Lollipop {

// our response class
    class Response {

        //protected $__App = null;
        public $title = \Lollipop::SIGNATURE; // title
        public $status = 200; // status header to be sent
        public $content_type = false; // content type to be served
        public $data = array(); // data vars to be passed to the view    
        public $body = '';

        public function __toString() {
            return $this->respond();
        }

        public function respond($body = null) {

            // set our status heading
            \Lollipop\Http::statusHeader($this->status);
            //$charset = ($this->__App->config('settings.use_charset')) ? Http\most_acceptable(env('accept.charset')) : 'utf-8';
            $charset = 'utf-8;';
            header('Content-Type: ' . $this->content_type . '; charset=' . $charset);
            if ($body) {
                $this->body = $body;
            }
            return $this->body;
        }

    }

}
