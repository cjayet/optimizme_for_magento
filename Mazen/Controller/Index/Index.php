<?php
/**
 * MAZEN main controller
 * @category
 */

namespace Optimizmeformagento\Mazen\Controller\Index;

use Magento\Framework\App\Action\Context;
use Firebase\JWT\JWT;

/**
 * Class Index
 *
 * @package Optimizmeformagento\Mazen\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $optimizmeaction;
    protected $optimizmeutils;
    protected $resourceConfig;
    protected $jsonHelper;

    protected $boolNoAction;
    protected $OPTIMIZME_MAZEN_JWT_SECRET;

    /**
     * Index constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->boolNoAction = 0;
        $this->OPTIMIZME_MAZEN_JWT_SECRET = '';

        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /* @var $optimizmeMazenAction \Optimizmeformagento\Mazen\Helper\OptimizmeMazenActions */
        /* @var $optimizmeMazenUtils \Optimizmeformagento\Mazen\Helper\OptimizmeMazenUtils */
        header("Access-Control-Allow-Origin: *");

        // load helper classes
        $this->optimizmeaction = $this->_objectManager->create('Optimizmeformagento\Mazen\Helper\OptimizmeMazenActions');
        $this->optimizmeutils = $this->_objectManager->create('Optimizmeformagento\Mazen\Helper\OptimizmeMazenUtils');
        $optimizmeMazenAction = $this->optimizmeaction;
        $optimizmeMazenUtils = $this->optimizmeutils;

        // load JWT
        $this->OPTIMIZME_MAZEN_JWT_SECRET = $optimizmeMazenUtils->getJwtKey();

        $isDataFormMazen = false;

        $getRequestDataOtpme = $this->getRequest()->getParam('data_optme');

        if (isset($getRequestDataOtpme) && $getRequestDataOtpme != '') {
            // request found
            $requestDataOptme = new \stdClass();
            $requestDataOptme->data_optme = $getRequestDataOtpme;
            $requestDataOptme = json_encode($requestDataOptme);
            $isDataFormMazen = true;
        } else {
            // try to get application/json content
            $requestDataOptme = stripslashes(file_get_contents('php://input'));
            if (strstr($requestDataOptme, 'data_optme')) {
                $isDataFormMazen = true;
            }
        }

        if (isset($requestDataOptme) && $requestDataOptme != '' && $isDataFormMazen == true) {
            $doAction = 1;
            $jsonData = json_decode($requestDataOptme);
            if (!isset($jsonData->data_optme) || $jsonData->data_optme == '') {
                // nothing to do
                $doAction = 0;
            } else {
                if ($optimizmeMazenUtils->optMazenIsJwt($jsonData->data_optme)) {
                    // JWT
                    if (!isset($this->OPTIMIZME_MAZEN_JWT_SECRET) || $this->OPTIMIZME_MAZEN_JWT_SECRET == '') {
                        $msg = 'JSON Web Token not defined, this CMS is not registered.';
                        $this->setMsgReturn($msg, 'danger');
                        $doAction = 0;
                    } else {
                        try {
                            // try decode JSON Web Token
                            $decoded = JWT::decode($jsonData->data_optme, $this->OPTIMIZME_MAZEN_JWT_SECRET, ['HS256']);
                            $dataOptimizme = $decoded;
                        } catch (\Firebase\JWT\SignatureInvalidException $e) {
                            $msg = 'JSON Web Token not decoded properly, secret may be not correct';
                            $this->setMsgReturn($msg, 'danger');
                            $doAction = 0;
                        }
                    }
                } else {
                    // simple JSON, only for "register_cms" action
                    $dataOptimizme = $jsonData->data_optme;
                    if (!is_object($dataOptimizme) || $dataOptimizme->action != 'register_cms') {
                        $msg = 'JSON Web Token needed';
                        $this->setMsgReturn($msg, 'danger');
                        $doAction = 0;
                    }
                }
            }

            if ($doAction == 1) {

                $postId = '';
                if (is_numeric($dataOptimizme->url_cible)) {
                    $postId = $dataOptimizme->url_cible;
                } else {
                    if (isset($dataOptimizme->id_post) && $dataOptimizme->id_post != '') {
                        $postId = $dataOptimizme->id_post;
                    }
                }

                // ACTIONS
                if ($dataOptimizme->action == '') {
                    // no action specified
                    $msg = 'No action defined';
                    $this->setMsgReturn($msg, 'danger');
                } else {
                    // action to do
                    switch ($dataOptimizme->action) {
                        // init dialog
                        case 'register_cms':
                            $optimizmeMazenAction->registerCMS($dataOptimizme);
                            break;

                        // products
                        case 'get_products':
                            $optimizmeMazenAction->getProducts();
                            break;
                        case 'get_product':
                            $optimizmeMazenAction->getProduct($postId);
                            break;
                        case 'set_product_title':
                            $optimizmeMazenAction->updateObjectTitle($postId, $dataOptimizme, 'Product', 'Name');
                            break;
                        case 'set_product_reference':
                            $optimizmeMazenAction->updateObjectReference($postId, $dataOptimizme, 'Product', 'Sku');
                            break;
                        case 'set_product_content':
                            $optimizmeMazenAction->updateObjectContent($postId, $dataOptimizme, 'Product', 'Description');
                            break;
                        case 'set_product_shortdescription':
                            $optimizmeMazenAction->updateObjectShortDescription($postId, $dataOptimizme, 'Product', 'ShortDescription');
                            break;
                        case 'set_product_metadescription':
                            $optimizmeMazenAction->updateObjectMetaDescription($postId, $dataOptimizme, 'Product', 'MetaDescription');
                            break;
                        case 'set_product_metatitle':
                            $optimizmeMazenAction->updateObjectMetaTitle($postId, $dataOptimizme, 'Product', 'MetaTitle');
                            break;
                        case 'set_product_slug':
                            $optimizmeMazenAction->updateObjectSlug($postId, $dataOptimizme, 'Product', 'UrlKey');
                            break;
                        case 'set_product_status':
                            $optimizmeMazenAction->updateObjectStatus($postId, $dataOptimizme, 'Product', 'Status');
                            break;
                        case 'set_product_imgattributes':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'img', 'Product', 'Description');
                            break;
                        case 'set_product_hrefattributes':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'a', 'Product', 'Description');
                            break;
                        case 'set_product_h1':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h1', 'Product', 'Description');
                            break;
                        case 'set_product_h2':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h2', 'Product', 'Description');
                            break;
                        case 'set_product_h3':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h3', 'Product', 'Description');
                            break;
                        case 'set_product_h4':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h4', 'Product', 'Description');
                            break;
                        case 'set_product_h5':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h5', 'Product', 'Description');
                            break;
                        case 'set_product_h6':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h6', 'Product', 'Description');
                            break;

                        // CMS pages
                        case 'get_posts':
                            $optimizmeMazenAction->getPages();
                            break;
                        case 'get_post':
                            $optimizmeMazenAction->getPage($postId);
                            break;
                        case 'set_post_title':
                            $optimizmeMazenAction->updateObjectTitle($postId, $dataOptimizme, 'Page', 'Title');
                            break;
                        case 'set_post_slug':
                            $optimizmeMazenAction->updateObjectSlug($postId, $dataOptimizme, 'Page', 'Identifier');
                            break;
                        case 'set_post_metatitle':
                            $optimizmeMazenAction->updateObjectMetaTitle($postId, $dataOptimizme, 'Page', 'Metatitle');
                            break;
                        case 'set_post_metadescription':
                            $optimizmeMazenAction->updateObjectMetaDescription($postId, $dataOptimizme, 'Page', 'Metadescription');
                            break;
                        case 'set_post_shortdescription':
                            $optimizmeMazenAction->updateObjectShortDescription($postId, $dataOptimizme, 'Page', 'ContentHeading');
                            break;
                        case 'set_post_status':
                            $optimizmeMazenAction->updateObjectStatus($postId, $dataOptimizme, 'Page', 'IsActive');
                            break;
                        case 'set_post_content':
                            $optimizmeMazenAction->updateObjectContent($postId, $dataOptimizme, 'Page', 'Content');
                            break;
                        case 'set_post_imgattributes':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'img', 'Page', 'Content');
                            break;
                        case 'set_post_hrefattributes':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'a', 'Page', 'Content');
                            break;
                        case 'set_post_h1':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h1', 'Page', 'Content');
                            break;
                        case 'set_post_h2':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h2', 'Page', 'Content');
                            break;
                        case 'set_post_h3':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h3', 'Page', 'Content');
                            break;
                        case 'set_post_h4':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h4', 'Page', 'Content');
                            break;
                        case 'set_post_h5':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h5', 'Page', 'Content');
                            break;
                        case 'set_post_h6':
                            $optimizmeMazenAction->updateObjectAttributesTag($postId, $dataOptimizme, 'h6', 'Page', 'Content');
                            break;

                        // product categories
                        case 'get_product_categories':
                            $optimizmeMazenAction->loadCategories();
                            break;
                        case 'get_product_category':
                            $optimizmeMazenAction->loadCategoryContent($postId);
                            break;
                        case 'set_product_category_name':
                            $optimizmeMazenAction->updateObjectTitle($postId, $dataOptimizme, 'Category', 'Name');
                            break;
                        case 'set_product_category_description':
                            $optimizmeMazenAction->updateObjectContent($postId, $dataOptimizme, 'Category', 'Description');
                            break;
                        case 'set_product_category_slug':
                            $optimizmeMazenAction->updateObjectSlug($postId, $dataOptimizme, 'Category', 'UrlKey');
                            break;
                        case 'set_product_category_metatitle':
                            $optimizmeMazenAction->updateObjectMetaTitle($postId, $dataOptimizme, 'Category', 'Meta_title');
                            break;
                        case 'set_product_category_metadescription':
                            $optimizmeMazenAction->updateObjectMetaDescription($postId, $dataOptimizme, 'Category', 'Meta_description');
                            break;

                        // redirections
                        case 'get_redirections':
                            $optimizmeMazenAction->loadRedirections();
                            break;
                        case 'delete_redirection':
                            $optimizmeMazenAction->deleteRedirection($dataOptimizme);
                            break;

                        // default
                        default:
                            $this->boolNoAction = 1;
                            break;
                    }

                    // results of action
                    if ($this->boolNoAction == 1) {
                        // no action done
                        $msg = 'No action found!';
                        $this->setMsgReturn($msg, 'danger');
                    } else {
                        // action done
                        if (is_array($optimizmeMazenAction->tabErrors) && count($optimizmeMazenAction->tabErrors) > 0) {
                            $optimizmeMazenAction->returnResult['result'] = 'danger';
                            $msg = 'One or several errors have been raised: ';
                            $msg .= $optimizmeMazenUtils->getListMessages($optimizmeMazenAction->tabErrors);
                            $this->setMsgReturn($msg, 'danger');
                        } elseif (is_array($optimizmeMazenAction->returnAjax) && count($optimizmeMazenAction->returnAjax) > 0) {
                            // ajax to return - encode data
                            $this->setDataReturn($optimizmeMazenAction->returnAjax);
                        } else {
                            // no error, OK !
                            $msg = 'Action done!';
                            $msg .= $optimizmeMazenUtils->getListMessages($optimizmeMazenAction->tabSuccess);
                            $this->setMsgReturn($msg);
                        }
                    }
                }
            }


        }
    }


    /**
     * Return custom JSON message
     * @param $tabData
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
        $jsonHelper = $this->_objectManager->create('Magento\Framework\Json\Helper\Data');
        $encodedData = $jsonHelper->jsonEncode($this->returnResult);
        $this->getResponse()->setHeader('Content-type', 'application/json')->setBody($encodedData);
    }

    /**
     * Return simple JSON message
     * @param $msg
     * @param string $typeResult : success, info, warning, danger
     */
    public function setMsgReturn($msg, $typeResult='success')
    {
        $this->returnResult['result'] = $typeResult;
        $this->returnResult['message'] = $msg;

        // return results
        $jsonHelper = $this->_objectManager->create('Magento\Framework\Json\Helper\Data');
        $encodedData = $jsonHelper->jsonEncode($this->returnResult);
        $this->getResponse()->setHeader('Content-type', 'application/json')->setBody($encodedData);
    }
}
