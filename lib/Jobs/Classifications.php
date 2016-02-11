<?php

/**
 * List all job classifications
 *
 * @since   Version 3.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage\Jobs;

/**
 * Classifications
 */

class Classifications extends Jobs {

    /**
     * Get child classifications
     *
     * @return array
     *
     * @param int $parent_id
     */

    public function getChildClassifications($parent_id = 0) {

        $query = "SELECT jn_classification_id, jn_classification_name, ? AS jn_parent_id FROM jn_classifications WHERE jn_parent_id = ? ORDER BY jn_classification_name";

        return $this->db->fetchAll($query, array($parent_id, $parent_id));
    }
}
