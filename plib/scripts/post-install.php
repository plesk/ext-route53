<?php
// Copyright 1999-2014. Parallels IP Holdings GmbH. All Rights Reserved.
pm_Loader::registerAutoload();
pm_Context::init('route53');

try {
    $script = PRODUCT_ROOT . '/bin/extension --exec ' . pm_Context::getModuleId() . ' route53.php';
    $result = pm_ApiCli::call('server_dns', array('--enable-custom-backend', $script));
} catch (pm_Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
exit(0);
