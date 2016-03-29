<?php

/**
 * BBCode image filter for Decoda
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Formatting\BbcodeEtc\Filters; 

use Decoda\Filter\ImageFilter as DecodaImageFilter; 
use Decoda\Decoda; 

class ImageFilter extends DecodaImageFilter {
    
    /**
     * Supported tags.
     *
     * @type array
     */
    protected $_tags = array(
        'img' => array(
            'htmlTag' => 'img',
            'displayType' => Decoda::TYPE_INLINE,
            'allowedTypes' => Decoda::TYPE_NONE,
            'contentPattern' => DecodaImageFilter::IMAGE_PATTERN,
            'autoClose' => true,
            'attributes' => array(
                'default' => DecodaImageFilter::WIDTH_HEIGHT,
                'width' => DecodaImageFilter::DIMENSION,
                'height' => DecodaImageFilter::DIMENSION,
                'alt' => DecodaImageFilter::WILDCARD
            ),
            'htmlAttributes' => [
                'class' => "content-image"
            ],
        ),
        'image' => array(
            'aliasFor' => 'img'
        )
    );
    
    /**
     * Use the content as the image source.
     *
     * @param array $tag
     * @param string $content
     * @return string
     */
    public function parse(array $tag, $content) {
        
        // If more than 1 http:// is found in the string, possible XSS attack
        if ((mb_substr_count($content, 'http://') + mb_substr_count($content, 'https://')) > 1) {
            return null;
        }
        
        $tag['attributes']['src'] = $content;
        
        if (!empty($tag['attributes']['default'])) {
            list($width, $height) = explode('x', $tag['attributes']['default']);
            
            $tag['attributes']['width'] = $width;
            $tag['attributes']['height'] = $height;
        }
        
        if (empty($tag['attributes']['alt'])) {
            $tag['attributes']['alt'] = '';
        }
        
        return parent::parse($tag, $content);
    }
    
}