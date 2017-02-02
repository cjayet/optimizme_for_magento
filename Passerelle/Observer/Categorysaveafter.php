<?php

namespace Optimizmeformagento\Passerelle\Observer;

use Magento\Framework\Event\ObserverInterface;

class Categorysaveafter implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $category = $observer->getCategory();  // get category object

        // TODO send back data to easycontent
    }
}