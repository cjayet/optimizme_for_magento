<?php
namespace Optimizmeformagento\Passerelle\Block;

class Index extends \Magento\Framework\View\Element\Template
{
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
        array $data = []
    ) {
        parent::__construct($context, $data);
    }


    /**
     * @return string
     */
    public function getIndex()
    {
        return 'Silence is golden';
    }

}
