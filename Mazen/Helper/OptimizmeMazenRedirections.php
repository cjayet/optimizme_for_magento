<?php
namespace Optimizmeformagento\Mazen\Helper;

/**
 * Class OptimizmeMazenRedirections
 * @package Optimizmeformagento\Mazen\Helper
 */
class OptimizmeMazenRedirections extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $storeManager;
    protected $urlRewriteFactory;
    protected $urlRewrite;
    protected $optimizmeMazenUtils;

    /**
     * OptimizmeMazenRedirections constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite
     * @param OptimizmeMazenUtils $optimizmeMazenUtils
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite,
        \Optimizmeformagento\Mazen\Helper\OptimizmeMazenUtils $optimizmeMazenUtils
    )
    {
        $this->storeManager = $storeManager;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->urlRewrite = $urlRewrite;
        $this->optimizmeMazenUtils = $optimizmeMazenUtils;
    }


    /** add a redirection in url_rewrite */
    function addRedirection($entityId, $oldUrl, $newUrl, $storeId){

        $result = '';

        // add in database if necessary
        if ($oldUrl != $newUrl){
            // check if url already exists
            $redirection = $this->getRedirectionByRequestPath($oldUrl);
            if (is_array($redirection) && count($redirection)>0){
                // update
                $urlRewrite = $this->urlRewrite->load($redirection['url_rewrite_id']);
                $urlRewrite->setTargetPath($newUrl);
                $urlRewrite->save();
            }
            else {
                // insert redirection
                $this->urlRewriteFactory->create()
                    ->setEntityId($entityId)
                    ->setRequestPath($oldUrl)
                    ->setTargetPath($newUrl)
                    ->setEntityType('custom')
                    ->setRedirectType('301')
                    ->setStoreId($storeId)
                    ->save();
            }

            // change all links in post_content
            $this->optimizmeMazenUtils->changeAllLinksInPostContent($oldUrl, $newUrl);

        }
        else {
            $result = 'same';
        }

        return $result;
    }

    /**
     * @param $id
     */
    public function deleteRedirection($id){
        $redirectionToDelete = $this->urlRewrite->load($id);
        if ($redirectionToDelete->getId() && is_numeric($redirectionToDelete->getId())){
            $redirectionToDelete->delete();
        }
    }

    /**
     * @param $requestPath
     * @return array
     */
    public function deleteRedirectionByRequestPath($requestPath){

        $magRedirections = $this->urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('request_path', $requestPath)
            ->getData();

        if (is_array($magRedirections) && count($magRedirections)>0){
            foreach ($magRedirections as $magRedirection){
                $customUrl = $this->urlRewriteFactory->create()->load($magRedirection['url_rewrite_id']);
                if ($customUrl && $customUrl->getId()){
                    $customUrl->delete();
                }
            }
        }

        return $magRedirections;
    }

    /**
     * @param string $statut
     * @return array
     */
    public function getAllRedirections($statut='custom'){

        $magRedirections = $this->urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type', $statut)
            ->getData();

        return $magRedirections;
    }

    /**
     * @param $oldUrl
     * @return mixed
     */
    public function getRedirectionByRequestPath($oldUrl){

        $magRedirections = $this->urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type', 'custom')
            ->addFieldToFilter('request_path', $oldUrl)
            ->getFirstItem()
            ->getData();

        return $magRedirections;
    }
}