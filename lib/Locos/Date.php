<?php

/**
 * Date / event object for a locomotive
 * @since   Version 3.8.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage\Locos;

use DateTime;
use Exception;
use Railpage\Url;
use Railpage\Debug;
use Railpage\Glossary\Glossary;
use Railpage\ContentUtility;

/**
 * Date class
 */
class Date extends Locos {

    /**
     * Date ID
     * @var int $id
     */

    public $id;

    /**
     * Date
     * @var \DateTime $Date
     */

    public $Date;

    /**
     * Optional end date for this event
     * @since Version 3.9.1
     * @var \DateTime $DateEnd
     */

    public $DateEnd;

    /**
     * Date type
     * @var string $action
     */

    public $action;

    /**
     * Date type id
     * @var int $action_id
     */

    public $action_id;

    /**
     * Descriptive text
     * @var string $text
     */

    public $text;

    /**
     * Rich descriptive text
     * @var string $rich_text
     */

    public $rich_text;

    /**
     * Metadata
     * @var array $meta
     */

    public $meta;

    /**
     * Memcached / Redis cache key
     * @since Version 3.10.0
     * @var string $mckey
     */

    public $mckey;

    /**
     * Locomotive object
     * @var Locomotive $Loco
     */

    public $Loco;

    /**
     * URL object
     * @since Version 3.10.
     * @var \Railpage\Url $url
     */

    public $url;

    /**
     * Locomotive ID
     * To be superseded by $this->Loco
     * @since Version 3.10.0
     * @var int $loco_id
     */

    public $loco_id;

    /**
     * Constructor
     *
     * @param int $id
     */

    public function __construct($id = null) {

        $timer = Debug::getTimer();

        parent::__construct();

        if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
            $this->id = $id;
            $this->populate();
        }

