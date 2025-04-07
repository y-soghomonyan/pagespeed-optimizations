<?php

namespace PSO\Controllers;

use PSO\Helpers\Helper;

class ImageLazyLoadController extends BaseController
{
    public function addActions(): void
    {
        add_filter('the_content', [$this, 'lazyLoadImages'], PHP_INT_MAX);
        add_filter('lazy_load_images', [$this, 'lazyLoadImages'], PHP_INT_MAX);
    }

    public function lazyLoadImages(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        if(!Helper::getSetting('lazy_load')) {
            return $content;
        }

        $placeholderImage = plugin_dir_url(PSO_PLUGIN_FILE) . 'assets/images/placeholder.avif';
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

        $images = $dom->getElementsByTagName('img');
        if ($images->length > 0) {
            $preloadImages = Helper::getImagesToPreload();
            foreach ($images as $image) {
                if (!$image->hasAttribute('class') || !str_contains($image->getAttribute('class'), 'lazy-img')) {
                    $originalSrc = $image->getAttribute('src');
                    if(in_array($originalSrc, $preloadImages) || str_contains($image->getAttribute('class'), 'logo')) {
                        $image->setAttribute('loading', 'eager');
                    } else {
                        $originalSrcset = $image->getAttribute('srcset');
                        $image->setAttribute('data-src', $originalSrc);
                        $image->setAttribute('data-srcset', $originalSrcset);
                        $image->setAttribute('src', $placeholderImage);
                        $image->removeAttribute('srcset');
                        $image->setAttribute('class', trim($image->getAttribute('class') . ' lazy-img'));
                    }
                }
            }
        }

        $iframes = $dom->getElementsByTagName('iframe');
        if ($iframes->length > 0) {
            foreach ($iframes as $iframe) {
                if (!$iframe->hasAttribute('class') || !str_contains($iframe->getAttribute('class'), 'lazy-img')) {
                    $originalSrc = $iframe->getAttribute('src');
                    $iframe->setAttribute('data-src', $originalSrc);
                    $iframe->setAttribute('src', $placeholderImage);
                    $iframe->setAttribute('class', trim($iframe->getAttribute('class') . ' lazy-img'));
                }
            }
        }

        // Process vc_parallax-inner divs using DOMXPath
        $xpath = new \DOMXPath($dom);
        $parallaxDivs = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vc_parallax-inner ")]');

        if ($parallaxDivs->length > 0) {
            foreach ($parallaxDivs as $div) {
                $style = $div->getAttribute('style');

                if (preg_match('/background-image: url\((.*?)\)/', $style, $matches)) {
                    $bgImageUrl = $matches[1];
                    $div->setAttribute('data-bg-image', $bgImageUrl);
                    $div->setAttribute('class', trim($div->getAttribute('class') . ' lazy-bg-set'));
                    $div->setAttribute('style', preg_replace('/background-image: url\((.*?)\);?/', '', $style));
                } elseif (preg_match('/background: url\((.*?)\)/', $style, $matches)) {
                    $bgImageUrl = $matches[1];
                    $div->setAttribute('data-bg-image', $bgImageUrl);
                    $div->setAttribute('class', trim($div->getAttribute('class') . ' lazy-bg-set'));
                    $div->setAttribute('style', preg_replace('/background: url\((.*?)\);?/', '', $style));
                }
            }
        }

        $content = $dom->saveHTML();
        return $content;
    }
}