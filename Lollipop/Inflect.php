<?php
namespace Lollipop {
    
class Inflect {

        /**
         *
         * @var array <string, string>
         */
        protected static $__plural = array(
            '/(quiz)$/i' => "$1zes",
            '/^(ox)$/i' => "$1en",
            '/([m|l])ouse$/i' => "$1ice",
            '/(matr|vert|ind)ix|ex$/i' => "$1ices",
            '/(x|ch|ss|sh)$/i' => "$1es",
            '/([^aeiouy]|qu)y$/i' => "$1ies",
            '/(hive)$/i' => "$1s",
            '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
            '/(shea|lea|loa|thie)f$/i' => "$1ves",
            '/sis$/i' => "ses",
            '/([ti])um$/i' => "$1a",
            '/(tomat|potat|ech|her|vet)o$/i' => "$1oes",
            '/(bu)s$/i' => "$1ses",
            '/(alias)$/i' => "$1es",
            '/(octop)us$/i' => "$1i",
            '/(ax|test)is$/i' => "$1es",
            '/(us)$/i' => "$1es",
            '/s$/i' => "s",
            '/$/' => "s"
        );

        /**
         *
         * @var array <string, string>
         */
        protected static $__singular = array(
            '/(quiz)zes$/i' => "$1",
            '/(matr)ices$/i' => "$1ix",
            '/(vert|ind)ices$/i' => "$1ex",
            '/^(ox)en$/i' => "$1",
            '/(alias)es$/i' => "$1",
            '/(octop|vir)i$/i' => "$1us",
            '/(cris|ax|test)es$/i' => "$1is",
            '/(shoe)s$/i' => "$1",
            '/(o)es$/i' => "$1",
            '/(bus)es$/i' => "$1",
            '/([m|l])ice$/i' => "$1ouse",
            '/(x|ch|ss|sh)es$/i' => "$1",
            '/(m)ovies$/i' => "$1ovie",
            '/(s)eries$/i' => "$1eries",
            '/([^aeiouy]|qu)ies$/i' => "$1y",
            '/([lr])ves$/i' => "$1f",
            '/(tive)s$/i' => "$1",
            '/(hive)s$/i' => "$1",
            '/(li|wi|kni)ves$/i' => "$1fe",
            '/(shea|loa|lea|thie)ves$/i' => "$1f",
            '/(^analy)ses$/i' => "$1sis",
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => "$1$2sis",
            '/([ti])a$/i' => "$1um",
            '/(n)ews$/i' => "$1ews",
            '/(h|bl)ouses$/i' => "$1ouse",
            '/(corpse)s$/i' => "$1",
            '/(us)es$/i' => "$1",
            '/s$/i' => ""
        );

        /**
         *
         * @var array <string, string>
         */
        protected static $__premapped = array(
            'move' => 'moves',
            'foot' => 'feet',
            'goose' => 'geese',
            'sex' => 'sexes',
            'child' => 'children',
            'man' => 'men',
            'tooth' => 'teeth',
            'person' => 'people',
            'text' => 'text',
            'model' => 'models',
            'extension' => 'extensions',
            'plugin' => 'plugins',
            'controller' => 'controllers',
            'factory' => 'factories',
            'helper' => 'helpers',
            'interface' => 'interfaces',
            'service' => 'services',
            'tool' => 'tools',
            'library' => 'libraries',
            'view' => 'views',
            'hook' => 'hooks',
            'class' => 'classes',
            'client' => 'clients',
            'language' => 'languages',
            'module' => 'modules',
            'theme' => 'themes',
            'site' => 'sites',
        );

        /**
         *
         * @var array <string>
         */
        protected static $__uncountable = array(
            'sheep',
            'fish',
            'deer',
            'series',
            'species',
            'money',
            'rice',
            'information',
            'equipment',
            'text',
            'mvc',
            'runtime',
            'data'
        );

        /**
         * Takes a singular and returns the plural
         *
         * @param type $string
         * @return type 
         */
        public static function many($string) {
            // save some time in the case that singular and plural are the same
            if (in_array(strtolower($string), self::$__uncountable)) {
                return $string;
            }

            // check for premapped singular forms
            if (isset(self::$__premapped[strtolower(trim($string))])) {
                return self::$__premapped[strtolower(trim($string))];
            }

            // check for matches using regular expressions
            foreach (self::$__plural as $pattern => $result) {
                if (preg_match($pattern, $string)) {
                    return preg_replace($pattern, $result, $string);
                }
            }
            return $string;
        }

        /**
         * Takes a plural, and returns the singular
         *
         * @param string $string
         * @return string
         */
        public static function one($string) {
            // save some time in the case that singular and plural are the same
            if (in_array(strtolower($string), self::$__uncountable)) {
                return $string;
            }

            // check for premapped plural forms
            $premapped = array_flip(self::$__premapped);
            if (isset($premapped[strtolower(trim($string))])) {
                return $premapped[strtolower(trim($string))];
            }
            // gc
            unset($premapped);

            // check for matches using regular expressions
            foreach (self::$__singular as $pattern => $result) {
                if (preg_match($pattern, $string)) {
                    return preg_replace($pattern, $result, $string);
                }
            }
            return $string;
        }

        /**
         * Takes a singular, checks the value of count to determine whether to return the singular or plural
         *
         * @param int $count
         * @param string $string
         * @return string 
         */
        public static function pluralize($count, $string) {
            if ($count == 1) {
                return $string;
            } else {
                return self::many($string);
            }
        }
    }
}
