<?php
namespace Optimizmeformagento\Passerelle\Block;

class Productslist extends \Magento\Framework\View\Element\Template
{
    protected $_productCollectionFactory;

    /**
     * Productslist constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param array $data
     */
    /*
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        array $data = []
    ) {

        $this->_productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

*/

    /**
     * Get all products
     */

    /*
    public function getProductsList() {

        $tabProducts = array();

        /*
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $collection = $productCollection->create()
            ->addAttributeToSelect('*')
            ->load();

        foreach ($collection as $product){
            //echo 'Name  =  '.$product->getName().'<br />';
            print_r($product->getData());
        }
        */
/*
        echo "PRODUCT : ";
        //print_r($this->_productCollectionFactory);
        die;

        //$collection = $this->_productCollectionFactory->create();
        //print_r($collection);

        die;
    }

*/
    public function getProductsList()
    {
        return 'Hello Prod list!';
    }


    public function getProductsListV2()
    {
        return 'Hello Prod list V2!';
    }

}
