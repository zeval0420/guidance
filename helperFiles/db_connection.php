<?php
    // db_connection.php

    // Uses the same connection settings as the rest of the reserve-a-lab /
    // scilab pages by including the shared db_connection.php helper.
    // Deployed under reserve-a-lab/fil/guidance it is four levels up;
    // a standalone checkout in htdocs/guidance finds it two levels up.
    $sharedDb = __DIR__ . '/../../../../scilab/helperFiles/db_connection.php';
    if (!is_file($sharedDb)) {
        $sharedDb = __DIR__ . '/../../scilab/helperFiles/db_connection.php';
    }
    require_once $sharedDb;
?>
