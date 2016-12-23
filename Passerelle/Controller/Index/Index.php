<?php

namespace Optimizmeformagento\Passerelle\Controller\Index;

use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
    protected $_optimizmeaction;
    protected $_optimizmeutils;
    protected $boolNoAction;

    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_resultPageFactory = $resultPageFactory;
        $this->boolNoAction = 0;
        parent::__construct($context);
    }


    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // action
        $this->_optimizmeaction = $this->_objectManager->create('Optimizmeformagento\Passerelle\Helper\Optmeactions');
        $this->_optimizmeutils = $this->_objectManager->create('Optimizmeformagento\Passerelle\Helper\Optmeutils');

        if (isset($_REQUEST['data_optme']) && $_REQUEST['data_optme'] != '')
        {
            // récupération des données
            $dataOptimizme = json_decode(($_REQUEST['data_optme']));

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
                $msg = 'Aucune action de définie';
                $this->_optimizmeaction->setMsgReturn($msg, 'danger');
            }
            else
            {
                // action to do
                switch ($dataOptimizme->action){

                    // post
                    case 'set_post_title' :             $this->_optimizmeaction->updateTitle($postId, $dataOptimizme); break;
                    case 'set_post_content' :           $this->_optimizmeaction->updateContent($postId, $dataOptimizme); break;
                    case 'set_post_shortdescription' :  $this->_optimizmeaction->updateShortDescription($postId, $dataOptimizme); break;
                    case 'set_post_metadescription' :   $this->_optimizmeaction->updateMetaDescription($postId, $dataOptimizme); break;
                    case 'set_post_metatitle' :         $this->_optimizmeaction->updateMetaTitle($postId, $dataOptimizme); break;
                    case 'set_post_slug' :              $this->_optimizmeaction->updateSlug($postId, $dataOptimizme); break;
                    case 'set_post_status' :            $this->_optimizmeaction->updatePostStatus($postId, $dataOptimizme); break;
                    //case 'set_post_imgattributes' :     $optAction->updateAttributesTag($postId, $dataOptimizme, 'img'); break;
                    //case 'set_post_hrefattributes' :    $optAction->updateAttributesTag($postId, $dataOptimizme, 'a'); break;

                    // redirections

                    // load content
                    case 'load_post_content' :          $this->_optimizmeaction->loadPostContent($postId); break;
                    case 'load_posts_pages':            $this->_optimizmeaction->loadPostsPages($dataOptimizme); break;

                    // create content


                    default:                            $this->boolNoAction = 1; break;
                }


                // results of action
                if ($this->boolNoAction == 1)
                {
                    // no action done
                    $msg = 'Aucune action trouvée.';
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