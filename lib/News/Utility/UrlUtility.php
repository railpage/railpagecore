<?php
	/**
	 * Make URLs for a news article
	 * @since Version 3.10.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railapge\News\Utility;
	
	use Railpage\Url;
	use Railpage\Debug;
	use Railpage\AppCore;
	use Railpage\News\Article;
	use Railpage\News\NewsFactory;
	
	class UrlUtility {
		
		/**
		 * Create a URL object from a provided Article
		 * @since Version 3.10.0
		 * @param \Railpage\News\Article
		 * @return \Railpage\Url
		 */
		
		public static function CreateArticleUrl(Article $Article) {
			
			$Url = new Url($Article->makePermaLink($Article->slug));
			$Url->source = $Article->source; 
			$Url->reject = sprintf("/news/pending?task=reject&id=%d&queue=newqueue", $Article->id);
			$Url->edit = sprintf("/news?mode=article.edit&id=%d", $Article->id);
				
			/**
			 * Alter the URL
			 */
			
			if (empty($Article->getParagraphs()) && !empty($Article->source)) {
				$Url->url = $Article->source;
				$Url->canonical = $Article->source;
			}
			
			return $Url;
			
		}
		
	}