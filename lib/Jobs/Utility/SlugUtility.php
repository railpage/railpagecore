<?php
    /**
     * Get an ID from a given slug
     *
     * @since   Version 3.9.1
     * @package Railpage
     * @author  Michael Greenhill
     */

    namespace Railpage\Jobs\Utility;

    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Organisations\Organisation;
    use Exception;
    use DateTime;
    use Zend_Db_Expr;

    class SlugUtility {

        /**
         * Get the ID
         *
         * @since Version 3.9.1
         *
         * @param string $type
         * @param string $slug
         *
         * @return int|bool
         */

        public static function getID($type, $slug) {

            $Database = (new AppCore)->getDatabaseConnection();

            $query = "SELECT jn_" . $type . "_id FROM jn_" . $type . "s WHERE jn_" . $type . "_name = ?";

            if ($id = $Database->fetchOne($query, $slug)) {
                return $id;
            }

            return false;

        }
    }