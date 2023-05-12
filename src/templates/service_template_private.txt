<?php

$error_message = "Unauthorized access";
$error_code = 403;
$error_data = [
    "message"=>$error_message,
];
// when use_atuh called it'll check for token if not exists it will set error response and exit
// you don't need to set parameters to use it. if you dont set it'll use default values
$app->use_auth($error_data, $error_code, $error_message);
//
$private_data = [
    "user_id"=>1,
];
$app->set_response($data, 200, 'success');
