<?php

namespace Lollipop {

    function browserInfo() {
        // determine the info first
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        $browser = array();

        if (strchr($useragent, "MSIE")) {
            $browser['browser'] = 'IE';
            preg_match('|MSIE ([0-9]\.[0-9]); |', $useragent, $match);

            $browser['full_version'] = $match[1];
            $short = explode('.', $browser['full_version']);
            $browser['version'] = $short[0];
            // TODO : Fix this issue as sometimes there is no fucken space after Firefox - FIXED
        } else if (strchr($useragent, "Firefox")) {
            $browser['browser'] = 'Firefox';
            preg_match('|Firefox/(.*)|', $useragent, $match);

            $browser['full_version'] = $match[1];
            $short = explode('.', $browser['full_version']);
            $browser['version'] = $short[0];
        } else if (strchr($useragent, "Opera")) {
            $browser['browser'] = 'Opera';
            preg_match('|Opera/(.*) \(|', $useragent, $match);

            $browser['full_version'] = $match[1];
            $short = explode('.', $browser['full_version']);
            $browser['version'] = $short[0];
        } else if (strchr($useragent, "Chrome")) {
            $browser['browser'] = 'Chrome';
            preg_match('|Chrome/(.*) |', $useragent, $match);

            $browser['full_version'] = $match[1];
            $short = explode('.', $browser['full_version']);
            $browser['version'] = $short[0];
        }
        #todo : find a way to test for Safari
        else if (strchr($useragent, "Safari")) {
            $browser['browser'] = 'Safari';
            preg_match('|Version/(.*) |', $useragent, $match);

            $browser['full_version'] = $match[1];
            $short = explode('.', $browser['full_version']);
            $browser['version'] = $short[0];
        } else {
            $browser['browser'] = 'Unknown';
            $browser['full_version'] = '1.0';
            $browser['version'] = 1;
        }
        unset($match, $short, $useragent);
        // no expression returns the full browser version
        return array(
            'info' => $browser['browser'] . ' ' . $browser['full_version'],
            'name' => $browser['browser'],
            'version' => $browser['version'],
            'build' => $browser['full_version']
        );
    }

    function browser($expression = false) {
        $browser = \Lollipop::env('browser');
        if (!$expression) {
            return $browser['info'];
        }
        // explode the expression to determine the parts
        // eg. IE lt 9
        $expr = explode(' ', $expression);
        // count the expression parts, two means equals version
        // one means just the browser matches
        $type = count($expr);
        switch ($type) {
            case 1:
                if (trim($expr[0]) == 'name') {
                    return $browser['name'];
                } else if (trim($expr[0]) == 'version') {
                    return $browser['version'];
                } else if (strtolower(trim($expr[0])) == strtolower($browser['name'])) {
                    return true;
                }
                return false;
                break;

            case 2:
                if (strtolower(trim($expr[0])) == strtolower($browser['browser']) && trim($expr[1]) == $browser['version']) {
                    return true;
                }
                return false;
                break;

            case 3:
                // determine the comparison
                switch (trim($expr[1])) {
                    // less than
                    case 'lt':
                        if (strtolower(trim($expr[0])) == strtolower($browser['name']) && trim($expr[2]) > $browser['version']) {
                            return true;
                        }
                        return false;
                        break;

                    // greater than
                    case 'gt':
                        if (strtolower(trim($expr[0])) == strtolower($browser['name']) && trim($expr[2]) < $browser['version']) {
                            return true;
                        }
                        return false;
                        break;

                    // less than or equal to
                    case 'lte':
                        if (strtolower(trim($expr[0])) == strtolower($browser['name']) && trim($expr[2]) >= $browser['version']) {
                            return true;
                        }
                        return false;
                        break;

                    // greater than or equal to
                    case 'gte':
                        if (strtolower(trim($expr[0])) == strtolower($browser['name']) && trim($expr[2]) <= $browser['version']) {
                            return true;
                        }
                        return false;
                        break;

                    // equal to
                    case 'eq':
                        if (strtolower(trim($expr[0])) == strtolower($browser['name']) && trim($expr[2]) == $browser['version']) {
                            return true;
                        }
                        return false;
                        break;

                    default:
                        return false;
                        break;
                }
                break;

            default:
                return $browser['info'];
                break;
        }
    }

    /** new functions * */

