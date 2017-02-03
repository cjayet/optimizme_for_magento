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
    )
    {
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
        header("Access-Control-Allow-Origin: *");

        //echo "dans execute"; die;
        // action
        $this->optimizmeaction = $this->_objectManager->create('Optimizmeformagento\Mazen\Helper\OptimizmeMazenActions');
        $this->optimizmeutils = $this->_objectManager->create('Optimizmeformagento\Mazen\Helper\OptimizmeMazenUtils');

        if (isset($_REQUEST['data_optme']) && $_REQUEST['data_optme'] != '')
        {
            // is valid request?
            if ( substr_count($_REQUEST['data_optme'], '.') == 2){

                // JWT
                try {
                    // try decode JSON Web Token
                    $this->OPTIMIZME_MAZEN_JWT_SECRET = $this->optimizmeutils->getJwtKey();
                    $decoded = JWT::decode($_REQUEST['data_optme'], $this->OPTIMIZME_MAZEN_JWT_SECRET, array('HS256'));
                    $dataOptimizme = $decoded;
                } catch (\Firebase\JWT\SignatureInvalidException $e){
                    $msg = 'JSON Web Token not decoded properly: '. $e->getMessage();
                    $this->optimizmeaction->setMsgReturn($msg, 'danger');
                    die;
                }
            }
            else {

                // simple JSON, only for "register_cms" action
                $dataOptimizme = json_decode(stripslashes($_REQUEST['data_optme']));
                if ( !is_object($dataOptimizme) || $dataOptimizme->action != 'register_cms'){
                    $msg = 'JSON Web Token needed.';
                    $this->optimizmeaction->setMsgReturn($msg, 'danger');
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
                $this->optimizmeaction->setMsgReturn($msg, 'danger');
            }
            else
            {
                // action to do
                switch ($dataOptimizme->action){

                    // init dialog
                    case 'register_cms':                $this->optimizmeaction->registerCMS($dataOptimizme); break;

                    // post
                    case 'set_post_title' :             $this->optimizmeaction->updateTitle($postId, $dataOptimizme); break;
                    case 'set_post_content' :           $this->optimizmeaction->updateContent($postId, $dataOptimizme); break;
                    case 'set_post_shortdescription' :  $this->optimizmeaction->updateShortDescription($postId, $dataOptimizme); break;
                    case 'set_post_metadescription' :   $this->optimizmeaction->updateMetaDescription($postId, $dataOptimizme); break;
                    case 'set_post_metatitle' :         $this->optimizmeaction->updateMetaTitle($postId, $dataOptimizme); break;
                    case 'set_post_slug' :              $this->optimizmeaction->updateSlug($postId, $dataOptimizme); break;
                    case 'set_post_status' :            $this->optimizmeaction->updatePostStatus($postId, $dataOptimizme); break;
                    case 'set_post_imgattributes' :     $this->optimizmeaction->updateAttributesTag($postId, $dataOptimizme, 'img'); break;
                    case 'set_post_hrefattributes' :    $this->optimizmeaction->updateAttributesTag($postId, $dataOptimizme, 'a'); break;
                    case 'set_post_reference' :         $this->optimizmeaction->setReference($postId, $dataOptimizme); break;

                    // redirections
                    case 'load_redirections':           $this->optimizmeaction->loadRedirections(); break;
                    case 'redirection_delete':          $this->optimizmeaction->deleteRedirection($dataOptimizme); break;

                    // load content
                    case 'load_post_content' :          $this->optimizmeaction->loadPostContent($postId); break;
                    case 'load_posts_pages':            $this->optimizmeaction->loadPostsPages(); break;

                    // categories
                    case 'load_categories':             $this->optimizmeaction->loadCategories(); break;
                    case 'load_category_content':       $this->optimizmeaction->loadCategoryContent($postId); break;
                    case 'set_category_name':           $this->optimizmeaction->setCategoryName($postId, $dataOptimizme); break;
                    case 'set_category_description':    $this->optimizmeaction->setCategoryDescription($postId, $dataOptimizme); break;
                    case 'set_category_slug':           $this->optimizmeaction->updateCategorySlug($postId, $dataOptimizme); break;

                    // create content
                    // TODO magento

                    default:                            $this->boolNoAction = 1; break;
                }

                // results of action
                if ($this->boolNoAction == 1)
                {
                    // no action done
                    $msg = 'No action found.';
                    $this->optimizmeaction->setMsgReturn($msg, 'danger');
                }
                else
                {
                    // action done
                    if (is_array($this->optimizmeaction->tabErrors) && count($this->optimizmeaction->tabErrors) > 0)
                    {
                        $this->optimizmeaction->returnResult['result'] = 'danger';
                        $msg = 'Une ou plusieurs erreurs ont été levées : ';
                        $msg .= $this->optimizmeutils->getListMessages($this->optimizmeaction->tabErrors, 1);
                        $this->optimizmeaction->setMsgReturn($msg, 'danger');
                    }
                    elseif (is_array($this->optimizmeaction->returnAjax) && count($this->optimizmeaction->returnAjax) > 0)
                    {
                        // ajax to return - encode data
                        $this->optimizmeaction->setDataReturn($this->optimizmeaction->returnAjax);
                    }
                    else
                    {
                        // no error, OK !
                        $msg = 'Action done!';
                        $msg .= $this->optimizmeutils->getListMessages($this->optimizmeaction->tabSuccess);
                        $this->optimizmeaction->setMsgReturn($msg);
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