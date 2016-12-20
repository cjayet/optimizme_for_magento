<?php
namespace Optimizmeformagento\Passerelle\Block;


class Helloworld extends \Magento\Framework\View\Element\Template
{
    public function psrlMsg()
    {
        $tabMsg = array('result' => 'success',
                        'message' => 'Liste bien récupérée',
                        'products' => array('prod1', 'prod2')
            );
        return $tabMsg;
    }
}
