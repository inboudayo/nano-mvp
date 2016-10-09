<?php

// run bootstrap if the requesting script is the index page
if(basename($_SERVER['SCRIPT_NAME']) == 'index.php') {
    require('../lib/bootstrap.php');
}
