<?php

namespace Optimizmeformagento\Passerelle\Helper;

/**
 * Class Optmeredirections
 * @package Optimizmeformagento\Passerelle\Helper
 */
class Optmeredirections extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_storeManager;
    protected $_urlRewriteFactory;
    protected $_urlRewrite;
    protected $_optmeutils;

    /**
     * Optmeredirections constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite
     * @param Optmeutils $optMeUtils
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite,
        \Optimizmeformagento\Passerelle\Helper\Optmeutils $optMeUtils
    )
    {
        $this->_storeManager = $storeManager;
        $this->_urlRewriteFactory = $urlRewriteFactory;
        $this->_urlRewrite = $urlRewrite;
        $this->_optmeutils = $optMeUtils;
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
                $urlRewrite = $this->_urlRewrite->load($redirection['url_rewrite_id']);
                $urlRewrite->setTargetPath($newUrl);
                $urlRewrite->save();
            }
            else {
                // insert redirection
                $urlRewriteModel = $this->_urlRewriteFactory->create()
                    ->setEntityId($entityId)
                    ->setRequestPath($oldUrl)
                    ->setTargetPath($newUrl)
                    ->setEntityType('custom')
                    ->setRedirectType('301')
                    ->setStoreId($storeId)
                    ->save();
            }

            // change all links in post_content
            $this->_optmeutils->changeAllLinksInPostContent($oldUrl, $newUrl);

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
        $redirectionToDelete = $this->_urlRewrite->load($id);
        if ($redirectionToDelete->getId() && is_numeric($redirectionToDelete->getId())){
            $redirectionToDelete->delete();
        }
    }


    /**
     * @param $requestPath
     * @return array
     */
    public function deleteRedirectionByRequestPath($requestPath){

        $magRedirections = $this->_urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('request_path', $requestPath)
            ->getData();

        if (is_array($magRedirections) && count($magRedirections)>0){
            foreach ($magRedirections as $magRedirection){
                $customUrl = $this->_urlRewriteFactory->create()->load($magRedirection['url_rewrite_id']);
                if ($customUrl && $customUrl->getId()){
                    $customUrl->delete();
                }
            }
        }

        return $magRedirections;
    }



    /**
     * @param string $statut
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getAllRedirections($statut='custom'){

        $magRedirections = $this->_urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type', $statut)
            ->getData();

        return $magRedirections;
    }

    /**
     * @param $oldUrl
     * @return array|null|object|void
     */
    public function getRedirectionByRequestPath($oldUrl){

        $magRedirections = $this->_urlRewriteFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type', 'custom')
            ->addFieldToFilter('request_path', $oldUrl)
            ->getFirstItem()
            ->getData();

        return $magRedirections;
    }
}
