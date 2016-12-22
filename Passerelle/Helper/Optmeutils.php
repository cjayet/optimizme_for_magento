<?php

namespace Optimizmeformagento\Passerelle\Helper;

/**
 * Class Data
 * @package Optmizmeformagento\Passerelle\Helper
 */

class Optmeutils extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Dump formatted content
     * @param $s
     */
    public function nice($s){
        echo '<pre>';print_r($s);echo'</pre>';
    }

    /**
     * Affichage des derniers articles du blog
     * @param $feed
     * @param $nbElements
     */
    public function showNewsRss($feed, $nbElements){
        // TODO
    }

    /**
     * Return list of messages
     * @param $tabMessages
     * @return string
     */
    public function getListMessages($tabMessages, $list=0){

        $msg = '';
        if (is_array($tabMessages) && count($tabMessages)>0)
        {
            if ($list == 1){
                $msg .= '<ul>';
                foreach ($tabMessages as $message)
                    $msg .= '<li>'. $message .'</li>';
                $msg .= '</ul>';
            }
            else {
                foreach ($tabMessages as $message)
                    $msg .= $message;
            }

        }
        return $msg;
    }

    /**
     * @param $message
     * @param string $statut : updated / error
     */
    public function showMessageBackoffice($message, $statut='updated'){
        ?>
        <div class="<?php echo $statut ?> notice">
            <p><?php echo $message ?></p>
        </div>
        <?php
    }


    /**
     * Check if media exists in media library (search by title)
     * @param $urlFile
     * @return bool
     */
    public function isMediaInLibrary($urlFile){
        // TODO
    }


    /**
     * Add media in library
     * @param $urlFile : URL where to download and copy file
     * @return false|string
     */
    public function addMediaInLibrary($urlFile){

        // TODO
    }

    /**
     *
     * @param $url
     * @return bool
     */
    public function isFileMedia($url){

        $infos = pathinfo($url);
        $extensionMediaAutorized = $this->getAuthorizedMediaExtension();
        if (is_array($infos) && isset($infos['extension']) && $infos['extension'] != ''){
            // extension found: is it authorized?
            if (in_array($infos['extension'], $extensionMediaAutorized)){
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getAuthorizedMediaExtension(){
        $tabExtensions = array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg', //Images
            'doc', 'docx', 'rtf', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ots', 'ott', 'odb', 'odg', 'otp', 'otg', 'odf', 'ods', 'odp' // files
        );
        return $tabExtensions;
    }

    /**
     * Get meta description
     * @param $post
     * @return mixed
     */
    public function getMetaDescription($post){

        // TODO
    }


    /**
     * @param $type
     * @return string
     */
    public function getPostMetaKeyFromType($type)
    {
        // TODO
    }

    /**
     * @param $newMetaValue
     * @param $idPost
     * @param $metaKey
     * @return bool
     */
    public function doUpdatePostMeta($newMetaValue, $idPost, $metaKey){
        // TODO
    }



    /**
     * Get canonical url
     * @param string $post
     * @return string
     */
    public function getCanonicalUrl($post=''){
       // TODO
    }

    /**
     * @param $post
     * @return mixed
     */
    public function getMetaNoIndex($post){
        // TODO
    }

    /**
     * @param $post
     * @return mixed
     */
    public function getMetaNoFollow($post){
        // TODO
    }


    /**
     * Get Dom from html
     *  and add a "<span>" tag in top
     * @param $doc
     * @param $tag
     * @param $content
     * @return DOMNodeList
     */
    public function getNodesInDom($doc, $tag, $content){

        // load post content in DOM
        libxml_use_internal_errors(true);
        $doc->loadHTML('<span>'.$content.'</span>');
        libxml_clear_errors();

        // get all images in post content
        $xp = new \DOMXPath($doc);
        $nodes = $xp->query('//'.$tag);
        return $nodes;
    }

    /**
     * Get HTML from dom document
     *  and remove "<span>" tag in top
     * @param $doc
     * @return string
     */
    public function getHtmlFromDom($doc){
        $racine = $doc->getElementsByTagName('span')->item(0);
        $newContent = '';
        if ($racine->hasChildNodes()){
            foreach ($racine->childNodes as $node){
                $newContent .= utf8_decode($doc->saveHTML($node));
            }
        }
        return $newContent;
    }


    /**
     * Clean content before saving
     * @param $content
     * @return mixed
     */
    public function cleanHtmlFromEasycontent($content){
        $content = str_replace(' easyContentAddRow', '', $content);
        $content = str_replace(' ui-droppable', '', $content);
        $content = str_replace('style=""', '', $content);
        $content = str_replace('class=""', '', $content);

        return trim($content);
    }


    /**
     * @param $oldUrl
     * @param $newUrl
     */
    public function changeAllLinksInPostContent($oldUrl, $newUrl){
        // TODO
    }


    /**
     * @param $idProduct
     * @param $function
     * @param $value
     * @param $objAction
     * @param int $isRequired
     * @return bool
     */
    public function saveProductField($idProduct, $function, $value, $objAction, $isRequired=0){

        if ( !is_numeric($idProduct)){
            // need more data
            $objAction->addMsgError('ID product missing');
        }
        elseif ( $isRequired == 1 && $value == ''){
            // no empty
            $objAction->addMsgError('This field is required');
        }
        elseif (!isset($value)){
            // need more data
            $objAction->addMsgError('Function '. $function .' missing');
        }
        else{
            // all is ok: try to save
            // get product details
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($idProduct);

            if ($product->getId() == ''){
                $objAction->addMsgError('Loading product failed', 1);
            }
            else {
                // update
                try {
                    $product->$function($value);
                    $product->save();
                    return $product;
                }
                catch (Exception $e){
                    $objAction->addMsgError('Product not saved, '. $e->getMessage(), 1);
                }
            }
        }

        // error somewhere
        return false;
    }


}
