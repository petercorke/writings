<?php
// The release number that RTB 9.10, 10.0 and 10.1 compare with their own at startup
// (startup_rtb.m prints "Release <this> now available" if they differ). Each request is
// counted, by country and MATLAB release, in telemetry.php.
// Source: github.com/petercorke/writings, wordpress/rtb/currentversion.php.
require __DIR__ . '/telemetry.php';
header( 'Content-Type: text/plain' );
header( 'Cache-Control: no-store' ); // every check must reach PHP to be counted
echo '9.10';
rtb_record( 'check', rtb_matlab_release( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
