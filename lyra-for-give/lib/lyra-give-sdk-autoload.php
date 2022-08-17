<?php
/**
 * Copyright © Lyra Network and contributors.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network and contributors
 * @license   See COPYING.md for license details.
 */


spl_autoload_register('lyraGiveSdkAutoload', true, true);

function lyraGiveSdkAutoload($className)
{
    if (empty($className) || strpos($className, 'Lyranetwork\\Lyra\\Sdk\\') !== 0) {
        // Not Lyra SDK classes.
        return;
    }

    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    include_once $className . '.php';
}