    /**
     * takes html, and converts it to formatted text
     * Inspired by html2text by Jon Abernathy <jon@chuggnutt.com>
     */
    function textify($html, $wordwrap_at = 75) {
        $search = array(
            // Remove invisible content
            '/<head[^>]*?>.*?<\/head>/siu',
            '/<style[^>]*?>.*?<\/style>/siu',
            '/<script[^>]*?.*?<\/script>/siu',
            '/<object[^>]*?.*?<\/object>/siu',
            '/<embed[^>]*?.*?<\/embed>/siu',
            '/<applet[^>]*?.*?<\/applet>/siu',
            '/<noframes[^>]*?.*?<\/noframes>/siu',
            '/<noscript[^>]*?.*?<\/noscript>/siu',
            '/<noembed[^>]*?.*?<\/noembed>/siu',
            "/\r/", // Non-legal carriage return
            "/[\n\t]+/", // Newlines and tabs
            '/[ ]{2,}/', // Runs of spaces, pre-handling
            '/<h[1][^>]*>(.*?)<\/h[1]>/ie', // h1
            '/<h[2][^>]*>(.*?)<\/h[2]>/ie', // h2
            '/<h[3][^>]*>(.*?)<\/h[3]>/ie', // h3
            '/<h[456][^>]*>(.*?)<\/h[456]>/ie', // h4 - h6
            '/<p[^>]*>/i', // p
            '/<br[^>]*>/i', // br
            '/<strong[^>]*>(.*?)<\/strong>/ie', // strong
            '/<em[^>]*>(.*?)<\/em>/ie', // em
            '/(<ul[^>]*>|<\/ul>)/i', // ul
            '/(<ol[^>]*>|<\/ol>)/i', // ol
            '/<li[^>]*>(.*?)<\/li>/i', // li
            '/<li[^>]*>/i', // nesting li
            '/<hr[^>]*>/ie', // hr
            '/(<table[^>]*>|<\/table>)/i', // table
            '/(<tr[^>]*>|<\/tr>)/i', // tr
            '/<td[^>]*>(.*?)<\/td>/i', // td
            '/<th[^>]*>(.*?)<\/th>/ie', // th
            '/&(nbsp|#160);/i', // Non-breaking space
            '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
            '/&(apos|rsquo|lsquo|#8216|#8217);/i', // Single quotes
            '/&gt;/i', // Greater-than
            '/&lt;/i', // Less-than
            '/&(amp|#38);/i', // Ampersand
            '/&(copy|#169);/i', // Copyright
            '/&(trade|#8482|#153);/i', // Trademark
            '/&(reg|#174);/i', // Registered
            '/&(mdash|#151|#8212);/i', // mdash
            '/&(ndash|minus|#8211|#8722);/i', // ndash
            '/&(bull|#149|#8226);/i', // Bullet
            '/&(pound|#163);/i', // Pound sign
            '/&(euro|#8364);/i', // Euro sign
            '/&[^&;]+;/i', // Unknown
            '/[ ]{3,}/' // Runs of spaces, post-handling
        );

        $replace = array(
            '', '', '', '', '', '', '', '', '', // invisibles 
            '', // Non-legal carriage return
            ' ', // Newlines and tabs
            ' ', // Runs of spaces, pre-handling
            "\"\n\n\" . strtoupper(\"\\1\") . \"\n\" . str_repeat('*', strlen(\"\\1\")) . \"\n\n\"", // h1
            "\"\n\n\\1\n\" . str_repeat('=', strlen(\"\\1\")) . \"\n\n\"", // h2
            "\"\n\n\\1\n\" . str_repeat('-', strlen(\"\\1\")) . \"\n\n\"", // h3
            "strtoupper(\"\n\n\\1\n\n\")", // h4 - h6
            "\n\n", // p
            "\n", // br
            'strtoupper("*\\1*")', // strong
            'strtoupper("\\1")', // em
            "\n\n", // ul
            "\n\n", // ol
            "   - \\1\n", // li
            "\n   - ", // nesting li
            "\"\n\" . str_repeat('-', {$wordwrap_at}) . \"\n\"", // hr
            "\n\n", // table
            "\n", // tr
            "\t\\1\n", // td
            "strtoupper(\"\t\\1\n\")", // th
            ' ', // Non-breaking space
            '"', // Double quotes
            "'", // Single quotes
            '>', '<', '&', '(c)', '(tm)', '(R)', '--', '-', '*', '£', // misc
            'EUR', // Euro sign. € ?
            '', // Unknown/unhandled entities
            '   '  // Runs of spaces, post-handling
        );

        $text = trim(stripslashes($html));
        // Run our defined search-and-replace
        $text = preg_replace($search, $replace, $text);
        // Strip any other HTML tags
        $text = strip_tags($text);
        // Bring down number of empty lines to 2 max
        $text = preg_replace("/\n\s+\n/", "\n\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);
        // If width is 0 or less, don't wrap the text.
        if ($wordwrap_at > 0) {
            $text = wordwrap($text, $wordwrap_at);
        }
        return $text;
    }

    /**
     * Return the contents of the target file
     * The method will also substitute any variables defined within the file if they are defined in the $vars array
     * 
     * @param string $file
     * The target file or path to the target file
     * @param mixed $vars
     * An associative array of variables to be substituded into the output, or false to ignore
     * @return mixed
     * Will return the target content as a string, or false on failure 
     */
    function file_to_string($file, $vars = false) {
        if (is_file($file)) {

            ob_start();
            if (isset($vars) && $vars) {
                // Load variables
                foreach ($vars as $key => $value) {
                    $$key = $value;
                }
            }

            include($file);
            $out = ob_get_contents();
            ob_end_clean();
            return $out;
        } else {
            return "";
        }
    }

    /**
     * Gets the file extension of a file
     * Will return the file extension of any file passed to it. You have the option of returning it with or without the dot.
     * 
     * @param string $filename
     * The filename or path to a file
     * @param bool $dot
     * True to return the dot, false not to
     * @param bool $lowercase
     * True to force the return value to lowercase, false to return as is
     * @return string
     * The file extension 
     */
    function file_ext($filename, $dot = true, $lowercase = true) {
        $file_ext = strrchr($filename, '.');
        if ($file_ext) {
            if (!$dot) {
                $file_ext = str_replace('.', '', $file_ext);
            }
            if ($lowercase) {
                $file_ext = strtolower($file_ext);
            }
        }
        return $file_ext;
    }

    /**
     * Generates a slug for use in urls from a string.
     * 
     * @param string $input
     * The input string.
     * @param boolean $lowercase
     * Flag whether the return value should be lowercased.
     * @return string
     * The slug, with spaces hyphenated and lowercased as desired.
     */
    function slug($input, $lowercase = false) {
        $slug = str_replace(' ', '-', $input);
        $slug = preg_replace("/[^a-zA-Z0-9_\-\.]+/", "", strip_tags($slug));
        return ($lowercase) ? strtolower($slug) : $slug;
    }

    // uses the stringTool to build a string (based on c#'s StringBuilder object
    function str_build($input) {
        return new \Lollipop\String\Builder($input);
    }

    function split_by_caps($string, $ucfirst = true, $glue = false) {
        $pattern = "/(.)([A-Z])/";
        $replacement = "\\1 \\2";
        $return = ($ucfirst) ?
                ucfirst(preg_replace($pattern, $replacement, $string)) :
                strtolower(preg_replace($pattern, $replacement, $string));
        return ($glue) ? str_replace(' ', $glue, $return) : $return;
    }

    function return_between($start, $end, $string, $cut = false, $case_insensitive = true) {
        $start = ($case_insensitive) ? strtolower($start) : $start;
        $end = ($case_insensitive) ? strtolower($end) : $end;
        $len = strlen($start);
        $scheck = ($case_insensitive) ? strtolower($string) : $string;
        if ($len > 0) {
            $pos1 = strpos($scheck, $start);
        } else {
            $pos1 = 0;
        }
        if ($pos1 !== false) {
            if ($end == '') {
                return substr($string, $pos1 + $len);
            }
            $pos2 = strpos(substr($scheck, $pos1 + $len), $end);
            if ($pos2 !== false) {
                return substr($string, $pos1 + $len, $pos2);
            }
        }
        return '';
    }

    # Convert a stdClass to an Array.

    function array_from_object(\stdClass $object) {
        # Typecast to (array) automatically converts stdClass -> array.
        $object = (array) $object;

        # Iterate through the former properties looking for any stdClass properties.
        # Recursively apply (array).
        foreach ($object as $key => $value) {
            if (is_object($value) && get_class($value) === 'stdClass') {
                $object[$key] = array_from_object($value);
            }
        }
        return $object;
    }

    # Convert an Array to stdClass.

    function array_to_object(array $array) {
        # Iterate through our array looking for array values.
        # If found recurvisely call itself.
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_to_object($value);
            }
        }

