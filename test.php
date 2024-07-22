<?php
ini_set('session.gc_maxlifetime', 0);

$cookie_params = session_get_cookie_params();

session_set_cookie_params(
    0,
    $cookie_params['path'],
    $cookie_params['domain'],
    $cookie_params['secure'],
    $cookie_params['httponly']
);

session_start();

if (!isset($_SESSION['test_session'])) {
    $_SESSION['test_session'] = time(); // Store current timestamp in session
    echo "Session created. Refresh this page to check if session persists.";
} else {
    $session_age = time() - $_SESSION['test_session'];
    echo "Session exists. Session age: $session_age seconds.";
}
?>
