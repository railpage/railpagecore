<?php

/**
 * Image collection utility
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images\Utility;

use Railpage\Images\Images;
use Railpage\Images\Image;
use Railpage\Images\Collection;
use Railpage\Images\MapImage;
use Railpage\Locos\LocoClass;
use Railpage\Locos\Locomotive;
use Railpage\Locos\Liveries\Livery;
use Railpage\Debug;
use Railpage\Place;
use Railpage\PlaceUtility;
use Railpage\AppCore;
use Railpage\Users\User;
use Railpage\Users\Factory as UsersFactory;
use Exception;
use InvalidArgumentException;

class CollectionUtility {
    
    /**
     * Find all collections
     * @since Version 3.9.1
     * @return array
     * @param \Railpage\Users\User $userObject
     */
    
    public static function getCollections($userObject = null) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $query = "SELECT i.*, u.username FROM image_collection AS i LEFT JOIN nuke_users AS u ON i.user_id = u.user_id";
        $params = array(); 
        
        if ($userObject instanceof User) {
            $query .= " WHERE i.user_id = ?";
            $params[] = $userObject->id;
        }
        
        $query .= " ORDER BY i.modified DESC";
        $result = array(); 
        
        foreach ($Database->fetchAll($query, $params) as $row) {
            $result[] = (new Collection($row['id']))->getArray(); 
        }
        
        return $result;
        
    }
    
    /**
     * Find collections featuring this image
     * @since Version 3.9.1
     * @param \Railpage\Images\Image $imageObject
     * @return array
     */
    
    public static function getCollectionsFeaturingImage(Image $imageObject) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $query = "SELECT i.*, u.username, il.namespace 
            FROM image_collection AS i
            LEFT JOIN nuke_users AS u ON i.user_id = u.user_id 
            LEFT JOIN image_link AS il ON namespace_key = i.id
            WHERE namespace = ? 
                AND image_id = ?
                AND i.id > 1
            ORDER BY i.modified DESC";
        
        $params = [
            (new Collection)->namespace,
            $imageObject->id
        ];
        
        $result = $Database->fetchAll($query, $params); 
        
        foreach ($result as $key => $row) {
            $result[$key]['url'] = self::createUrl($row['slug']);
        }
        
        return $result;
        
    }
    
    /**
     * Make the URL for this collection
     * @since Version 3.9.1
     * @param string $slug
     * @return string
     */
    
    public static function createUrl($slug) {
        
        return sprintf("/photos/collection/%s", $slug); 
        
    }
    
}