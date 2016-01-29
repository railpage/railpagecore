<?php
    /**
     * Job location object
     *
     * @since   Version 3.7
     * @package Railpage
     * @author  Michael Greenhill
     */

    namespace Railpage\Jobs;

    use Exception;
    use DateTime;

    /**
     * Job location class
     *
     * @since   Version 3.7.0
     * @version 3.8.7
     */
    class Location extends Jobs {

        /**
         * Location ID
         *
         * @var int $id
         */

        public $id;

        /**
         * Location name
         *
         * @var string $name
         */

        public $name;

        /**
         * Location parent ID
         *
         * @var int $parent_id ;
         */

        public $parent_id;

        /**
         * Constructor
         *
         * @param int|string $id
         */

        public function __construct($id) {

            parent::__construct();

            if (filter_var($id, FILTER_VALIDATE_INT)) {
                $this->id = $id;

                $this->fetch();
            } elseif (is_string($id)) {
                if (!$this->id = Utility\SlugUtility::getID("location", $id)) {
                    $this->name = $id;
                    $this->parent_id = 0;
                    $this->commit();
                }
            }
        }

        /**
         * Fetch the location object
         *
         * @return boolean
         * @throws \Exception if $this->id is empty
         */

        public function fetch() {

            if (empty( $this->id )) {
                throw new Exception("Cannot populate Railpage\Jobs\Location - Location ID empty or not given");
            }

            $query = "SELECT jn_location_id, jn_location_name, jn_parent_id FROM jn_locations WHERE jn_location_id = ?";

            if ($row = $this->db->fetchRow($query, $this->id)) {
                $this->name = $row['jn_location_name'];
                $this->parent_id = $row['jn_parent_id'];
            }
        }

        /**
         * Validate this location
         *
         * @return boolean
         * @throws \Exception if $this->name is empty
         * @throws \Exception if $this->parent_id is invalid
         */

        public function validate() {

            if (empty( $this->name )) {
                throw new Exception("Cannot validate Railpage\Jobs\Location - Location name cannot be empty");
            }

            if ($this->parent_id !== 0 && empty( $this->parent_id )) {
                throw new Exception("Cannot vlidate Railpage\Jobs\Location - Location has no parent ID");
            }

            return true;
        }

        /**
         * Commit changes to this location
         *
         * @return boolean
         */

        public function commit() {

            $data = array(
                "jn_location_name" => $this->name,
                "jn_parent_id"     => $this->parent_id
            );

            if ($this->id) {
                // Update
                if ($this->db->update("jn_locations", $data, "jn_location_id = " . $this->id)) {
                    return true;
                }
            } else {
                // Insert
                if ($this->db->insert("jn_locations", $data)) {
                    $this->id = $this->db->lastInsertId();

                    return true;
                }
            }
        }
    }
    