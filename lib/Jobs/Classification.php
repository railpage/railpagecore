<?php

/**
 * Job classification object
 *
 * @since   Version 3.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage\Jobs;

use Exception;
use DateTime;

/**
 * Job classification class
 *
 * @since   Version 3.7
 * @version 3.8.7
 */
class Classification extends Jobs {

    /**
     * Classification ID
     *
     * @var int $id
     */

    public $id;

    /**
     * Classification name
     *
     * @var string $name
     */

    public $name;

    /**
     * Classification parent ID
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
            if (!$this->id = Utility\SlugUtility::getID("classification", $id)) {
                $this->name = $id;
                $this->parent_id = 0;
                $this->commit();
            }
        }
    }

    /**
     * Fetch the classification object
     *
     * @return void
     * @throws \Exception if $this->id is not a valid integer
     */

    public function fetch() {

        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot populate Railpage\Jobs\Classification - Classification ID empty or not given");
        }

        $query = "SELECT jn_classification_id, jn_classification_name, jn_parent_id FROM jn_classifications WHERE jn_classification_id = ?";

        if ($row = $this->db->fetchRow($query, $this->id)) {
            $this->name = $row['jn_classification_name'];
            $this->parent_id = $row['jn_parent_id'];
        }
    }

    /**
     * Validate this classification
     *
     * @return boolean
     * @throws \Exception if $this->name is empty
     * @throws \Exception if $this->parent_id is empty
     */

    public function validate() {

        if (empty( $this->name )) {
            throw new Exception("Cannot validate Railpage\Jobs\Classification - Classification name cannot be empty");
        }

        if ($this->parent_id !== 0 && empty( $this->parent_id )) {
            throw new Exception("Cannot validate Railpage\Jobs\Classification - Classification has no parent ID");
        }

        return true;
    }

    /**
     * Commit changes to this classification
     *
     * @return $this
     */

    public function commit() {

        $this->validate();

        $data = array(
            "jn_classification_name" => $this->name,
            "jn_parent_id"           => $this->parent_id
        );

        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = array(
                "jn_classification_id = ?" => $this->id
            );

            // Update
            $this->db->update("jn_classifications", $data, $where);
        } else {
            // Insert
            $this->db->insert("jn_classifications", $data);
            $this->id = $this->db->lastInsertId();
        }

        return $this;
    }
}
