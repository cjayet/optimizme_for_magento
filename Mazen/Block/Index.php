<?php
namespace Optimizmeformagento\Mazen\Block;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * Index constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
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
