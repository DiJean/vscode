<?php
file_put_contents('/var/www/vm20c2.ru/logs/diag.log', 
    date('[Y-m-d H:i:s]') . print_r($_REQUEST, true),
    FILE_APPEND
);
echo "OK";
