<?php
// Serve a Robotics Toolbox zip from the old (2009-era) download page, /RTB/.
// Invoked as dl-zip.php?file=current/robot-<version>.zip, or dl-zip.php?ver=r<N> for
// the releases in r4, r6, r7, r8. Only those files can be served: until 1 Oct 2026 any
// path or URL was passed straight to readfile().
// Source: github.com/petercorke/writings, wordpress/rtb/dl-zip.php.

if ( ! isset( $_COOKIE['RobotToolboxCookie02'] ) ) {
	print '<HTML><HEAD><TITLE>Robotics Toolbox download page</TITLE></HEAD><BODY>';
	print 'No cookie set; please complete guestbook page';
	print '</BODY></HTML>';
	exit;
}

$file = null;
if ( isset( $_GET['ver'] ) && in_array( $_GET['ver'], array( 'r4', 'r6', 'r7', 'r8' ), true ) ) {
	$file = './' . $_GET['ver'] . '/robot.zip';
}
if ( isset( $_GET['file'] ) && preg_match( '/^current\/robot-[0-9]+\.[0-9]+\.zip$/', $_GET['file'] ) ) {
	$file = $_GET['file'];
}
if ( null === $file || ! is_file( __DIR__ . '/' . $file ) ) {
	http_response_code( 404 );
	print 'Not found';
	exit;
}

header( 'Content-type: application/zip-stream' );
header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
readfile( __DIR__ . '/' . $file );

$fp = fopen( __DIR__ . '/logs/file', 'a' );
flock( $fp, LOCK_EX );
fputs( $fp, 'zip: ' . date( 'j-m-Y H:i', time() ) . ', ' . $_SERVER['REMOTE_ADDR'] . ', ' . $file . "\n" );
fclose( $fp );
