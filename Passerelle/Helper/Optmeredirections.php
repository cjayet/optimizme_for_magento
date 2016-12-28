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

        // add in database
        if ($oldUrl != $newUrl){

            // check if url already exists
            $redirection = $this->getRedirection($oldUrl);
            if ($redirection->id != ''){
                // update existing redirection
                /*$wpdb->update(
                    $wpdb->prefix. 'optimizme_redirections',
                    array('url_redirect' => $newUrl, 'updated_at' => date('Y-m-d H:i:s')),
                    array('id' => $redirection->id ),
                    array('%s', '%s')
                );*/

                // TODO

                $result = 'update';
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

        // check if there is no double redirection
        $this->checkAndPurgeUrlIfDoubleRedirections();

        return $result;
    }


    /**
     * Edit redirection
     */
    function editRedirection($id, $field, $value){
        // TODO
        /*
        global $wpdb;

        if ($id !=''){
            $wpdb->update(
                $wpdb->prefix .'optimizme_redirections',
                array( $field => $value, 'updated_at' => date('Y-m-d H:i:s') ),
                array( 'ID' => $id )
            );
        }
        return false;
        */
    }

    /**
     * @param $id
     */
    function deleteRedirection($id){
        $redirectionToDelete = $this->_urlRewrite->load($id);
        if ($redirectionToDelete->getId() && is_numeric($redirectionToDelete->getId())){
            $redirectionToDelete->delete();
        }
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
    public function getRedirection($oldUrl, $isDisabled=0){
        // TODO
        /*global $wpdb;
        $redirection = 'SELECT * 
                        FROM '. $wpdb->prefix .'optimizme_redirections 
                        WHERE url_base LIKE "%'. $oldUrl .'"
                        AND is_disabled="'. $isDisabled .'" ';
        $objRedirect = $wpdb->get_row($redirection);

        return $objRedirect;
        */
    }


    /**
     * Purge double redirections
     *  ex: link1 redirect to link2
     *      link2 redirect to link3
     *      => link1 redirect to link3
     */
    public function checkAndPurgeUrlIfDoubleRedirections(){

        // TODO

        /*global $wpdb;

        // get redirects which have another redirection
        $sql = 'SELECT r1.id as r1id, r1.url_base as r1url_base, r1.url_redirect as r1url_redirect,
                      r2.id as r2id, r2.url_redirect as r2url_redirect
                FROM '. $wpdb->prefix .'optimizme_redirections r1
                JOIN '. $wpdb->prefix .'optimizme_redirections r2 on r1.id != r2.id
                WHERE r2.url_base = r1.url_redirect';
        $results = $wpdb->get_results($sql);

        if (is_array($results) && count($results)>0){
            foreach ($results as $doubleRedirection){
                $this->editRedirection($doubleRedirection->r1id, 'url_redirect', $doubleRedirection->r2url_redirect);
            }
        }*/

    }


}
