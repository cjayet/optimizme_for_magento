<?php
namespace Optimizmeformagento\Passerelle\Block;

class Productslist extends \Magento\Framework\View\Element\Template
{
    protected $_optMeUtils;
    protected $_productCollectionFactory;

    /**
     * Productslist constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Optimizmeformagento\Passerelle\Helper\Optmeutils $OptMeUtils
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Optimizmeformagento\Passerelle\Helper\Optmeutils $OptMeUtils,
        array $data = []
    ) {

        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_optMeUtils = $OptMeUtils;

        parent::__construct($context, $data);
    }



    /**
     * Get all products
     */
    public function getProductsList() {

        $tabProducts = array();

        // v1
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

        // v2
        echo "PRODUCT : ";

        $collection = $this->_productCollectionFactory->create();
        $collection->setPageSize(3);    // LIMIT à 3
        $products = $collection->getData();

        if (is_array($products) && count($products)>0){

            foreach ($products as $product){
                //$this->_optMeUtils->nice($product);
                //$objProduct = $product->load($product->entity_id);

                //$om         =   \Magento\Framework\App\ObjectManager::getInstance();
                //$pdata =   $om->create('Magento\Catalog\Model\Product')->load($product['entity_id']);
                //print_r($pdata->getData());

                $productId = $product['entity_id'];
                $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
                $currentproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);

                //print_r($currentproduct); die;
                //echo $currentproduct->getName();
                //$currentproduct->setName('Joust Duffle Bag');
                //$currentproduct->save();
                echo $currentproduct->getName();

                die;
            }
        }
        else {
            echo "else";
        }

        echo "FIN GET PRODUCT LIST";
        die;
    }


    public function getProductsListV2()
    {
        return 'Hello Prod list V2!';
    }

}
