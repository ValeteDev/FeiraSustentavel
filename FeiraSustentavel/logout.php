<?php
// logout.php — encerra a sessão e volta ao login
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
