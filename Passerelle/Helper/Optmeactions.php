<?php

namespace Optimizmeformagento\Passerelle\Helper;
use Braintree\Exception;

/**
 * Class Data
 * @package Optmizmeformagento\Passerelle\Helper
 */

class Optmeactions extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $returnResult;
    public $tabErrors;
    public $tabSuccess;
    public $returnAjax;

    protected $_productCollectionFactory;
    protected $_optmeutils;
    protected $_optmeredirections;
    protected $_urlRewriteFactory;
    protected $_productUrlPathGenerator;

    /**
     * Optmeactions constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator
     * @param Optmeutils $optMeUtils
     * @param Optmeredirections $optMeRedirections
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator,
        \Optimizmeformagento\Passerelle\Helper\Optmeutils $optMeUtils,
        \Optimizmeformagento\Passerelle\Helper\Optmeredirections $optMeRedirections
    )
    {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_urlRewriteFactory = $urlRewriteFactory;
        $this->_productUrlPathGenerator = $productUrlPathGenerator;
        $this->_optmeutils = $optMeUtils;
        $this->_optmeredirections = $optMeRedirections;

        // tab messages and returns
        $this->returnResult = array();
        $this->tabErrors = array();
        $this->tabSuccess = array();
        $this->returnAjax = array();
    }

    /**
     * Update product name
     * @param $idPost
     * @param $objData
     */
    public function updateTitle($idPost, $objData){
        $this->_optmeutils->saveProductField($idPost, 'setName', $objData->new_title, $this, 1);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateContent($idPost, $objData){

        if (!isset($objData->new_content))
        {
            // need more data
            array_push($this->tabErrors, 'Contenu non trouvé.');
            $this->addMsgError('Contenu non trouvé', 1);
        }
        else{

            // copy media files to Magento img
            $doc = new \DOMDocument;
            libxml_use_internal_errors(true);
            $doc->loadHTML('<span>'.$objData->new_content.'</span>');
            libxml_clear_errors();

            // get all images in post content
            $xp = new \DOMXPath($doc);

            // tags to parse and attributes to transform
            $tabParseScript = array(
                'img' => 'src',
                'a' => 'href',
                'video' => 'src',
                'source' => 'src'
            );

            foreach ($tabParseScript as $tag => $attr)
            {
                foreach ($xp->query('//'.$tag) as $node)
                {
                    // url media in easycontent
                    $urlFile = $node->getAttribute($attr);
                    if(!(strpos($urlFile, 'http') === 0)){
                        $urlFile = 'http://localhost'. $urlFile;        // TODO enlever localhost
                        echo "URL SPOTTED"; die;
                    }

                    // check if is media and already in media library
                    if ($this->_optmeutils->isFileMedia($urlFile)){
                        $urlMediaCMS = $this->_optmeutils->isMediaInLibrary($urlFile);
                        if (!$urlMediaCMS){
                            $resAddImage = $this->_optmeutils->addMediaInLibrary($urlFile);
                            if ( !$resAddImage ){
                                $this->addMsgError("Error copying img file", 1);
                            }
                            else {
                                $urlMediaCMS = $resAddImage;
                            }
                        }

                        // change HTML source
                        $node->setAttribute($attr, $urlMediaCMS);
                        $node->removeAttribute('data-mce-src');
                    }
                }
            }

            // span racine to enlever
            $newContent = $this->_optmeutils->getHtmlFromDom($doc);
            $newContent = $this->_optmeutils->cleanHtmlFromEasycontent($newContent);

            // save product content
            $this->_optmeutils->saveProductField($idPost, 'setDescription', $newContent, $this);

            if (count($this->tabErrors) == 0){
                $this->returnAjax['message'] = 'Contenu enregistré avec succès';
                $this->returnAjax['id_post'] = $idPost;
                $this->returnAjax['content'] = $newContent;
            }
        }
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateShortDescription($idPost, $objData){
        $this->_optmeutils->saveProductField($idPost, 'setShortDescription', $objData->new_short_description, $this);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateAttributesTag($idProduct, $objData, $tag){

        $boolModified = 0;
        if ( !is_numeric($idProduct)){
            // need more data
            $this->addMsgError('ID product not sent', 1);
        }
        if ($objData->url_reference == ''){
            // need more data
            $this->addMsgError('Aucun lien de référence trouvé, action annulée', 1);
        }
        else
        {
            // get product details
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($idProduct);

            if ($product->getId() != '')
            {
                // load nodes
                $doc = new \DOMDocument;
                $nodes = $this->_optmeutils->getNodesInDom($doc, $tag, $product->getDescription());
                if ($nodes->length > 0) {
                    foreach ($nodes as $node) {

                        if ($tag == 'img'){
                            if ($node->getAttribute('src') == $objData->url_reference) {
                                // image found in source: update (force utf8)
                                $boolModified = 1;

                                if ($objData->alt_image != '')      $node->setAttribute('alt', utf8_encode($objData->alt_image));
                                else                                $node->removeAttribute('alt');

                                if ($objData->title_image != '')    $node->setAttribute('title', utf8_encode($objData->title_image));
                                else                                $node->removeAttribute('title');
                            }
                        }
                        elseif ($tag == 'a'){
                            if ($node->getAttribute('href') == $objData->url_reference){
                                // href found in source: update (force utf8)
                                $boolModified = 1;

                                if ($objData->rel_lien != '')       $node->setAttribute('rel', utf8_encode($objData->rel_lien));
                                else                                $node->removeAttribute('rel');

                                if ($objData->target_lien != '')    $node->setAttribute('target', utf8_encode($objData->target_lien));
                                else                                $node->removeAttribute('target');
                            }
                        }
                    }
                }

                if ($boolModified == 1){
                    // action done: save new content
                    // span racine to enlever
                    $newContent = $this->_optmeutils->getHtmlFromDom($doc);

                    // update
                    $this->_optmeutils->saveProductField($idProduct, 'setDescription', $newContent, $this);

                }
                else {
                    // nothing done
                    $this->addMsgError('Nothing done.');
                }
            }
        }
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateMetaDescription($idPost, $objData){
        $this->_optmeutils->saveProductField($idPost, 'setMetaDescription', $objData->meta_description, $this);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateMetaTitle($idPost, $objData){
        $this->_optmeutils->saveProductField($idPost, 'setMetaTitle', $objData->meta_title, $this);
    }




    /**
     * @param $idPost
     * @param $objData
     */
    public function updateCanonicalUrl($idPost, $objData){
        // TODO magento
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateMetaRobots($idPost, $objData){
        // TODO magento
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updatePostStatus($idPost, $objData){
        if ( !isset($objData->is_publish) )         $objData->is_publish = 0;
        $this->_optmeutils->saveProductField($idPost, 'setStatus', $objData->is_publish, $this, 1);
    }



    /**
     * Change permalink of a post
     * and add a redirection
     * @param $idPost
     * @param $objData
     */
    public function updateSlug($idPost, $objData){

        // load product init (for after)
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $productInit = $objectManager->create('Magento\Catalog\Model\Product')->load($idPost);

        // save new url key
        $productUpdated = $this->_optmeutils->saveProductField($idPost, 'setUrlKey', $objData->new_slug, $this, 1);

        if ( $productUpdated->getId() && $productUpdated->getId() != ''){

            // save url key ok : change url
            $this->returnAjax['url'] = $productUpdated->getUrlModel()->getUrl($productUpdated);
            $this->returnAjax['message'] = 'URL changed';
            $this->returnAjax['new_slug'] = $productUpdated->getUrlKey();

            // get redirects (from >> to)
            $redirectFrom = $this->_productUrlPathGenerator->getUrlPathWithSuffix($productInit, $productInit->getStoreId());
            $redirectTo = $this->_productUrlPathGenerator->getUrlPathWithSuffix($productUpdated, $productUpdated->getStoreId());

            // add custom url rewrite
            $this->_optmeredirections->addRedirection($productUpdated->getId(), $redirectFrom, $redirectTo, $productUpdated->getStoreId());


        }
    }


    /**
     * Return content from a post
     * @param $idPost
     * @param $objData
     */
    public function loadPostContent($idPost){

        // get product details
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($idPost);

        if ($product->getId() != ''){

            // check si le contenu est bien compris dans une balise "row" pour qu'il soit bien inclus dans l'éditeur
            if (trim($product->getDescription()) != ''){
                if (!stristr($product->getDescription(), '<div class="row')){
                    $product->setDescription('<div class="row ui-droppable"><div class="col-md-12 col-sm-12 col-xs-12 column"><div class="ge-content ge-content-type-tinymce" data-ge-content-type="tinymce">'. $product->getDescription() .'</div></div></div>');
                }
            }

            // load and return product data
            $this->returnAjax['title'] = $product->getName();
            $this->returnAjax['short_description'] = $product->getShortDescription();
            $this->returnAjax['content'] = $product->getDescription();
            $this->returnAjax['slug'] = $product->getUrlKey();
            $this->returnAjax['url'] = $product->getUrlModel()->getUrl($product);
            $this->returnAjax['publish'] = $product->getStatus();
            $this->returnAjax['meta_description'] = $product->getMetaDescription();
            $this->returnAjax['meta_title'] = $product->getMetaTitle();
            $this->returnAjax['url_canonical'] = 'todo';                                // TODO gestion url canonique
            $this->returnAjax['noindex'] = 'todo';                                      // TODO gestion noindex
            $this->returnAjax['nofollow'] = 'todo';                                     // TODO gestion nofollow
            $this->returnAjax['blog_public'] = 1;
        }
    }


    /**
     * Load posts/pages
     */
    public function loadPostsPages($objData){

        $tabResults = array();
        $productsReturn = array();

        // récupération de la liste des produits
        $collection = $this->_productCollectionFactory->create();
        $collection->setPageSize(10);    // TODO enlever la limite
        $products = $collection->getData();

        if (count($products)>0){
            foreach ($products as $productBoucle){

                // get product details
                $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productBoucle['entity_id']);

                if ($product->getName() != ''){
                    if ($product->getStatus() == 1)         $status = 'En ligne';
                    else                                    $status = 'Hors ligne';
                    $prodReturn = array(
                        'ID' => $product->getId(),
                        'post_title' => $product->getName(),
                        'post_status' => $status
                    );
                    array_push($productsReturn, $prodReturn);
                }
            }
        }

        $tabResults['posts'] = $productsReturn;
        $this->returnAjax['arborescence'] = $tabResults;
    }


    /**
     * Load false content
     */
    public function loadLoremIpsum(){
        $nbParagraphes = rand(2,4);
        $content = file_get_contents('http://loripsum.net/api/'.$nbParagraphes.'/short/decorate/');
        $this->returnAjax['content'] = $content;
    }

    /**
     * load list of custom redirections
     */
    public function loadRedirections(){

        $tabResults = array();
        $magRedirections = $this->_optmeredirections->getAllRedirections();

        if (is_array($magRedirections) && count($magRedirections)>0){

            foreach ($magRedirections as $redirection){

                // get store base url for this url rewrite (depending from store id)
                $storeBaseUrl = $this->_optmeutils->getStoreBaseUrl($redirection['store_id']);

                array_push($tabResults, array(
                    'id' => $redirection['url_rewrite_id'],
                    'request_path' => $storeBaseUrl. $redirection['request_path'],
                    'target_path' => $storeBaseUrl. $redirection['target_path']
                ));
            }
        }

        $this->returnAjax['redirections'] = $tabResults;
    }


    /**
     * @param $objData
     */
    public function deleteRedirection($objData){
        if (!isset($objData->id_redirection) || $objData->id_redirection == ''){
            // need more data
            array_push($this->tabErrors, __('Redirection non trouvée', 'optimizme'));
        }
        else {
            $this->_optmeredirections->deleteRedirection($objData->id_redirection);
        }
    }


    /**
     * Check if has error or not
     * @return bool
     */
    public function hasErrors(){
        if (is_array($this->tabErrors) && count($this->tabErrors)>0){
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * @param $msg
     * @param string $typeResult : success, info, warning, danger
     */
    public function setMsgReturn($msg, $typeResult='success'){
        $this->returnResult['result'] = $typeResult;
        $this->returnResult['message'] = $msg;

        // return results
        header("Access-Control-Allow-Origin: *");
        echo json_encode($this->returnResult);
    }

    /**
     * @param $msg
     * @param string $typeResult : success, info, warning, danger
     */
    public function setDataReturn($tabData, $typeResult='success'){
        $this->returnResult['result'] = $typeResult;

        if (is_array($tabData) && count($tabData)>0){
            foreach ($tabData as $key => $value){
                $this->returnResult[$key] = $value;
            }
        }

        // return results
        header("Access-Control-Allow-Origin: *");
        echo json_encode($this->returnResult);
    }



    /**
     * Création d'un post
     * @param $objData
     */
    public function createPost($objData){
        // TODO magento
    }


    /**
     * @param $msg
     * @param int $trace
     */
    public function addMsgError($msg, $trace=0){
        if ($trace == 1)        $logTrace = __CLASS__ . ', ' . debug_backtrace()[1]['function'] . ': ';
        else                    $logTrace = '';
        array_push($this->tabErrors, $logTrace. $msg);
    }

}
