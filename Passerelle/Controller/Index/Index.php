<?php
namespace Optimizmeformagento\Passerelle\Controller\Index;
use Magento\Framework\App\Action\Context;
use Firebase\JWT\JWT;

/**
 * Class Index
 * @package Optimizmeformagento\Passerelle\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
    protected $_optimizmeaction;
    protected $_optimizmeutils;
    protected $_resourceConfig;

    protected $_boolNoAction;
    protected $_OPTIMIZME_JWT_SECRET;

    /**
     * Index constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_boolNoAction = 0;
        $this->_OPTIMIZME_JWT_SECRET = '';

        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $time_pre = microtime(true);
        header("Access-Control-Allow-Origin: *");

        // action
        $this->_optimizmeaction = $this->_objectManager->create('Optimizmeformagento\Passerelle\Helper\Optmeactions');
        $this->_optimizmeutils = $this->_objectManager->create('Optimizmeformagento\Passerelle\Helper\Optmeutils');

        if (isset($_REQUEST['data_optme']) && $_REQUEST['data_optme'] != '')
        {
            // is valid request?
            if ( substr_count($_REQUEST['data_optme'], '.') == 2){

                // JWT
                try {
                    // try decode JSON Web Token
                    $this->_OPTIMIZME_JWT_SECRET = $this->_optimizmeutils->getJwtKey();
                    $decoded = JWT::decode($_REQUEST['data_optme'], $this->_OPTIMIZME_JWT_SECRET, array('HS256'));
                    $dataOptimizme = $decoded;
                } catch (\Firebase\JWT\SignatureInvalidException $e){
                    $msg = 'JSON Web Token not decoded properly: '. $e->getMessage();
                    $this->_optimizmeaction->setMsgReturn($msg, 'danger');
                    die;
                }
            }
            else {

                // simple JSON, only for "register_cms" action
                $dataOptimizme = json_decode(stripslashes($_REQUEST['data_optme']));
                if ( !is_object($dataOptimizme) || $dataOptimizme->action != 'register_cms'){
                    $msg = 'JSON Web Token needed.';
                    $this->_optimizmeaction->setMsgReturn($msg, 'danger');
                    die;
                }
            }

            // post id
            $postId = '';
            if (is_numeric($dataOptimizme->url_cible))      $postId = $dataOptimizme->url_cible;
            else {
                if (isset($dataOptimizme->id_post) && $dataOptimizme->id_post != ''){
                    $postId = $dataOptimizme->id_post;
                }
            }


            // ACTIONS
            if ($dataOptimizme->action == '')
            {
                // no action specified
                $msg = 'No action defined';
                $this->_optimizmeaction->setMsgReturn($msg, 'danger');
            }
            else
            {
                // action to do
                switch ($dataOptimizme->action){

                    // init dialog
                    case 'register_cms':                $this->_optimizmeaction->registerCMS($dataOptimizme); break;

                    // post
                    case 'set_post_title' :             $this->_optimizmeaction->updateTitle($postId, $dataOptimizme); break;
                    case 'set_post_content' :           $this->_optimizmeaction->updateContent($postId, $dataOptimizme); break;
                    case 'set_post_shortdescription' :  $this->_optimizmeaction->updateShortDescription($postId, $dataOptimizme); break;
                    case 'set_post_metadescription' :   $this->_optimizmeaction->updateMetaDescription($postId, $dataOptimizme); break;
                    case 'set_post_metatitle' :         $this->_optimizmeaction->updateMetaTitle($postId, $dataOptimizme); break;
                    case 'set_post_slug' :              $this->_optimizmeaction->updateSlug($postId, $dataOptimizme); break;
                    case 'set_post_status' :            $this->_optimizmeaction->updatePostStatus($postId, $dataOptimizme); break;
                    case 'set_post_imgattributes' :     $this->_optimizmeaction->updateAttributesTag($postId, $dataOptimizme, 'img'); break;
                    case 'set_post_hrefattributes' :    $this->_optimizmeaction->updateAttributesTag($postId, $dataOptimizme, 'a'); break;

                    // redirections
                    case 'load_redirections':           $this->_optimizmeaction->loadRedirections(); break;
                    case 'redirection_delete':          $this->_optimizmeaction->deleteRedirection($dataOptimizme); break;

                    // load content
                    case 'load_post_content' :          $this->_optimizmeaction->loadPostContent($postId); break;
                    case 'load_posts_pages':            $this->_optimizmeaction->loadPostsPages($dataOptimizme); break;

                    // categories
                    case 'load_categories':             $this->_optimizmeaction->loadCategories($dataOptimizme); break;
                    case 'load_category_content':       $this->_optimizmeaction->loadCategoryContent($postId, $dataOptimizme); break;
                    case 'set_category_name':           $this->_optimizmeaction->setCategoryName($postId, $dataOptimizme); break;
                    case 'set_category_description':    $this->_optimizmeaction->setCategoryDescription($postId, $dataOptimizme); break;
                    case 'set_category_slug':           $this->_optimizmeaction->updateCategorySlug($postId, $dataOptimizme); break;

                    // create content
                    // TODO magento

                    default:                            $this->_boolNoAction = 1; break;
                }

                // calculate execution time
                $time_post = microtime(true);
                $exec_time = $time_post - $time_pre;
                $this->_optimizmeaction->returnAjax['time'] = $exec_time;

                // results of action
                if ($this->_boolNoAction == 1)
                {
                    // no action done
                    $msg = 'No action found.';
                    $this->_optimizmeaction->setMsgReturn($msg, 'danger');
                }
                else
                {
                    // action done
                    if (is_array($this->_optimizmeaction->tabErrors) && count($this->_optimizmeaction->tabErrors) > 0)
                    {
                        $this->_optimizmeaction->returnResult['result'] = 'danger';
                        $msg = 'Une ou plusieurs erreurs ont été levées : ';
                        $msg .= $this->_optimizmeutils->getListMessages($this->_optimizmeaction->tabErrors, 1);
                        $this->_optimizmeaction->setMsgReturn($msg, 'danger');
                    }
                    elseif (is_array($this->_optimizmeaction->returnAjax) && count($this->_optimizmeaction->returnAjax) > 0)
                    {
                        // ajax to return - encode data
                        $this->_optimizmeaction->setDataReturn($this->_optimizmeaction->returnAjax);
                    }
                    else
                    {
                        // no error, OK !
                        $msg = 'Modification effectuée avec succès.';
                        $msg .= $this->_optimizmeutils->getListMessages($this->_optimizmeaction->tabSuccess);
                        $this->_optimizmeaction->setMsgReturn($msg);
                    }
                }
            }


            // stop script - no need to go further
            die;
        }


        $resultPage = $this->_resultPageFactory->create();
        return $resultPage;
    }
}