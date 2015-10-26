<?php
    /**
     * Railpage site debugging
     *
     * @since   Version 3.9.1
     * @package Railpage
     * @author  Michael Greenhill
     */

    namespace Railpage;

    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use DateTimeZone;
    use DateInterval;
    use Railpage\Registry;

    class Debug {

        /**
         * Our debug array
         *
         * @since Version 3.9.1
         * @var array $log
         */

        private static $log;

        /**
         * Our error log array
         *
         * @since Version 3.9.1
         * @var array $errors
         */

        private static $errors;

        /**
         * Start a debug timer
         *
         * @since Version 3.9.1
         * @return float
         */

        public static function getTimer() {

            if (!defined("RP_DEBUG") || !RP_DEBUG) {
                return;
            }

            return microtime(true);
        }

        /**
         * Add an entry to the log
         *
         * @since Version 3.9.1
         *
         * @param string      $text
         * @param int|boolean $timer
         *
         * @return void
         */

        public static function logEvent($text, $timer = false) {

            if (!defined("RP_DEBUG") || !RP_DEBUG) {
                return;
            }

            if (filter_var($timer, FILTER_VALIDATE_FLOAT)) {
                $text = sprintf("%s - completed in %ss", $text, round(microtime(true) - $timer, 5));
            }

            self::$log[] = $text;

            // Temporary
            global $site_debug;
            $site_debug[] = $text;

            return;
        }

        /**
         * Record an exception
         *
         * @since Version 3.9.1
         *
         * @param \Exception $e
         *
         * @return void
         */

        public static function logException(Exception $e) {

            self::$errors[] = $e->__toString();

            return;

        }

        /**
         * Get the contents of this log
         *
         * @since Version 3.9.1
         * @return array
         */

        public static function getLog() {

            return self::$log;
        }

        /**
         * Print the pretty site debug log
         *
         * @since Version 3.9.1
         * @return void
         */

        public static function PrintPretty() {

            // temporary
            global $site_debug;

            foreach ($site_debug as $event) {
                preg_match("/([0-9]+\.[0-9]+)s/", $event, $matches);

                if (substr($event, 0, 1) === "#") {
                    echo "<br>";
                }

                if (isset( $matches[1] )) {
                    if (floatval($matches[1]) >= 0.1) {
                        echo "<span style='background:red;color:whitesmoke;'>" . $event . "</span>";
                    } elseif (floatval($matches[1]) >= 0.05 && floatval($matches[1]) < 0.1) {
                        echo "<span style='background:orange;color:whitesmoke;'>" . $event . "</span>";
                    } else {
                        echo $event;
                    }
                } else {
                    echo $event;
                }

                echo "<br>";
            }
        }

        /**
         * Record a new instance of the specified class
         *
         * @since Version 3.9.1
         * @return void
         *
         * @param string $object
         */

        public static function RecordInstance($object = null, $id = false) {

            $trace = debug_backtrace();

            $object = is_null($object) ? $trace[1]['class'] : $object;

            $idstring = $id === false ? "" : "(" . $id . ") ";

            $message = "####  Instantiating new instance of " . $object . $idstring . " from " . $trace[1]['file'] . " on line " . $trace[1]['line'] . ", called from " . $trace[1]['class'] . "::" . $trace[1]['function'] . "()  ####";

            self::logEvent($message);

        }

        /**
         * Log a command line event
         *
         * @since Version 3.9.1
         *
         * @param string      $text
         * @param int|boolean $timer
         *
         * @return void
         */

        public static function LogCLI($text, $timer = false) {

            if (php_sapi_name() != "cli" || class_exists("PHPUnit_Framework_TestCase")) {
                return;
            }

            if (filter_var($timer, FILTER_VALIDATE_FLOAT)) {
                $text = sprintf("%s - completed in %ss", $text, round(microtime(true) - $timer, 5));
            }

            print $text . "\n";

            return;

        }

        /**
         * Save an error message
         *
         * @since Version 3.10.0
         *
         * @param string|Exception $error
         *
         * @return void
         */

        public static function SaveError($error) {

            if ($error instanceof Exception) {
                $error = $error->getMessage();
            }

            $Registry = Registry::getInstance();

            try {
                $Error_Handler = $Registry->get("errorlogger");
            } catch (Exception $e) {
                require_once( "includes" . DS . "error.class.php" );
                $Error_Handler = new Error_Handler;

                $Registry->set("errorlogger", $Error_Handler);
            }

            $Error_Handler->save($error);

            return;

        }

        /**
         * Output a variable to the browser
         *
         * @param mixed $array
         */

        public static function printArray($array) {

            echo "<pre style='font-family:consolas;font-size:14px;line-height:1.1;'>";

            if (is_array($array)) {
                print_r($array);
            } else {
                var_dump($array);
            }

            echo "</pre>";
        }

    }