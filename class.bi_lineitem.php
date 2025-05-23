<?php

require_once __DIR__ . '/vendor/autoload.php';

use Ksfraser\FaBankImport\Container;

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */
class bi_lineitem extends generic_fa_interface_model 
{
    private $viewService;
    private $container;

    public function __construct($trz, $vendor_list = array(), $optypes = array())
    {
        parent::__construct(null, null, null, null, null);
        
        // Initialize container
        $this->container = Container::getInstance();
        
        // Initialize view service with transaction data
        $this->viewService = $this->container->getTransactionViewService($trz);
        
        // Store additional data
        $this->vendor_list = $vendor_list;
        $this->optypes = $optypes;
    }

    // Original displayLeft method refactored to use ViewService
    /* Replaced by src/Ksfraser/FaBankImport/Services/TransactionViewService.php */
    public function display()
    {
        echo $this->viewService->display();
    }

    // ... rest of the necessary methods
}
