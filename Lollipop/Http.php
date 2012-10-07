<?php

/**
 * Http Namespace
 */

namespace Lollipop {

    abstract class Http {

        // gets the type of http status - pass in the code
        public static function statusType($status_code) {
            // according to restpatterns.org, the first digit of a status code indicates its description
            $type = substr($status_code, 0, 1);
            switch ($type) {
                case 1:
                    return 'information';
                    break;

                case 2:
                    return 'success';
                    break;

                case 3:
                    return 'supplemental';
                    break;

                case 4:
                case 5:
                    return 'error';
                    break;

                default:
                    return 'unknown';
            }
        }

        // gets the http status codes - pass in the code to get the message
        public static function statusMessage($status_code) {

            $codes = array(
                100 => 'Continue',
                101 => 'Switching Protocols',
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                306 => '(Unused)',
                307 => 'Temporary Redirect',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Timeout',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Requested Range Not Satisfiable',
                417 => 'Expectation Failed',
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable', // use when the site is down for maintenance
                504 => 'Gateway Timeout',
                505 => 'HTTP Version Not Supported'
            );

            return (isset($codes[(int) $status_code])) ? $codes[(int) $status_code] : false;
        }

        public static function mimeType($lookup) {
            $types = array(
                'application/json' => 'json', // actually the only valid json mine-type
                'application/x-javascript' => 'json',
                'text/javascript' => 'json',
                'text/x-javascript' => 'json',
                'text/x-json' => 'json',
                'text/xml' => 'xml', // actually deprecated according to xml.org
                'application/xml' => 'xml', // correct mime-type
                'text/html' => 'html',
                'application/xhtml+xml' => 'xhtml',
                'text/css; charset' => 'css',
                'text/javascript' => 'js',
                'application/soap+xml' => 'soap',
                'application/x-www-form-urlencoded' => 'form',
                'multipart/form-data' => 'form',
                'text/yaml' => 'yaml',
                'text/plain' => 'text',
                'application/octet-stream' => 'file'
            );

            if (strpos($lookup, '/') === false) {
                // reverse the lookup
                $types = array_flip($types);
            }

            if (isset($types[$lookup])) {
                return $types[$lookup];
            }
            return false;
        }

        public static function contentType($type) {
            $types = array(
                'json' => 'application/json; charset=utf-8',
                'html' => 'text/html; charset=utf-8',
                'xhtml' => 'application/xhtml+xml; charset=utf-8',
                'css' => 'text/css; charset=utf-8',
                'js' => 'text/javascript; charset=utf-8',
                'xml' => 'application/xml; charset=utf-8',
                'soap' => 'application/soap+xml; charset=utf-8',
                'yaml' => 'text/plain',
                'form' => 'application/x-www-form-urlencoded'
            );

            if (isset($types[$type])) {
                return $types[$type];
            }
            return false;
        }

        /**
         * Lookup content type based on the content-type header
         * We only use this to determine how to process the data for JSON and XML requests
         * @param type $type
         * @return string|boolean 
         */
        public static function getContentType($input) {
            // usually, the content type is followed by a charset
            $cleaned = explode(';', $input);
            $type = trim($cleaned[0]);

            $types = array(
                'application/json' => 'json', // actually the only valid json mine-type
                'application/x-javascript' => 'json',
                'text/javascript' => 'json',
                'text/x-javascript' => 'json',
                'text/x-json' => 'json',
                'text/xml' => 'xml', // actually deprecated according to xml.org
                'application/xml' => 'xml', // correct mime-type
                'text/html' => 'html',
                'application/xhtml+xml' => 'xhtml',
                'text/css; charset' => 'css',
                'application/soap+xml' => 'soap',
                'application/x-www-form-urlencoded' => 'form',
                'multipart/form-data' => 'form',
                'text/yaml' => 'yaml',
                'text/plain' => 'text'
            );

            if (isset($types[$type])) {
                return $types[$type];
            }
            return false;
        }

        /*
         * Sets the http status header
         */

        public static function statusHeader($status_code) {
            // set our status
            $status_header = 'HTTP/1.1 ' . $status_code . ' ' . self::statusMessage($status_code);
            // set the status
            header($status_header);
        }

        // strip = true will return the expression with the found expression stripped
        public static function verb($expression) {
            $pattern = array(
                \Lollipop\Env::GET,
                \Lollipop\Env::POST,
                \Lollipop\Env::PUT,
                \Lollipop\Env::DELETE,
                \Lollipop\Env::OPTIONS,
                \Lollipop\Env::HEAD);

            foreach ($pattern as $find) {
                if (strpos($expression, $find) !== false) {
                    // our return will come back with an array of the Type and the cleaned up string
                    return array(
                        'verb' => $find,
                        'expression' => trim(str_replace($pattern, '', $expression))
                    );
                }
            }

            // if no verb is matched, return the default as GET
            return array(
                'verb' => \Lollipop\Env::GET,
                'expression' => $expression
            );
        }

        // allows us to make http requests to urls via the http protocol
        public static function request($url, $content = '', $content_type = false, $accept_type = false, $auth = false, $timeout = 240) {
            $response_headers = false;
            $response_data = false;

            try {
                $find = self::verb($url);
                // get our request method
                $verb = $find['verb'];
                $resource = $find['expression'];

                // build our header
                $headers = array();

                if ($accept_type) {
                    $headers[] = "Accept: " . self::contentType($accept_type);
                }

                switch (strtolower($verb)) {
                    case 'get':
                    case 'delete':
                        $resource .= '?' . $content;
                        $content_type = 'html';
                        break;

                    default:
                }

                if ($content_type) {
                    $headers[] = "Content-Type: " . self::contentType($content_type);
                } else {
                    $headers[] = "Content-Type: " . self::contentType('form');
                }

                // determine the length of our content
                $headers[] = "Content-Length: " . strlen($content);

                $header = (count($headers) > 1) ? implode("\r\n", $headers) : $headers[0];

                $opts = array(
                    'http' => array(
                        'method' => $verb,
                        'timeout' => $timeout,
                        'header' => "{$header}\r\n",
                        'content' => $content
                    )
                );

                $context = stream_context_create($opts);
                $response_data = file_get_contents($resource, false, $context);
                $response_headers = $http_response_header;
                $response_error = false;

                // we also pull through the status code
                $matches = array();
                preg_match('#HTTP/\d+\.\d+ (\d+)+ (.*)#', $response_headers[0], $matches);
                //w::pre($matches);
            } catch (Exception $e) {
                $response_error = array('type' => 'Exception', 'code' => $e->getCode(), 'message' => $e->getMessage());
                $response_data = $e->getMessage();
            }

            if (!$response_data) {
                $response_error = array('type' => 'HTTP', 'code' => $matches[1], 'message' => $matches[2]);
                $response_data = $matches[2];
            }

            return array(
                'headers' => $response_headers,
                'error' => $response_error,
                'http_status' => array('code' => $matches[1], 'message' => $matches[2]),
                'response' => $response_data
            );
        }

    }
}