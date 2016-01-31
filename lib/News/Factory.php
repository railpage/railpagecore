<?php
    /**
     * Factory code pattern - return an instance of blah from the registry, Redis, Memcached, etc...
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\News;
    
    use Railpage\Debug;
    use Railpage\AppCore;
    use Railpage\Url;
    use Railpage\Registry;
    use Exception;
    
    class Factory {
        
        /**
         * Return a news article
         * @since Version 3.9.1
         * @return \Railpage\News\Article
         * @param int|string $id
         */
        
        public static function CreateArticle($id) {
            
            $Redis = AppCore::getRedis();
            $Memcached = AppCore::getMemcached(); 
            $Registry = Registry::getInstance();  
            
            /**
             * Lookup article slug-to-ID first
             */
            
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                
                $mckey = sprintf("railpage:news.article_slug=%s", $id);
                
                if (!$article_id = $Memcached->fetch($mckey)) {
                    $Database = AppCore::getDatabase(); 
                    
                    $article_id = $Database->fetchOne("SELECT sid FROM nuke_stories WHERE slug = ?", $id); 
                    
                }
                
                if (!filter_var($article_id, FILTER_VALIDATE_INT)) {
                    throw new Exception("Could not find an article ID matching URL slug " . $id); 
                }
                
                $id = $article_id;
                
            }
            
            /**
             * We have an integer article ID, so go ahead and load it
             */
            
            $regkey = sprintf(Article::REGISTRY_KEY, $id);
            
            try {
                
                $Article = $Registry->get($regkey); 
                
            } catch (Exception $e) {
                
                if ($Article = $Redis->fetch($regkey)) {
                
                    $Article->Memcached = $Memcached; 
                    $Article->Redis     = $Redis;
                    
                    $Database = AppCore::getDatabase(); 
                    
                    $Article->setDatabaseConnection($Database)->setDatabaseReadOnlyConnection($Database);
                    
                } else {
                
                    $Article = new Article($id); 
                    $Redis->save($regkey, $Article);
                
                }
                
                $Registry->set($regkey, $Article); 
                
            }
            
            return $Article;
            
        }
        
    }
