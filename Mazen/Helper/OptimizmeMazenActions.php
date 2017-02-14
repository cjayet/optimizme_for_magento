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
    protected $pageCollectionFactory;
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
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\User\Model\User $user,
        \Optimizmeformagento\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils,
        \Optimizmeformagento\Mazen\Helper\OptimizmeMazenRedirections $optimizmeMazenRedirections
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
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


    ////////////////////////////////////////////////
    //              PRODUCTS
    ////////////////////////////////////////////////

    /**
     * Load products list
     */
    public function getProducts()
    {
        /* @var $product \Magento\Catalog\Model\Product */

        $tabResults = array();
        $productsReturn = array();

        // récupération de la liste des produits
        $collection = $this->productCollectionFactory->create();
        $collection->setPageSize(10);    // TODO remove product limit
        $products = $collection->getData();

        if (count($products)>0) {
            foreach ($products as $productBoucle) {

                // get product details
                $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productBoucle['entity_id']);
                $productUrl = $product->getUrlModel()->getUrl($product);

                if ($product->getName() != '') {
                    if ($product->getStatus() == 1) {
                        $status = 'Publish';
                    } else {
                        $status = 'Not publish';
                    }
                    $prodReturn = array(
                        'ID' => $product->getId(),
                        'post_title' => $product->getName(),
                        'post_status' => $status,
                        'url' => $productUrl
                    );
                    array_push($productsReturn, $prodReturn);
                }
            }
        }

        $tabResults['products'] = $productsReturn;
        $this->returnAjax['arborescence'] = $tabResults;
    }

    /**
     * Get product detail
     * @param $idPost
     */
    public function getProduct($idPost)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        // get product details
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($idPost);

        if ($product->getId() != '') {

            // check si le contenu est bien compris dans une balise "row" pour qu'il soit bien inclus dans l'éditeur
            if (trim($product->getDescription()) != '') {
                if (!stristr($product->getDescription(), '<div class="row')) {
                    $product->setDescription('<div class="row ui-droppable"><div class="col-md-12 col-sm-12 col-xs-12 column"><div class="ge-content ge-content-type-tinymce" data-ge-content-type="tinymce">'. $product->getDescription() .'</div></div></div>');
                }
            }

            // load and return product data
            $this->returnAjax['post']['title'] = $product->getName();
            $this->returnAjax['post']['reference'] = $product->getSku();
            $this->returnAjax['post']['short_description'] = $product->getShortDescription();
            $this->returnAjax['post']['content'] = $product->getDescription();
            $this->returnAjax['post']['slug'] = $product->getUrlKey();
            $this->returnAjax['post']['url'] = $product->getUrlModel()->getUrl($product);
            $this->returnAjax['post']['publish'] = $product->getStatus();
            $this->returnAjax['post']['meta_title'] = $product->getMetaTitle();
            $this->returnAjax['post']['meta_description'] = $product->getMetaDescription();
            //$this->returnAjax['post']['url_canonical'] = '';
            //$this->returnAjax['post']['noindex'] = '';
            //$this->returnAjax['post']['nofollow'] = '';
            //$this->returnAjax['post']['blog_public'] = 1;
        }
    }


    /**
     * Update object name
     * @param $idPost
     * @param $objData
     * @param $type : Product/Cms
     * @param $field : field to update
     */
    public function updateObjectTitle($idPost, $objData, $type, $field)
    {
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->new_title, $this, 1);
    }

    /**
     * @param $idPost
     * @param $objData
     * @param $type
     * @param $field
     */
    public function updateObjectContent($idPost, $objData, $type, $field)
    {
        /* @var $node \DOMElement */
        if (!isset($objData->new_content)) {
            // need more data
            $this->addMsgError('Content not found', 1);
        } else {

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

            foreach ($tabParseScript as $tag => $attr) {
                foreach ($xp->query('//'.$tag) as $node) {
                    // url media in MAZEN
                    $urlFile = $node->getAttribute($attr);

                    // check if is media and already in media library
                    if ($this->optimizmeMazenUtils->isFileMedia($urlFile)) {
                        $urlMediaCMS = $this->optimizmeMazenUtils->isMediaInLibrary($urlFile);
                        if (!$urlMediaCMS) {
                            $resAddImage = $this->optimizmeMazenUtils->addMediaInLibrary($urlFile);
                            if (!$resAddImage) {
                                $this->addMsgError("Error copying img file", 1);
                            } else {
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

            // save content
            $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $newContent, $this);

            if (count($this->tabErrors) == 0) {
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
    public function updateObjectShortDescription($idPost, $objData, $type, $field)
    {
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->new_short_description, $this);
    }

    /**
     * @param $idObject
     * @param $objData
     * @param $tag
     */
    public function updateObjectAttributesTag($idObject, $objData, $tag, $type, $field)
    {
        /* @var $object \Magento\Catalog\Model\Product */
        /* @var $node \DOMElement */

        $boolModified = 0;
        if (!is_numeric($idObject)) {
            // need more data
            $this->addMsgError('ID product not sent', 1);
        }
        if ($objData->url_reference == '') {
            // need more data
            $this->addMsgError('No link found, action canceled', 1);
        } else {
            // get product details
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            if ($type == 'Product' || $type == 'Category') {
                $object = $objectManager->create('Magento\Catalog\Model\Product')->load($idObject);
            }
            else {
                $object = $objectManager->create('Magento\Cms\Model\Page')->load($idObject);
            }

            if ($type == 'Product' || $type == 'Category') {
                $idObject = $object->getId();
            }
            else {
                $idObject = $object->getPageId();
            }

            if ($idObject != '') {
                // load nodes
                $doc = new \DOMDocument;
                if ($field == 'Description') {
                    $nodes = $this->optimizmeMazenUtils->getNodesInDom($doc, $tag, $object->getDescription());
                }
                else {
                    $nodes = $this->optimizmeMazenUtils->getNodesInDom($doc, $tag, $object->getContent());
                }

                if ($nodes->length > 0) {
                    foreach ($nodes as $node) {
                        if ($tag == 'img') {
                            if ($node->getAttribute('src') == $objData->url_reference) {
                                // image found in source: update (force utf8)
                                $boolModified = 1;

                                if ($objData->alt_image != '') {
                                    $node->setAttribute('alt', utf8_encode($objData->alt_image));
                                } else {
                                    $node->removeAttribute('alt');
                                }

                                if ($objData->title_image != '') {
                                    $node->setAttribute('title', utf8_encode($objData->title_image));
                                } else {
                                    $node->removeAttribute('title');
                                }
                            }
                        } elseif ($tag == 'a') {
                            if ($node->getAttribute('href') == $objData->url_reference) {
                                // href found in source: update (force utf8)
                                $boolModified = 1;

                                if ($objData->rel_lien != '') {
                                    $node->setAttribute('rel', utf8_encode($objData->rel_lien));
                                } else {
                                    $node->removeAttribute('rel');
                                }

                                if ($objData->target_lien != '') {
                                    $node->setAttribute('target', utf8_encode($objData->target_lien));
                                } else {
                                    $node->removeAttribute('target');
                                }
                            }
                        }
                    }
                }

                if ($boolModified == 1) {
                    // action done: save new content
                    // root span to remove
                    $newContent = $this->optimizmeMazenUtils->getHtmlFromDom($doc);

                    // update
                    $this->optimizmeMazenUtils->saveObjField($idObject, $field, $type, $newContent, $this);
                } else {
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
    public function updateObjectMetaDescription($idPost, $objData, $type, $field)
    {
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->meta_description, $this);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectMetaTitle($idPost, $objData, $type, $field)
    {
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->meta_title, $this);
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateCanonicalUrl($idPost, $objData)
    {
        // TODO magento
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateMetaRobots($idPost, $objData)
    {
        // TODO magento
    }

    /**
     * @param $idPost
     * @param $objData
     */
    public function updateObjectStatus($idPost, $objData, $type, $field)
    {
        if (!isset($objData->is_publish) || $objData->is_publish == 0) {
            $objData->is_publish = 0;
        } else {
            $objData->is_publish = 1;
        }

        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->is_publish, $this, 1);
    }

    /**
     * Change permalink of a post
     * and add a redirection
     * @param $idPost
     * @param $objData
     * @param $type
     * @param $field
     */
    public function updateObjectSlug($idPost, $objData, $type, $field)
    {
        /* @var $objectInit \Magento\Catalog\Model\Product */
        /* @var $objectExpected \Magento\Catalog\Model\Product */

        if (!is_numeric($idPost)) {
            // need more data
            $this->addMsgError('ID object missing');
        } elseif ($objData->new_slug == '') {
            // no empty
            $this->addMsgError('This field is required');
        } else {
            // load object init (for after)
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            if ($type == 'Product' || $type == 'Category') {
                $namespaceModel = 'Catalog';
            }
            else {
                $namespaceModel = 'Cms';
            }
            $objectInit = $objectManager->create('Magento\\'. $namespaceModel .'\Model\\'. $type)->load($idPost);

            // load
            if ($type == 'Product') {
                $redirectFrom = $this->productUrlPathGenerator->getUrlPathWithSuffix($objectInit, $objectInit->getStoreId());
            }
            elseif ($type == 'Category') {
                $redirectFrom = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($objectInit, $objectInit->getStoreId());
            }
            else {
                $redirectFrom = $objectInit->getIdentifier();
            }

            // if custom url exists: remove
            $objectExpected = $objectInit;
            if ($type == 'Product') {
                $objectExpected->setUrlKey($objData->new_slug);
                $redirectCheck = $this->productUrlPathGenerator->getUrlPathWithSuffix($objectExpected, $objectExpected->getStoreId());
            }
            elseif ($type == 'Category') {
                $objectExpected->setUrlKey($objData->new_slug);
                $redirectCheck = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($objectExpected, $objectExpected->getStoreId());
            }
            else {
                $objectExpected->setIdentifier($objData->new_slug);
                $redirectCheck = $objectExpected->getIdentifier();
            }

            // is it correct?
            //$this->optimizmeMazenRedirections->deleteRedirectionByRequestPath($redirectCheck);

            // save new url key
            $objectUpdated = $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->new_slug, $this, 1);


            if (!$objectUpdated) {
                // no update
            } else {

                if ($type == 'Product' || $type == 'Category') {
                    $idObjectUpdated = $objectUpdated->getId();
                }
                else {
                    $idObjectUpdated = $objectUpdated->getPageId();
                }

                if (isset($idObjectUpdated) && $idObjectUpdated != '') {

                    // save url key ok : change url
                    // get redirects (from >> to)
                    if ($type == 'Product') {
                        // product
                        //$idObjectUpdated = $objectUpdated->getId();
                        $this->returnAjax['url'] = $objectUpdated->getUrlModel()->getUrl($objectUpdated);
                        $this->returnAjax['new_slug'] = $objectUpdated->getUrlKey();
                        $redirectTo = $this->productUrlPathGenerator->getUrlPathWithSuffix($objectUpdated, $objectUpdated->getStoreId());
                        $entityType = 'product';
                    }
                    elseif ($type == 'Category') {
                        // product category
                        //$idObjectUpdated = $objectUpdated->getId();
                        $this->returnAjax['url'] = $objectUpdated->getUrl();
                        $this->returnAjax['new_slug'] = $objectUpdated->getUrlKey();
                        $redirectTo = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($objectUpdated, $objectUpdated->getStoreId());
                        $entityType = 'category';
                    }
                    else {
                        // cms page
                        //$idObjectUpdated = $objectUpdated->getPageId();
                        $this->returnAjax['url'] = $objectManager->create('Magento\Cms\Helper\Page')->getPageUrl($idObjectUpdated);
                        $this->returnAjax['new_slug'] = $objectUpdated->getIdentifier();
                        $redirectTo = $objectUpdated->getIdentifier();
                        $entityType = 'cms-page';
                    }

                    $this->returnAjax['message'] = 'URL changed';

                    // add custom url rewrite
                    $this->optimizmeMazenRedirections->addRedirection($idObjectUpdated, $redirectFrom, $redirectTo, $objectUpdated->getStoreId(), $entityType);
                }
            }
        }
    }

    /**
     * Change reference for a product
     * @param $idPost
     * @param $objData
     */
    public function updateObjectReference($idPost, $objData, $type, $field)
    {
        $this->optimizmeMazenUtils->saveObjField($idPost, $field, $type, $objData->new_reference, $this, 1);
    }




    ////////////////////////////////////////////////
    //              PRODUCT CATEGORIES
    ////////////////////////////////////////////////

    /**
     * Load categories list
     */
    public function loadCategories()
    {

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
    public function loadCategoryContent($elementId)
    {
        /* @var $category \Magento\Catalog\Model\Category */
        $tabCategory = array();

        // get category details
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $category = $objectManager->create('Magento\Catalog\Model\Category')->load($elementId);

        if ($category->getId() && $category->getId() != '') {
            $tabCategory['id'] = $category->getId();
            $tabCategory['name'] = $category->getName();
            $tabCategory['slug'] = $category->getUrlKey();
            $tabCategory['url'] = $category->getUrl();
            $tabCategory['description'] = $category->getDescription();
            $tabCategory['meta_title'] = $category->getMetaTitle();
            $tabCategory['meta_description'] = $category->getMetaDescription();
        }

        $this->returnAjax['message'] = 'Category loaded';
        $this->returnAjax['category'] = $tabCategory;
    }

    /**
     * @param $idCategory
     * @param $objData
     */
    public function setCategoryDescription($idCategory, $objData)
    {
        $this->optimizmeMazenUtils->saveObjField($idCategory, 'Description', 'Category', $objData->description, $this);
    }


    ////////////////////////////////////////////////
    //              PAGES
    ////////////////////////////////////////////////


    /**
     * Get cms pages list
     */
    public function getPages(){
        /* @var $page \Magento\Cms\Model\Page\ */

        $tabResults = array();
        $productsReturn = array();

        // récupération de la liste des produits
        $collection = $this->pageCollectionFactory->create();
        $pages = $collection->getData();

        if (count($pages)>0) {
            foreach ($pages as $pageBoucle) {
                // get product details
                $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                $page = $objectManager->create('Magento\Cms\Model\Page')->load($pageBoucle['page_id']);

                if ($page->getTitle() != '') {
                    if ($page->getIsActive() == 1) {
                        $status = 'publish';
                    } else {
                        $status = 'draft';
                    }
                    $prodReturn = array(
                        'ID' => $page->getPageId(),
                        'post_title' => $page->getTitle(),
                        'post_status' => $status
                    );
                    array_push($productsReturn, $prodReturn);
                }
            }
        }

        $tabResults['pages'] = $productsReturn;
        $this->returnAjax['arborescence'] = $tabResults;
    }


    /**
     * Get cms page detail
     * @param $idPost
     */
    public function getPage($idPost)
    {
        /* @var $product \Magento\Cms\Model\Page */
        // get page detail
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $page = $objectManager->create('Magento\Cms\Model\Page')->load($idPost);

        if ($page->getPageId() != '') {

            // is content in "row" for beeing inserted in mazen-dev app
            if (trim($page->getContent()) != '') {
                if (!stristr($page->getContent(), '<div class="row')) {
                    $page->setContent('<div class="row ui-droppable"><div class="col-md-12 col-sm-12 col-xs-12 column"><div class="ge-content ge-content-type-tinymce" data-ge-content-type="tinymce">'. $page->getContent() .'</div></div></div>');
                }
            }

            // load and return page data
            $this->returnAjax['post']['title'] = $page->getTitle();
            $this->returnAjax['post']['short_description'] = $page->getContentHeading();
            $this->returnAjax['post']['content'] = $page->getContent();
            $this->returnAjax['post']['slug'] = $page->getIdentifier();
            $this->returnAjax['post']['url'] = $objectManager->create('Magento\Cms\Helper\Page')->getPageUrl($page->getPageId());
            $this->returnAjax['post']['publish'] = $page->getIsActive();
            $this->returnAjax['post']['meta_title'] = $page->getMetaTitle();
            $this->returnAjax['post']['meta_description'] = $page->getMetaDescription();
        }
    }





    ////////////////////////////////////////////////
    //              REDIRECTION
    ////////////////////////////////////////////////

    /**
     * load list of custom redirections
     */
    public function loadRedirections()
    {
        $tabResults = array();
        $magRedirections = $this->optimizmeMazenRedirections->getAllRedirections();

        if (is_array($magRedirections) && count($magRedirections)>0) {
            foreach ($magRedirections as $redirection) {

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
    public function deleteRedirection($objData)
    {
        if (!isset($objData->id_redirection) || $objData->id_redirection == '') {
            // need more data
            array_push($this->tabErrors, 'Redirection not found');
        } else {
            $this->optimizmeMazenRedirections->deleteRedirection($objData->id_redirection);
        }
    }


    ////////////////////////////////////////////////
    //              SITE
    ////////////////////////////////////////////////

    /**
     * Get secret key for JSON Web Signature
     */
    public function registerCMS($objData)
    {
        if ($this->user->authenticate($objData->login, $objData->password)) {
            // auth ok! we can generate token
            $keyJWT = $this->optimizmeMazenUtils->generateKeyForJwt();
            $this->optimizmeMazenUtils->saveJwtKey($keyJWT);


            // all is ok
            $this->returnAjax['message'] = 'JSON Token generated in Magento.';
            $this->returnAjax['jws_token'] = $keyJWT;
            $this->returnAjax['cms'] = 'magento';
            $this->returnAjax['site_domain'] = $objData->url_cible;
            $this->returnAjax['jwt_disable'] = 1;
        } else {
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
    public function loadLoremIpsum()
    {
        $nbParagraphes = rand(2, 4);
        $content = file_get_contents('http://loripsum.net/api/'.$nbParagraphes.'/short/decorate/');
        $this->returnAjax['content'] = $content;
    }

    /**
     * Check if has error or not
     * @return bool
     */
    public function hasErrors()
    {
        if (is_array($this->tabErrors) && count($this->tabErrors)>0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $msg
     * @param string $typeResult : success, info, warning, danger
     */
    public function setMsgReturn($msg, $typeResult='success')
    {
        $this->returnResult['result'] = $typeResult;
        $this->returnResult['message'] = $msg;

        // return results
        header("Access-Control-Allow-Origin: *");
        header('Content-Type: application/json');
        echo json_encode($this->returnResult);
    }

    /**
     * @param $msg
     * @param string $typeResult : success, info, warning, danger
     */
    public function setDataReturn($tabData, $typeResult='success')
    {
        $this->returnResult['result'] = $typeResult;

        if (is_array($tabData) && count($tabData)>0) {
            foreach ($tabData as $key => $value) {
                $this->returnResult[$key] = $value;
            }
        }

        // return results
        header("Access-Control-Allow-Origin: *");
        header('Content-Type: application/json');
        echo json_encode($this->returnResult);
    }

    /**
     * Création d'un post
     * @param $objData
     */
    public function createPost($objData)
    {
        // TODO magento
    }

    /**
     * @param $msg
     * @param int $trace
     */
    public function addMsgError($msg, $trace=0)
    {
        if ($trace == 1) {
            $logTrace = __CLASS__ . ', ' . debug_backtrace()[1]['function'] . ': ';
        } else {
            $logTrace = '';
        }
        array_push($this->tabErrors, $logTrace. $msg);
    }
}
