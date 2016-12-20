<?php
namespace Optimizmeformagento\Passerelle\Block;

class Getproductslist extends \Magento\Framework\View\Element\Template
{
    /**
     * Get all products
     */
    public function getProductsList() {

        $tabProducts = array();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $collection = $productCollection->create()
            ->addAttributeToSelect('*')
            ->load();

        foreach ($collection as $product){
            echo 'Name  =  '.$product->getName().'<br />';
        }

        die;
    }


}
