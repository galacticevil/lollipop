<?php

namespace Lollipop {
    /**
     * Autoloader class
     * Allows use to register namespaces with their own path, which allows us a fair level of flexibility
     * <pre><code>
     * <?php $loader = new \Lollipop\Autoloader('Lollipop', '/libs/Lollipop'); ?>
     * </code></pre>
     */
    class Autoloader {

        private $namespace = null;
        private $path = null;

        public function __construct($namespace, $path) {
            $this->namespace = $namespace;
            $this->path = \Lollipop::path($path);
            spl_autoload_register(array($this, 'register'));
        }

        public function unregister() {
            spl_autoload_unregister(array($this, 'register'));
        }

        // PSR-0 autoloader
        public function register($classname) {
            $classname = ltrim($classname, '\\');
            $filename = '';
            $ns = '';

            $explode = explode('\\', $classname);
            // our file will always be the last element
            $filename = array_pop($explode);

            if (isset($explode[0])) {
                // namespace is the first element
                $ns = array_shift($explode);
            }

            if ($ns == '' && stripos($filename, '_') !== false) {
                $fexplode = explode('_', $filename);
                $ns = array_shift($fexplode);
                if (isset($fexplode[0])) {
                    $filename = (count($fexplode) > 1) ? implode(LDS, $fexplode) : $fexplode[0];
                }
            }

            if ($ns == $this->namespace) {
                if (isset($explode[0])) {
                    $path = (count($explode) > 1) ? LDS . implode(LDS, $explode) : LDS . $explode[0];
                } else {
                    $path = null;
                }
                $filepath = $this->path . $path . LDS . str_replace('_', LDS, $filename) . '.php';

                // now we can test whether the file exists
                if (is_file($filepath)) {
                    require($filepath);
                    unset($path, $filename, $explode, $ns, $fexplode, $classname);
                    return true;
                }
            }
            unset($path, $filename, $explode, $ns, $fexplode, $classname, $filepath);
            return false;
        }

    }

}
