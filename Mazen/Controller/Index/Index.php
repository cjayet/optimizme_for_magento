<?php
namespace Optimizmeformagento\Mazen\Controller\Index;

use Magento\Framework\App\Action\Context;
use Firebase\JWT\JWT;

/**
 * Class Index
 * @package Optimizmeformagento\Mazen\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $optimizmeaction;
    protected $optimizmeutils;
    protected $resourceConfig;

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

        if (isset($_REQUEST['data_optme'])) {
            // $_POST/$_GET
            $requestDataOptme = new \stdClass();
            $requestDataOptme->data_optme = $_REQUEST['data_optme'];
            $requestDataOptme = json_encode($requestDataOptme);
        } else {
            // try to get application/json content
            $requestDataOptme = stripslashes(file_get_contents('php://input'));
        }

        if (isset($requestDataOptme) && $requestDataOptme != '') {
            $jsonData = json_decode($requestDataOptme);
            if (!isset($jsonData->data_optme) || $jsonData->data_optme == '') {
                exit;
            }

            if ($optimizmeMazenUtils->optMazenIsJwt($jsonData->data_optme)) {
                // JWT
                if (!isset($this->OPTIMIZME_MAZEN_JWT_SECRET) || $this->OPTIMIZME_MAZEN_JWT_SECRET == '') {
                    $msg = 'JSON Web Token not defined, this CMS is not registered.';
                    $optimizmeMazenAction->setMsgReturn($msg, 'danger');
                    die;
                } else {
                    try {
                        // try decode JSON Web Token
                        $decoded = JWT::decode($jsonData->data_optme, $this->OPTIMIZME_MAZEN_JWT_SECRET, array('HS256'));
                        $dataOptimizme = $decoded;
                    } catch (\Firebase\JWT\SignatureInvalidException $e) {
                        $msg = 'JSON Web Token not decoded properly, secret may be not correct';
                        $optimizmeMazenAction->setMsgReturn($msg, 'danger');
                        die;
                    }

                    // log action
                    /*
                    $logContent = "\n--------------\n". 'Date '. date('Y-m-d H:i:s') ."\n";
                    $logContent .= 'Data: '. $requestDataOptme . "\n";

                    try {
                        if (is_writable($tOPTIMIZME_MAZEN_LOGS)) {
                            if ($handle = fopen(OPTIMIZME_MAZEN_LOGS, 'a+')) {
                                fwrite($handle, $logContent);
                                fclose($handle);
                            }
                        }
                    } catch (\Exception $e) {
                        // no log
                    }
                    */
                }
            } else {
                // simple JSON, only for "register_cms" action
                $dataOptimizme = $jsonData->data_optme;
                if (!is_object($dataOptimizme) || $dataOptimizme->action != 'register_cms') {
                    $msg = 'JSON Web Token needed';
                    $optimizmeMazenAction->setMsgReturn($msg, 'danger');
                    die;
                }
            }

            // post id
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
                $optimizmeMazenAction->setMsgReturn($msg, 'danger');
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
                    case 'set_product_reference':
                        $optimizmeMazenAction->updateObjectReference($postId, $dataOptimizme, 'Product', 'Sku');
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
                    $optimizmeMazenAction->setMsgReturn($msg, 'danger');
                } else {
                    // action done
                    if (is_array($optimizmeMazenAction->tabErrors) && count($optimizmeMazenAction->tabErrors) > 0) {
                        $optimizmeMazenAction->returnResult['result'] = 'danger';
                        $msg = 'Une ou plusieurs erreurs ont été levées : ';
                        $msg .= $optimizmeMazenUtils->getListMessages($optimizmeMazenAction->tabErrors, 1);
                        $optimizmeMazenAction->setMsgReturn($msg, 'danger');
                    } elseif (is_array($optimizmeMazenAction->returnAjax) && count($optimizmeMazenAction->returnAjax) > 0) {
                        // ajax to return - encode data
                        $optimizmeMazenAction->setDataReturn($optimizmeMazenAction->returnAjax);
                    } else {
                        // no error, OK !
                        $msg = 'Action done!';
                        $msg .= $optimizmeMazenUtils->getListMessages($optimizmeMazenAction->tabSuccess);
                        $optimizmeMazenAction->setMsgReturn($msg);
                    }
                }
            }

            // stop script - no need to go further
            die;
        }

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