        Debug::logEvent(__METHOD__, $timer);

    }

    /**
     * Populate this object
     * @since Version 3.9.1
     * @return void
     */

    private function populate() {

        $this->mckey = sprintf("railpage.locos.date=%d", $this->id);
        $update = false;

        if (!$row = $this->Redis->fetch($this->mckey)) {

            $row = $this->db->fetchRow('SELECT d.*, dt.* FROM loco_unit_date AS d INNER JOIN loco_date_type AS dt ON d.loco_date_id = dt.loco_date_id WHERE d.date_id = ?', $this->id);

            $this->Redis->save($this->mckey, $row, strtotime("+1 year"));
        }

        if ($row === false) {
            return;
        }

        $this->text = $row['text'];
        $this->rich_text = $row['text'];
        $this->meta = json_decode($row['meta'], true);
        $this->action = $row['loco_date_text'];
        $this->action_id = $row['loco_date_id'];
        $this->loco_id = $row['loco_unit_id'];

        $this->loadLoco();

        if ($row['timestamp'] == "0000-00-00") {
            $this->Date = new DateTime();
            $this->Date->setTimestamp($row['date']);

            $update = true;
        } else {
            $this->Date = new DateTime($row['timestamp']);
        }

        if (isset( $row['date_end'] ) && !is_null($row['date_end'])) {
            $this->DateEnd = new DateTime($row['date_end']);
        }

        /**
         * Create the rich text entry
         */

        $this->createRichText();

        /**
         * Update this object if required
         */

        if ($update) {
            $this->commit();
        }
    }

    /**
     * Create the rich text entry
     * @since Version 3.9.1
     * @return void
     */

    private function createRichText() {

        if (!is_array($this->meta) || count($this->meta) === 0) {
            return;
        }

        foreach ($this->meta as $key => $data) {
            $this->rich_text .= "\n<strong>" . ucfirst($key) . ": </strong>";

            switch ($key) {

                case "livery" :
                    #$this->rich_text .= "[url=/flickr?tag=railpage:livery=" . $data['id'] . "]" . $data['name'] . "[/url]";
                    $this->rich_text .= "<a data-livery-id=\"" . $data['id'] . "\" data-livery-name=\"" . $data['name'] . "\" href='#' class='rp-modal-livery'>" . $data['name'] . "</a>";
                    break;

                case "owner" :
                    $Operator = new Operator($data['id']);
                    $this->rich_text .= "[url=" . $Operator->url_owner . "]" . $Operator->name . "[/url]";
                    break;

                case "operator" :
                    $Operator = new Operator($data['id']);
                    $this->rich_text .= "[url=" . $Operator->url_operator . "]" . $Operator->name . "[/url]";
                    break;

                case "position" :
                    if (!isset( $data['title'] ) || empty( $data['title'] )) {
                        $data['title'] = "Location";
                    }

                    $this->rich_text .= "<a data-lat=\"" . $data['lat'] . "\" data-lon=\"" . $data['lon'] . "\" data-zoom=\"" . $data['zoom'] . "\" data-title=\"" . $data['title'] . "\" data-toggle='modal' href='#' class='rp-modal-map'>Click to view</a>";
                    break;
            }
        }

        return;

    }


    /**
     * Validate changes to this object
     * @return boolean
     * @throws \Exception if $this->date is not an instance of \DateTime
     * @throws \Exception if $this->action_id is empty or not an integer
     * @throws \Exception if $this->Loco is not an instance of \Railpage\Locos\Locomotive
     */

    public function validate() {

        if (is_null($this->Loco) && filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->loadLoco();
        }

        if (!$this->Date instanceof DateTime) {
            throw new Exception("\$this->Date is not an instance of DateTime");
        }

        if (!filter_var($this->action_id)) {
            throw new Exception("\$this->action_id cannot be empty");
        }

        if (!$this->Loco instanceof Locomotive) {
            throw new Exception("\$this->Loco is not an instance of Railpage\Locos\Locomotive");
        }
        
        if (is_null($this->text)) {
            $this->text = "";
        }

        if (!empty( $this->meta )) {
            foreach ($this->meta as $k => $v) {
                $this->meta[$k] = $this->stripEmptyMeta($v);

                /*
                if (is_array($v)) {
                    foreach ($v as $l1k => $l1v) {
                        if (is_array($l1v)) {
                            foreach ($l1v as $l2k => $l2v) {
                                if (empty($this->meta[$k][$l2k])) {
                                    unset($this->meta[$k][$l2k]);
                                }
                            }
                        }

                        if (empty($this->meta[$k][$l1k])) {
                            unset($this->meta[$k][$l1k]);
                        }
                    }
                }
                */

                if (empty( $this->meta[$k] )) {
                    unset( $this->meta[$k] );
                }
            }
        }

        return true;
    }

    /**
     * Filter the meta data
     * @since Version 3.9.1
     *
     * @param array $array
     *
     * @return array
     */

    private function stripEmptyMeta($array) {

        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $array[$key] = $this->stripEmptyMeta($val);
            }

            if (empty( $array[$key] )) {
                unset( $array[$key] );
                continue;
            }
        }

        return $array;

    }

    /**
     * Commit changes to this locomotive
     * @since Version 3.9.1
     * @return \Railpage\Locos\Date
     */

    public function commit() {

        $this->validate();

        $data = array(
            "loco_unit_id" => $this->Loco->id,
            "loco_date_id" => $this->action_id,
            "date"         => $this->Date->getTimestamp(),
            "date_end"     => $this->DateEnd instanceof DateTime ? $this->DateEnd->format("Y-m-d") : NULL,
            "timestamp"    => $this->Date->format("Y-m-d"),
            "text"         => $this->text,
            "meta"         => json_encode($this->meta)
        );

        if (filter_var($this->id)) {

            $this->Redis->delete($this->mckey);

            $where = array(
                "date_id = ?" => $this->id
            );

            $this->db->update("loco_unit_date", $data, $where);
        } else {
            $this->db->insert("loco_unit_date", $data);
            $this->id = $this->db->lastInsertId();
        }

        if (isset( $this->Loco->meta['construction_cost'] )) {
            $this->Loco->meta['construction_cost_inflated'] = ContentUtility::convertCurrency($this->Loco->meta['construction_cost'], Utility\LocomotiveUtility::getConstructionDate($this->Loco));
            $this->Loco->commit();
        }

        return $this;
    }

    /**
     * Find the glossary entry for this date
     * @since Version 3.9.1
     * @return null|\Railpage\Glossary\Entry
     */

    public function getGlossary() {

        return (new Glossary)->lookupText($this->action);

    }

    /**
     * Load the locomotive associated with this date
     * @since Version 3.9.1
     * @return \Railpage\Locos\Date
     */

    public function loadLoco() {

        $this->Loco = Factory::CreateLocomotive($this->loco_id);

        $this->url = new Url($this->Loco->url);

        return $this;

    }

    /**
     * Get this as an array
     * @since Version 3.9.1
     * @return array
     */

    public function getArray() {

        if (!$this->Loco instanceof Locomotive) {
            $this->loadLoco();
        }

        $return = array(
            "id"       => $this->id,
            "loco"     => array(
                "id"     => $this->Loco->id,
                "number" => $this->Loco->number,
                "class"  => array(
                    "id"   => $this->Loco->Class->id,
                    "name" => $this->Loco->Class->name
                ),
            ),
            "date"     => $this->Date->format("Y-m-d"),
            "date_end" => $this->DateEnd instanceof DateTime ? $this->DateEnd->format("Y-m-d") : NULL,
            "text"     => $this->text,
            "meta"     => $this->meta
        );

        return $return;

    }
}
