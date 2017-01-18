<?php
require __DIR__ . '/vendor/autoload.php';       // TODO ok ici ???

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Optimizmeformagento_Passerelle',
    __DIR__
);