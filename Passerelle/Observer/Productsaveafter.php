<?php

namespace Optimizmeformagento\Passerelle\Observer;

use Magento\Framework\Event\ObserverInterface;

class Productsaveafter implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();  // get product object

        // TODO send back data to easycontent
    }
}