<?php
// Aggregate usage statistics for the MATLAB Robotics Toolbox, as JSON: version checks by
// RTB 9.10-10.1 at MATLAB startup, downloads from the old download page, and guestbook
// entries. Totals only, by country; no IP addresses, no organisation names.
// Read by the weekly RVC ecosystem report and snapshotted monthly in the writings repo.
// Source: github.com/petercorke/writings, wordpress/rtb/stats.php.
require __DIR__ . '/telemetry.php';

header( 'Cache-Control: public, max-age=3600' );

// ?csv=monthly or ?csv=daily: the complete totals, as kept on the server (finished months only)
if ( isset( $_GET['csv'] ) && in_array( $_GET['csv'], array( 'monthly', 'daily' ), true ) ) {
	rtb_rollover();
	header( 'Content-Type: text/csv' );
	readfile( RTB_TELEMETRY_DIR . '/' . $_GET['csv'] . '.csv' );
	exit;
}

header( 'Content-Type: application/json' );

$cache = RTB_TELEMETRY_DIR . '/stats-cache.json';
if ( file_exists( $cache ) && time() - filemtime( $cache ) < 3600 ) {
	readfile( $cache );
	exit;
}

ini_set( 'memory_limit', '512M' );
$geo = rtb_geo_refresh();
rtb_rollover();

/**
 * Rows of a totals CSV, plus the current month's raw file totalled the same way.
 *
 * @param string $file   "daily.csv" or "monthly.csv"
 * @param int    $keylen 10 or 7, as for rtb_totals()
 * @return array rows [period, event, country, detail, count, users]
 */
function rtb_rows( string $file, int $keylen ): array {
	$rows = array();
	$path = RTB_TELEMETRY_DIR . "/$file";
	if ( file_exists( $path ) ) {
		foreach ( array_slice( file( $path, FILE_IGNORE_NEW_LINES ), 1 ) as $line ) {
			$rows[] = explode( ',', $line );
		}
	}
	$raw = RTB_TELEMETRY_DIR . '/raw-' . gmdate( 'Y-m' ) . '.tsv';
	return file_exists( $raw ) ? array_merge( $rows, rtb_totals( file( $raw ), $keylen ) ) : $rows;
}

$monthly = rtb_rows( 'monthly.csv', 7 );
$daily   = rtb_rows( 'daily.csv', 10 );
$months  = array( gmdate( 'Y-m' ), gmdate( 'Y-m', strtotime( 'first day of last month' ) ) );
$since   = gmdate( 'Y-m-d', time() - 60 * DAY_SECONDS );

$out = array(
	'generated' => gmdate( 'Y-m-d\TH:i\Z' ),
	'about'     => 'Robotics Toolbox for MATLAB usage. "check": RTB 9.10, 10.0 or 10.1 started in MATLAB (detail = MATLAB release). "download": a zip from petercorke.com/RTB/ (detail = file). users = distinct IP addresses in the period, counted with a monthly key and never stored.',
	'countries' => 'ISO 3166 alpha-2; IP to country data by DB-IP (https://db-ip.com), CC BY 4.0',
	'geo'       => $geo,
);
foreach ( array( 'check', 'download' ) as $event ) {
	$e = array( 'months' => array(), 'daily' => array() );
	foreach ( $monthly as list( $m, $ev, $cc, $detail, $n, $u ) ) {
		if ( $ev !== $event ) {
			continue;
		}
		if ( '*' === $cc && '*' === $detail ) {
			$e['monthly_totals'][ $m ] = array( 'count' => (int) $n, 'users' => (int) $u );
		}
		if ( in_array( $m, $months, true ) ) {
			if ( '*' === $detail && '*' !== $cc ) {
				$e['months'][ $m ]['by_country'][ $cc ] = array( 'count' => (int) $n, 'users' => (int) $u );
			} elseif ( '*' === $cc && '*' !== $detail ) {
				$e['months'][ $m ]['by_detail'][ $detail ] = array( 'count' => (int) $n, 'users' => (int) $u );
			}
		}
	}
	foreach ( $daily as list( $d, $ev, $cc, $detail, $n, $u ) ) {
		if ( $ev === $event && '*' === $cc && '*' === $detail && $d >= $since ) {
			$e['daily'][ $d ] = array( 'count' => (int) $n, 'users' => (int) $u );
		}
	}
	foreach ( $e['months'] as &$mm ) {
		foreach ( array( 'by_country', 'by_detail' ) as $k ) {
			if ( isset( $mm[ $k ] ) ) {
				uasort( $mm[ $k ], fn( $a, $b ) => $b['users'] <=> $a['users'] );
			}
		}
	}
	unset( $mm );
	ksort( $e['daily'] );
	$out[ $event ] = $e;
}

// guestbook: entries per month and by country, last 12 months
$gb      = array( 'months' => array(), 'by_country' => array() );
$from    = gmdate( 'Y-m', strtotime( 'first day of -11 months' ) );
$month   = null;
$handle  = @fopen( __DIR__ . '/logs/guestbook', 'r' );
while ( $handle && ( $line = fgets( $handle ) ) !== false ) {
	if ( preg_match( '/^Date: (\d+)-(\d+)-(\d{4})/', $line, $m ) ) {
		$month = sprintf( '%s-%02d', $m[3], $m[2] );
		if ( $month >= $from ) {
			$gb['months'][ $month ] = ( $gb['months'][ $month ] ?? 0 ) + 1;
		}
	} elseif ( $month >= $from && preg_match( '/^Country: (.+)$/', $line, $m ) ) {
		$c                      = ucwords( strtolower( trim( $m[1] ) ) );
		$gb['by_country'][ $c ] = ( $gb['by_country'][ $c ] ?? 0 ) + 1;
	}
}
if ( $handle ) {
	fclose( $handle );
}
ksort( $gb['months'] );
arsort( $gb['by_country'] );
$out['guestbook'] = $gb;

$json = json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
file_put_contents( $cache, $json, LOCK_EX );
echo $json;
