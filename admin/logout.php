<?php
// admin/logout.php - Logout Admin

session_start();
session_destroy();

header('Location: login.php');
exit;
