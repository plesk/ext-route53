<?php
// Copyright 1999-2018. Plesk International GmbH.
pm_Loader::registerAutoload();

try {
    $result = pm_ApiCli::call('server_dns', array('--disable-custom-backend'));
} catch (pm_Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
exit(0);
