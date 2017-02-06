<?php
namespace Optimizmeformagento\Mazen\Helper;

/**
 * Class OptimizmeMazenActions
 * @package Optimizmeformagento\Mazen\Helper
 */
class OptimizmeMazenActions extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $returnResult;
    public $tabErrors;
    public $tabSuccess;
    public $returnAjax;

    protected $productCollectionFactory;
    protected $categoryCollectionFactory;
    protected $urlRewriteFactory;
    protected $productUrlPathGenerator;
    protected $categoryUrlPathGenerator;
    protected $user;
    protected $optimizmeMazenUtils;
    protected $optimizmeMazenRedirections;


    /**
     * OptimizmeMazenActions constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator
     * @param OptimizmeMazenUtils $optimizmeMazenUtils
     * @param OptimizmeMazenRedirections $optimizmeMazenRedirections
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\User\Model\User $user,
        \Optimizmeformagento\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils,
        \Optimizmeformagento\Mazen\Helper\OptimizmeMazenRedirections $optimizmeMazenRedirections
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->user = $user;
        $this->optimizmeMazenUtils = $optimizmeMazenUtils;
        $this->optimizmeMazenRedirections = $optimizmeMazenRedirections;

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
        $this->optimizmeMazenUtils->saveObjField($idPost, 'Name', 'Product', $objData->new_title, $this, 1);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateContent($idPost, $objData){
        /* @var $node \DOMElement */

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
                    // url media in MAZEN
                    $urlFile = $node->getAttribute($attr);

                    // check if is media and already in media library
                    if ($this->optimizmeMazenUtils->isFileMedia($urlFile)){
                        $urlMediaCMS = $this->optimizmeMazenUtils->isMediaInLibrary($urlFile);
                        if (!$urlMediaCMS){
                            $resAddImage = $this->optimizmeMazenUtils->addMediaInLibrary($urlFile);
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
            $newContent = $this->optimizmeMazenUtils->getHtmlFromDom($doc);
            $newContent = $this->optimizmeMazenUtils->cleanHtmlFromMazen($newContent);

            // save product content
            $this->optimizmeMazenUtils->saveObjField($idPost, 'Description', 'Product', $newContent, $this);

            if (count($this->tabErrors) == 0){
                $this->returnAjax['message'] = 'Content saved successfully!';
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
        $this->optimizmeMazenUtils->saveObjField($idPost, 'ShortDescription', 'Product', $objData->new_short_description, $this);
    }

    /**
     * @param $idProduct
     * @param $objData
     * @param $tag
     */
    public function updateAttributesTag($idProduct, $objData, $tag){
        /* @var $product \Magento\Catalog\Model\Product */
        /* @var $node \DOMElement */

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
                $nodes = $this->optimizmeMazenUtils->getNodesInDom($doc, $tag, $product->getDescription());
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
                    $newContent = $this->optimizmeMazenUtils->getHtmlFromDom($doc);

                    // update
                    $this->optimizmeMazenUtils->saveObjField($idProduct, 'Description', 'Product', $newContent, $this);

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
        $this->optimizmeMazenUtils->saveObjField($idPost, 'MetaDescription', 'Product', $objData->meta_description, $this);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateMetaTitle($idPost, $objData){
        $this->optimizmeMazenUtils->saveObjField($idPost, 'MetaTitle', 'Product', $objData->meta_title, $this);
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
        if ( !isset($objData->is_publish) || $objData->is_publish == 0 )        $objData->is_publish = 0;
        else                                                                    $objData->is_publish = 1;

        $this->optimizmeMazenUtils->saveObjField($idPost, 'Status', 'Product', $objData->is_publish, $this, 1);
    }

    /**
     * Change permalink of a post
     * and add a redirection
     * @param $idPost
     * @param $objData
     */
    public function updateSlug($idPost, $objData){
        /* @var $productInit \Magento\Catalog\Model\Product */
        /* @var $productExpected \Magento\Catalog\Model\Product */

        if ( !is_numeric($idPost)){
            // need more data
            $this->addMsgError('ID product missing');
        }
        elseif ( $objData->new_slug == '' ){
            // no empty
            $this->addMsgError('This field is required');
        }
        else {
            // load product init (for after)
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $productInit = $objectManager->create('Magento\Catalog\Model\Product')->load($idPost);
            $redirectFrom = $this->productUrlPathGenerator->getUrlPathWithSuffix($productInit, $productInit->getStoreId());

            // if custom url exists: remove
            $productExpected = $productInit;
            $productExpected->setUrlKey($objData->new_slug);
            $redirectCheck = $this->productUrlPathGenerator->getUrlPathWithSuffix($productExpected, $productExpected->getStoreId());
            $this->optimizmeMazenRedirections->deleteRedirectionByRequestPath($redirectCheck);

            // save new url key
            $productUpdated = $this->optimizmeMazenUtils->saveObjField($idPost, 'UrlKey', 'Product', $objData->new_slug, $this, 1);

            if (!$productUpdated){
                // no update
            }
            else {
                if ( $productUpdated->getId() && $productUpdated->getId() != ''){

                    // save url key ok : change url
                    $this->returnAjax['url'] = $productUpdated->getUrlModel()->getUrl($productUpdated);
                    $this->returnAjax['message'] = 'URL changed';
                    $this->returnAjax['new_slug'] = $productUpdated->getUrlKey();

                    // get redirects (from >> to)
                    $redirectTo = $this->productUrlPathGenerator->getUrlPathWithSuffix($productUpdated, $productUpdated->getStoreId());

                    // add custom url rewrite
                    $this->optimizmeMazenRedirections->addRedirection($productUpdated->getId(), $redirectFrom, $redirectTo, $productUpdated->getStoreId());

                }
            }
        }
    }

    /**
     * Change reference for a product
     * @param $idPost
     * @param $objData
     */
    public function setReference($idPost, $objData){
        $this->optimizmeMazenUtils->saveObjField($idPost, 'Sku', 'Product', $objData->new_reference, $this, 1);
    }

    /**
     * Return content from a post
     * @param $idPost
     * @param $objData
     */
    public function loadPostContent($idPost){

        /* @var $product \Magento\Catalog\Model\Product */
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
            $this->returnAjax['reference'] = $product->getSku();
            $this->returnAjax['short_description'] = $product->getShortDescription();
            $this->returnAjax['content'] = $product->getDescription();
            $this->returnAjax['slug'] = $product->getUrlKey();
            $this->returnAjax['url'] = $product->getUrlModel()->getUrl($product);
            $this->returnAjax['publish'] = $product->getStatus();
            $this->returnAjax['meta_description'] = $product->getMetaDescription();
            $this->returnAjax['meta_title'] = $product->getMetaTitle();
            $this->returnAjax['url_canonical'] = '';                                    // TODO gestion url canonique
            $this->returnAjax['noindex'] = '';                                          // TODO gestion noindex
            $this->returnAjax['nofollow'] = '';                                         // TODO gestion nofollow
            $this->returnAjax['blog_public'] = 1;
        }
    }

    /**
     * Load posts/pages
     */
    public function loadPostsPages(){
        /* @var $product \Magento\Catalog\Model\Product */

        $tabResults = array();
        $productsReturn = array();

        // récupération de la liste des produits
        $collection = $this->productCollectionFactory->create();
        $collection->setPageSize(10);    // TODO remove limit
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


    ////////////////////////////////////////////////
    //              CATEGORIES
    ////////////////////////////////////////////////

    /**
     * Load categories list
     */
    public function loadCategories(){

        /* @var $category \Magento\Catalog\Model\Category */
        $tabResults = array();

        // don't get root category
        $categories = $this->categoryCollectionFactory->create()->getData();

        if (count($categories)>0) {
            foreach ($categories as $categoryLoop) {

                // get category details
                $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                $category = $objectManager->create('Magento\Catalog\Model\Category')->load($categoryLoop['entity_id']);

                $categoryInfos = array(
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                    'slug' => $category->getUrlKey()
                );

                array_push($tabResults, $categoryInfos);
            }
        }

        $this->returnAjax['categories'] = $tabResults;
    }

    /**
     * @param $elementId
     */
    public function loadCategoryContent($elementId){
        /* @var $category \Magento\Catalog\Model\Category */
        $tabCategory = array();

        // get category details
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $category = $objectManager->create('Magento\Catalog\Model\Category')->load($elementId);

        if ($category->getId() && $category->getId() != ''){
            $tabCategory['id'] = $category->getId();
            $tabCategory['name'] = $category->getName();
            $tabCategory['slug'] = $category->getUrlKey();
            $tabCategory['url'] = $category->getUrl();
            $tabCategory['description'] = $category->getDescription();
        }

        $this->returnAjax['message'] = 'Category loaded';
        $this->returnAjax['category'] = $tabCategory;
    }

    /**
     * @param $idCategory
     * @param $objData
     */
    public function setCategoryName($idCategory, $objData){
        $this->optimizmeMazenUtils->saveObjField($idCategory, 'Name', 'Category', $objData->new_name, $this);
    }

    /**
     * @param $idCategory
     * @param $objData
     */
    public function setCategoryDescription($idCategory, $objData){
        $this->optimizmeMazenUtils->saveObjField($idCategory, 'Description', 'Category', $objData->description, $this);
    }

    /**
     * Change permalink of a post
     * @param $idCategory
     * @param $objData
     */
    public function updateCategorySlug($idCategory, $objData){
        /* @var $categoryInit \Magento\Catalog\Model\Category */

        if ( !is_numeric($idCategory)){
            // need more data
            $this->addMsgError('ID category missing');
        }
        elseif ( $objData->new_slug == '' ){
            // no empty
            $this->addMsgError('This field is required');
        }
        else {
            // load category init (for after)
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $categoryInit = $objectManager->create('Magento\Catalog\Model\Category')->load($idCategory);
            $redirectFrom = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($categoryInit, $categoryInit->getStoreId() );

            // if custom url exists: remove
            $categoryExpected = $categoryInit;
            $categoryExpected->setUrlKey($objData->new_slug);
            $redirectCheck = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($categoryExpected, $categoryExpected->getStoreId());
            $this->optimizmeMazenRedirections->deleteRedirectionByRequestPath($redirectCheck);

            // save new url key
            $categoryUpdated = $this->optimizmeMazenUtils->saveObjField($idCategory, 'UrlKey', 'Category', $objData->new_slug, $this, 1);

            if (!$categoryUpdated){
                // no update
            }
            else {
                if ( $categoryUpdated->getId() && $categoryUpdated->getId() != ''){

                    // save url key ok : change url
                    $this->returnAjax['url'] = $categoryUpdated->getUrl();
                    $this->returnAjax['message'] = 'URL changed';
                    $this->returnAjax['new_slug'] = $categoryUpdated->getUrlKey();

                    // get redirects (from >> to)
                    $redirectTo = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($categoryUpdated, $categoryUpdated->getStoreId());

                    // add custom url rewrite
                    $this->optimizmeMazenRedirections->addRedirection($categoryUpdated->getId(), $redirectFrom, $redirectTo, $categoryUpdated->getStoreId());

                }
            }
        }
    }


    ////////////////////////////////////////////////
    //              REDIRECTION
    ////////////////////////////////////////////////

    /**
     * load list of custom redirections
     */
    public function loadRedirections(){

        $tabResults = array();
        $magRedirections = $this->optimizmeMazenRedirections->getAllRedirections();

        if (is_array($magRedirections) && count($magRedirections)>0){

            foreach ($magRedirections as $redirection){

                // get store base url for this url rewrite (depending from store id)
                $storeBaseUrl = $this->optimizmeMazenUtils->getStoreBaseUrl($redirection['store_id']);

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
            $this->optimizmeMazenRedirections->deleteRedirection($objData->id_redirection);
        }
    }


    ////////////////////////////////////////////////
    //              SITE
    ////////////////////////////////////////////////

    /**
     * Get secret key for JSON Web Signature
     */
    public function registerCMS($objData){

        if ($this->user->authenticate($objData->login, $objData->password)){
            // auth ok! we can generate token
            $keyJWT = $this->optimizmeMazenUtils->generateKeyForJwt();
            $this->optimizmeMazenUtils->saveJwtKey($keyJWT);


            // all is ok
            $this->returnAjax['message'] = 'JSON Token generated in Magento.';
            $this->returnAjax['jws_token'] = $keyJWT;
            $this->returnAjax['cms'] = 'magento';
            $this->returnAjax['site_domain'] = $objData->url_cible;
            $this->returnAjax['jwt_disable'] = 1;

        }
        else {
            // error
            array_push($this->tabErrors, 'Signon error. CMS not registered.');
        }
    }


    ////////////////////////////////////////////////
    //              UTILS
    ////////////////////////////////////////////////

    /**
     * Load false content
     */
    public function loadLoremIpsum(){
        $nbParagraphes = rand(2,4);
        $content = file_get_contents('http://loripsum.net/api/'.$nbParagraphes.'/short/decorate/');
        $this->returnAjax['content'] = $content;
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

