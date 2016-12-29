<?php

namespace Optimizmeformagento\Passerelle\Helper;

/**
 * Class Optmeutils
 * @package Optimizmeformagento\Passerelle\Helper
 */
class Optmeutils extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_storeManager;
    protected $_wysiwygDirectory;
    protected $_directoryList;

    /**
     * Optmeutils constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwyg
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directory_list
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwyg,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list
    )
    {
        $this->_storeManager = $storeManager;
        $this->_wysiwygDirectory = $wysiwyg::IMAGE_DIRECTORY;
        $this->_directoryList = $directory_list;
    }


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

        $storeBaseUrl = $this->_storeManager->getStore()->getBaseUrl();
        if ( !stristr($urlFile, $storeBaseUrl) ){

            // different: copy to CMS
            $basenameFile = basename($urlFile);
            $folder = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA ) .'/'. $this->_wysiwygDirectory;

            if (file_exists($folder .'/'. $basenameFile)){
                return $folder .'/'. $basenameFile;
            }
            else{
                return false;
            }
        }
        else {
            // same: image already in prestashop
            return $urlFile;
        }
    }


    /**
     * Add media in library
     * @param $urlFile : URL where to download and copy file
     * @return false|string
     */
    public function addMediaInLibrary($urlFile){

        $urlMedia = '';
        $uploaddir = $this->_directoryList->getPath('media') .'/'. $this->_wysiwygDirectory;
        $urldir = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA ) .'/'. $this->_wysiwygDirectory;

        $nameFile = basename($urlFile);
        $uploadfile = $uploaddir .'/'. $nameFile;

        // write file in media
        try {
            $contents = file_get_contents($urlFile);
            $savefile = fopen($uploadfile, 'w');
            fwrite($savefile, $contents);
            fclose($savefile);
            $newUrl = $urldir .'/'. $nameFile;
            return $newUrl;
        }
        catch (Exception $e){
            return false;
        }
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
     * @param $field
     * @param $value
     * @param $objAction
     * @param int $isRequired
     * @return bool
     */
    public function saveProductField($idProduct, $field, $value, $objAction, $isRequired=0){

        if ( !is_numeric($idProduct)){
            // need more data
            $objAction->addMsgError('ID product missing');
        }
        elseif ( $isRequired == 1 && ($value == '' && $value !== 0)){
            // no empty
            $objAction->addMsgError('This field is required');
        }
        elseif (!isset($value)){
            // need more data
            $objAction->addMsgError('Function '. $field .' missing');
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
                // update if different
                $setter = 'set'. $field;
                $getter = 'get'. $field;

                $currentValue = $product->$getter();
                if ($currentValue != $value){
                    // new value => save
                    try {
                        $product->$setter($value);
                        $product->save();
                        return $product;
                    }
                    catch (Exception $e){
                        $objAction->addMsgError('Product not saved, '. $e->getMessage(), 1);
                    }
                }

            }
        }

        // error somewhere
        return false;
    }




    /**
     * @param $idStore
     * @return mixed
     */
    public function getStoreBaseUrl($idStore){
        return $this->_storeManager->getStore($idStore)->getBaseUrl();

    }

}