        # Typecast to (object) will automatically convert array -> stdClass
        return (object) $array;
    }

    /**
     * Here is an improved version of the keyByIndex function below. 
     * It is simpler and faster, especially on large arrays as it runs in O(1) instead of O(n).
     * Also, keyByIndex($a, -1) will return the last key, etc. 
     * @param array $a
     * @param type $pos
     * @return type 
     */
    function array_key_by_index(array $a, $pos) {
        $temp = array_slice($a, $pos, 1, true);
        $return = key($temp);
        unset($temp);
        return $return;
    }

    /**
     * Merge the contents of two or more stdClass objects together into the target object.
     * When we supply a target and a second object to w::extend(), properties from all of the objects are added to the target object 
     * - patterned after jQuery $.extend().
     * 
     * If the first argument is an obj, it will be returned as an obj, else it will be returned as an array 
     */
    function extend($target, $object) {
        $args = func_get_args();
        $out = array_shift($args);
        if (is_object($out)) {
            $is_obj = true;
            // convert it to an array
            $out = array_from_object($out);
        } else {
            $is_obj = false;
        }

        if ($args) {
            foreach ($args as $arg) {
                if (is_object($arg)) {
                    // convert it to an obj
                    $arr = array_from_object($arg);
                }
                // do our merge
                $out = merge($out, $arg);
            }
            // based on type, return the correct type
            return ($is_obj) ? array_to_object($out) : $out;
        }
    }

    /*
     * Here's an array_slice function for associative arrays. 
     * It slices by array key from and including that key. 
     * If the $length is a string it is assumed to be another array key and the array is sliced up 
     * to but not including the end key otherwise it slices that length.
     */

    function array_slice_assoc($array, $key, $length = null, $preserve_keys = true) {
        $offset = array_search($key, array_keys($array));

        if (is_string($length)) {
            $length = array_search($length, array_keys($array)) - $offset;
        }

        return array_slice($array, $offset, $length, $preserve_keys);
    }

    // very good recursive array merge, does everything the right way
    // Always use this
    function merge(array $a1, array $a2) {
        $result = array();
        for ($i = 0, $total = func_num_args(); $i < $total; $i++) {
            // Get the next array
            $arr = func_get_arg($i);

            // Is the array associative?
            $assoc = array_is_assoc($arr);

            foreach ($arr as $key => $val) {
                if (isset($result[$key])) {
                    if (is_array($val) AND is_array($result[$key])) {
                        if (array_is_assoc($val)) {
                            // Associative arrays are merged recursively
                            $result[$key] = merge($result[$key], $val);
                        } else {
                            // Find the values that are not already present
                            $diff = array_diff($val, $result[$key]);

                            // Indexed arrays are merged to prevent duplicates
                            $result[$key] = array_merge($result[$key], $diff);
                        }
                    } else {
                        if ($assoc) {
                            // Associative values are replaced
                            $result[$key] = $val;
                        } elseif (!in_array($val, $result, TRUE)) {
                            // Indexed values are added only if they do not yet exist
                            $result[] = $val;
                        }
                    }
                } else {
                    // New values are added
                    $result[$key] = $val;
                }
            }
        }

        return $result;
    }

    // merges multidimensional arrays with overwrites - recursive
    function merge_array($arr1, $arr2) {
        foreach ($arr2 as $k => $v) {
            if (array_key_exists($k, $arr1) && is_array($v)) {
                $arr1[$k] = merge_array($arr1[$k], $arr2[$k]);
            } else {
                $arr1[$k] = $v;
            }
        }
        return $arr1;
    }

    /**
     * Determines whether an array is Associative or not.
     * 
     * We do this by comparing the array_keys of the keys, if they match, then its not associative
     * @param array $array
     * Array to be tested
     * @return bool
     * Returns true or false
     */
    function array_is_assoc(array $array) {
        return (array_keys(array_keys($array)) !== array_keys($array)) ? true : false;
    }

    /**
     * Allows us to "flatten" a multi-dimensional array to a single dimenional array 
     */
    function array_flatten($array) {
        $return = array();
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $return += array_flatten($v);
            } else {
                $return[$k] = $v;
            }
        }
        return $return;
    }

    /**
     * Method to obtain a value from a multidimensional array using dot notation
     * 2x as fast as Kohana's dot implementation, and about one fifth the memory
     * @param type $array
     * @param type $query
     * @return null 
     */
    function dot($array, $query = null) {
        if (!is_array($array) && !is_object($array) && !$array instanceof Traversable) {
            // This is not an array, or an array-like object!
            return null;
        }
        if ($query === null) {
            return $array;
        }
        $parts = explode('.', $query);
        $levels = count($parts);

        foreach ($parts as $k) {
            if (isset($array[$k])) {
                $array = $array[$k];
                $levels--;
                if ($levels && is_string($array)) {
                    // if this tier is a string, and we still have levels left over, then we return a null
                    unset($levels, $parts, $k, $query);
                    // couldn't find the value requested
                    return null;
                }
            } else {
                unset($levels, $parts, $k, $query);
                // couldn't find the value requested
                return null;
            }
        }
        unset($levels, $parts, $k, $query);
        return $array;
    }

    // take the data, convert it to an xml string, and return an XML string
    function array_convert($arr, $xml = null, $root = 'sData') {
        $first = $xml;
        if (is_null($xml)) {
            $xml = new \SimpleXMLElement('<' . $root . '/>');
        }
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                if (is_int($k)) {
                    // make the root a singular
                    $nroot = Inflect::one($root);
                    $nroot = ($nroot == $root) ? $nroot . '_data' : $nroot;
                    array_convert($v, $xml->addChild($nroot), $k);
                } else {
                    array_convert($v, $xml->addChild($k), $k);
                }
            } else {
                if (is_int($k)) {
                    // make the root a singular
                    $nroot = Inflect::one($root);
                    $nroot = ($nroot == $root) ? $nroot . '_data' : $nroot;
                    $xml->addChild($nroot, $v);
                } else {
                    $xml->addChild($k, $v);
                }
            }
        }
        return (is_null($first)) ? $xml->asXML() : $xml;
    }

    /**
     * Encodes an array as JSON, but will have the root as 'data' or $root
     * @param type $data
     * @param type $root
     * @return type 
     */
    function json_encode($data, $root = 'sData') {
        // we use convert it, encode it, decode it again, and finally recode it to get the right structure
        $json = array_convert($data, null, $root);
        $xml = simplexml_load_string($json);
        $json = \json_encode($xml);
        $obj = new \stdClass();
        $obj->$root = \json_decode($json);

        // gc
        unset($json, $xml, $root);
        return \json_encode($obj);
    }

    /**
     * A simple wrapper to the PHP default for convenience sake
     * @param type $data
     * @return type 
     */
    function json_decode($data) {
        return \json_decode($data, true);
    }

    function file_get_json($filepath, $as_array = false) {
        if (is_file($filepath)) {
            $json = file_get_contents($filepath);
            $pattern[] = '#<\?(?:php)?(.*?)\?>#s'; // remove any php tags
            $replace[] = '';
            $pattern[] = '!/\*.*?\*/!s';
            $replace[] = '';
            $pattern[] = '/\n\s*\n/';
            $replace[] = "\n";
            $json = preg_replace($pattern, $replace, $json);
            return \json_decode($json, $as_array);
        }
        return false;
    }

    /**
     * Writes a json file, but keeps indentations
     * @param type $json
     * @return string
     */
    function file_put_json($filepath, $data) {
        // if the filepath ends with a .php, we are creating a secure file
        $json = stripslashes(\json_encode($data));
        if (file_ext($filepath) == '.php') {
            $result = "<?php " . \Lollipop::SECURED . "?>\n";
        } else {
            $result = '';
        }
        $pos = 0;
        $str_length = \strlen($json);
        $indent = "    ";
        $newline = "\n";
        $previous = '';
        $outside_quotes = true;

        for ($i = 0; $i <= $str_length; $i++) {
            // Grab the next character in the string
            $char = substr($json, $i, 1);
            // Are we inside a quoted string?
            if ($char == '"' && $previous != '\\') {
                $outside_quotes = !$outside_quotes;
            }
            // If this character is the end of an element, 
            // output a new line and indent the next line
            else if (($char == '}' || $char == ']') && $outside_quotes) {
                $result .= $newline;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indent;
                }
            }
            // Add the character to the result string
            $result .= $char;
            // If the last character was the beginning of an element, 
            // output a new line and indent the next line
            if (($char == ',' || $char == '{' || $char == '[') && $outside_quotes) {
                $result .= $newline;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indent;
                }
            }
            $previous = $char;
        }
        return \file_put_contents($filepath, $result);
    }

    /**
     * Will encode an array as XML
     * @param type $data
     * @param type $root
     * @return type 
     * The root will be $root, which defaults to 'data'
     */
    function xml_encode($arr, $root = 'sData') {
        return array_convert($arr, null, $root);
    }

    /**
     * Convert XML to Associative Array
     *
     * @param string $contents Either a filename or actual contents of xml as string
     * @param bool $get_attributes Optional.
     * @param string $priority
     * @return array 
     * the root will normally be 'data'
     */
    function xml_decode($xml) {
        $xml = simplexml_load_string($xml);
        // we want the root element
        $root = $xml->getName();
        $obj = new \stdClass();
        $obj->$root = $xml;
        $json = \json_encode($obj);
        unset($xml, $root, $obj);
        return json_decode($json, true);
    }

}
