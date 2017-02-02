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
    protected $_resourceConfig;
    protected $_scopeConfig;
    protected $_cacheTypeList;
    protected $_cacheFrontendPool;

    /**
     * Optmeutils constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwyg
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directory_list
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwyg,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool

    )
    {
        $this->_storeManager = $storeManager;
        $this->_wysiwygDirectory = $wysiwyg::IMAGE_DIRECTORY;
        $this->_directoryList = $directory_list;
        $this->_resourceConfig = $resourceConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
    }


    /**
     * Dump formatted content
     * @param $s
     */
    public function nice($s){
        echo '<pre>';print_r($s);echo'</pre>';
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

        $uploaddir = $this->_directoryList->getPath('media') .'/'. $this->_wysiwygDirectory;
        $urldir = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA ) .'/'. $this->_wysiwygDirectory;

        $nameFile = basename($urlFile);
        $uploadfile = $uploaddir .'/'. $nameFile;

        if (strstr($urlFile, 'passerelle.dev'))
            $urlFile = 'http://www.w3schools.com/css/img_fjords.jpg';       // TODO gérer en prod

        // write file in media
        try {
            copy($urlFile, $uploadfile);

            $newUrl = $urldir .'/'. $nameFile;
            return $newUrl;
        }
        catch (\Exception $e){
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
     * Get Dom from html
     *  and add a "<span>" tag in top
     * @param $doc
     * @param $tag
     * @param $content
     * @return \DOMNodeList
     */
    public function getNodesInDom($doc, $tag, $content){
        /* @var $doc \DOMDocument */
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
        /* @var $doc \DOMDocument */
        /* @var $racine \DOMNode */
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
     * @param $type
     * @param $value
     * @param $objAction
     * @param int $isRequired
     * @return bool|\Magento\Catalog\Model\Product
     */
    public function saveObjField($idProduct, $field, $type, $value, $objAction, $isRequired=0){
        /* @var $objAction Optmeactions */

        if ( !is_numeric($idProduct)){
            // need more data
            $objAction->addMsgError('ID element missing');
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
            // get product/category details
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->create('Magento\Catalog\Model\\'. $type)->load($idProduct);

            /* @var \Magento\Catalog\Model\Product $product */
            if ($product->getId() == ''){
                $objAction->addMsgError('Loading element failed', 1);
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

                        $objAction->returnAjax['message'] = 'Field saved';

                        return $product;
                    }
                    catch (\Exception $e){
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

    /**
     * @param int $length
     * @return string
     */
    public function generateKeyForJwt($length=64){
        $key = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
        return $key;
    }

    /**
     * @param $keyJWT
     */
    public function saveJwtKey($keyJWT){
        $this->_resourceConfig->saveConfig(
            'optimizme/jwt/key',
            $keyJWT,
            'default',
            0
        );

        // flush cache config to update key
        $this->cacheConfigClean();
    }

    /**
     *  Get saved JWT key
     */
    public function getJwtKey(){

        $key = $this->_scopeConfig->getValue('optimizme/jwt/key', 'default', 0);
        if (is_null($key))                  $key = '';
        return $key;
    }

    /**
     *  clean config cache
     */
    public function cacheConfigClean(){

        try{
            $types = array('config');
            foreach ($types as $type) {
                $this->_cacheTypeList->cleanType($type);
            }
            foreach ($this->_cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }
        }
        catch(\Exception $e){
            echo $msg = 'Error : '.$e->getMessage();
        }
    }
}
