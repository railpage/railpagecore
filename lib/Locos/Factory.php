<?php
    /**
     * Factory code pattern - return an instance of blah from the registry, Redis, Memcached, etc...
     *
     * @since   Version 3.9.1
     * @package Railpage
     * @author  Michael Greenhill
     */

    namespace Railpage\Locos;

    use Exception;
    use Railpage\AppCore;
    use Railpage\Registry;

    class Factory {

        /**
         * Do we want to use Redis to cache some of these objects?
         *
         * @since Version 3.9.1
         * @const boolean USE_REDIS
         */

        const USE_REDIS = true; // causing errors

        /**
         * Return a locomotive class
         *
         * @since Version 3.9.1
         * @return \Railpage\Locos\LocoClass
         *
         * @param int|string|bool $id Integer or string are valid values - defaults to bool false
         *
         * @throws \Exception if loco class id could not be found
         * @throws \Exception if an invalid class ID was supplied
         */

        public static function CreateLocoClass($id = false) {

            $Redis = AppCore::getRedis();
            $Registry = Registry::getInstance();

            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                $id = Utility\LocomotiveUtility::getClassId($id);
            }

            if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
                $regkey = sprintf(LocoClass::REGISTRY_KEY, $id);

                try {
                    $LocoClass = $Registry->get($regkey);
                } catch (Exception $e) {
                    $cachekey = sprintf(LocoClass::CACHE_KEY, $id);

                    if (!self::USE_REDIS || !$LocoClass = $Redis->fetch($cachekey)) {
                        $LocoClass = new LocoClass($id);

                        if (self::USE_REDIS) {
                            $Redis->save($cachekey, $LocoClass);
                        }
                    }

                    $Registry->set($regkey, $LocoClass);
                }

                if (filter_var($LocoClass->id, FILTER_VALIDATE_INT)) {
                    return $LocoClass;
                }

                throw new Exception(sprintf("Locomotive class id %s could not be found", $id));
            }

            throw new Exception("An invalid locomotive class ID was supplied");

        }

        /**
         * Return a locomotive
         *
         * @since Version 3.9.1
         * @return \Railpage\Locos\Locomotive
         *
         * @param int|bool    $id
         * @param string|bool $class
         * @param string|bool $number
         */

        public static function CreateLocomotive($id = false, $class = false, $number = false) {

            $Redis = AppCore::getRedis();
            $Registry = Registry::getInstance();

            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                $id = Utility\LocomotiveUtility::getLocoId($class, $number);
            }

            if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
                $regkey = sprintf(Locomotive::REGISTRY_KEY, $id);

                try {
                    $Loco = $Registry->get($regkey);
                } catch (Exception $e) {
                    $cachekey = sprintf(Locomotive::CACHE_KEY, $id);

                    if (!self::USE_REDIS || !$Loco = $Redis->fetch($cachekey)) {
                        $Loco = new Locomotive($id);

                        if (self::USE_REDIS) {
                            $Redis->save($cachekey, $Loco);
                        }
                    }

                    $Registry->set($regkey, $Loco);
                }

                return $Loco;
            }

            return false;

        }

        /**
         * Return a thing
         *
         * @since Version 3.9.1
         * @return mixed
         *
         * @param string     $Object An instance of Locomotive, Class, Livery etc to be created
         * @param int|string $id
         */

        public static function Create($Object, $id) {

            $class = sprintf("\Railpage\Locos\%s", $Object);
            $regkey = sprintf("railpage:locos.%s=%d", strtolower($Object), $id);

            $Registry = Registry::getInstance();

            try {
                $Object = $Registry->get($regkey);
            } catch (Exception $e) {

                $Object = new $class($id);

                $Registry->set($regkey, $Object);
            }

            return $Object;

        }

    }