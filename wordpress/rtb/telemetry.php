<?php
/**
 * Usage statistics for the MATLAB Robotics Toolbox, kept as aggregates by country.
 *
 * Two events are recorded:
 *
 * - "check": RTB 9.10, 10.0 and 10.1 fetch /RTB/currentversion.php each time MATLAB
 *   runs startup_rtb, with the MATLAB release in the User-Agent;
 * - "download": a zip served by the old download page, /RTB/dl-zip.php.
 *
 * No IP address is stored. Each event is written to the current month's raw file as
 * time, event, country, detail (MATLAB release or file name) and a keyed hash of the IP,
 * so that distinct users can be counted. The key is random and exists only for that
 * month: when the month is over, its raw file is reduced to totals (daily.csv and
 * monthly.csv) and both the raw file and the key are deleted, so nothing that could link
 * one user's requests survives the month.
 *
 * Countries come from DB-IP's "IP to Country Lite" database (CC BY 4.0,
 * https://db-ip.com), converted to two sorted binary tables, one for IPv4 and one for IPv6.
 *
 * Everything lives in ~/rtb-telemetry/, outside the web root.
 * Source: github.com/petercorke/writings, wordpress/rtb/.
 */

if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && realpath( $_SERVER['SCRIPT_FILENAME'] ) === __FILE__ ) {
	http_response_code( 404 ); // a library, not a page
	exit;
}

const DAY_SECONDS       = 86400;
const RTB_TELEMETRY_DIR = '/home/u41-iqh6n5pf7kxl/rtb-telemetry';
const RTB_GEO_URL       = 'https://download.db-ip.com/free/dbip-country-lite-%s.csv.gz';

/**
 * The country of an IP address.
 *
 * @param string $ip IPv4 or IPv6 address
 * @return string ISO 3166 alpha-2 code, "ZZ" if unknown
 */
function rtb_country( string $ip ): string {
	$bin = @inet_pton( $ip );
	if ( false === $bin ) {
		return 'ZZ';
	}
	$v4   = 4 === strlen( $bin );
	$size = $v4 ? 4 : 16;
	$rec  = $size + 2;
	$fp   = @fopen( RTB_TELEMETRY_DIR . '/geo/' . ( $v4 ? 'ipv4.bin' : 'ipv6.bin' ), 'rb' );
	if ( ! $fp ) {
		return 'ZZ';
	}
	// binary search for the last range starting at or below $bin
	$lo = 0;
	$hi = intdiv( fstat( $fp )['size'], $rec ) - 1;
	$cc = 'ZZ';
	while ( $lo <= $hi ) {
		$mid = intdiv( $lo + $hi, 2 );
		fseek( $fp, $mid * $rec );
		$r = fread( $fp, $rec );
		if ( strcmp( substr( $r, 0, $size ), $bin ) <= 0 ) {
			$cc = substr( $r, $size, 2 );
			$lo = $mid + 1;
		} else {
			$hi = $mid - 1;
		}
	}
	fclose( $fp );
	return $cc;
}

/**
 * The secret key for this month's IP hashes, created on first use.
 *
 * @param string $month "YYYY-MM"
 * @return string the key
 */
function rtb_month_key( string $month ): string {
	$path = RTB_TELEMETRY_DIR . "/key-$month";
	$key  = @file_get_contents( $path );
	if ( ! $key ) {
		$key = bin2hex( random_bytes( 32 ) );
		file_put_contents( $path, $key, LOCK_EX );
		chmod( $path, 0600 );
		$key = file_get_contents( $path ); // another request may have won the race
	}
	return $key;
}

/**
 * Record one event.
 *
 * @param string $event  "check" or "download"
 * @param string $detail MATLAB release (e.g. "R2026a") or file name
 */
function rtb_record( string $event, string $detail ): void {
	if ( ! is_dir( RTB_TELEMETRY_DIR ) ) {
		return;
	}
	rtb_rollover();
	$month = gmdate( 'Y-m' );
	$ip    = $_SERVER['REMOTE_ADDR'] ?? '';
	$user  = substr( hash_hmac( 'sha256', $ip, rtb_month_key( $month ) ), 0, 16 );
	$line  = implode( "\t", array( gmdate( 'Y-m-d\TH:i:s\Z' ), $event, rtb_country( $ip ), $detail, $user ) ) . "\n";
	file_put_contents( RTB_TELEMETRY_DIR . "/raw-$month.tsv", $line, FILE_APPEND | LOCK_EX );
}

/**
 * The MATLAB release named in a User-Agent, e.g. "R2026a", or "unknown".
 *
 * @param string $ua User-Agent header
 * @return string
 */
function rtb_matlab_release( string $ua ): string {
	return preg_match( '/R(19|20)[0-9]{2}[ab]/', $ua, $m ) ? $m[0] : 'unknown';
}

/**
 * Totals from raw event lines.
 *
 * Rows are [period, event, country, detail, count, users]; country and detail are "*" in
 * the rows that total over them, so every distinct-user count is exact.
 *
 * @param iterable $lines  raw lines (tab separated)
 * @param int      $keylen 10 to total by day ("YYYY-MM-DD"), 7 by month
 * @return array rows, sorted
 */
function rtb_totals( iterable $lines, int $keylen ): array {
	$count = array();
	$users = array();
	foreach ( $lines as $line ) {
		$f = explode( "\t", rtrim( $line, "\n" ) );
		if ( count( $f ) < 5 ) {
			continue;
		}
		list( $t, $event, $cc, $detail, $user ) = $f;
		$period                                = substr( $t, 0, $keylen );
		foreach ( array( array( $cc, $detail ), array( $cc, '*' ), array( '*', $detail ), array( '*', '*' ) ) as list( $c, $d ) ) {
			$k                    = "$period\t$event\t$c\t$d";
			$count[ $k ]          = ( $count[ $k ] ?? 0 ) + 1;
			$users[ $k ][ $user ] = true;
		}
	}
	$rows = array();
	foreach ( $count as $k => $n ) {
		$rows[] = array_merge( explode( "\t", $k ), array( $n, count( $users[ $k ] ) ) );
	}
	sort( $rows );
	return $rows;
}

/**
 * Append rows to a CSV file, writing the header if the file is new.
 *
 * @param string $file file name in RTB_TELEMETRY_DIR
 * @param string $head header line
 * @param array  $rows rows from rtb_totals()
 */
function rtb_append_csv( string $file, string $head, array $rows ): void {
	$path = RTB_TELEMETRY_DIR . "/$file";
	$out  = file_exists( $path ) ? '' : "$head\n";
	foreach ( $rows as $r ) {
		$out .= implode( ',', $r ) . "\n";
	}
	file_put_contents( $path, $out, FILE_APPEND | LOCK_EX );
}

/**
 * Reduce any finished month's raw file to totals, then delete it and its key.
 */
function rtb_rollover(): void {
	$current = gmdate( 'Y-m' );
	$done    = array();
	foreach ( glob( RTB_TELEMETRY_DIR . '/raw-*.tsv' ) as $path ) {
		$month = substr( basename( $path ), 4, 7 );
		if ( $month < $current ) {
			$done[ $month ] = $path;
		}
	}
	if ( ! $done ) {
		return;
	}
	$lock = fopen( RTB_TELEMETRY_DIR . '/rollover.lock', 'c' );
	if ( ! flock( $lock, LOCK_EX | LOCK_NB ) ) {
		return; // another request is doing it
	}
	ksort( $done );
	foreach ( $done as $month => $path ) {
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$lines = file( $path );
		rtb_append_csv( 'daily.csv', 'date,event,country,detail,count,users', rtb_totals( $lines, 10 ) );
		rtb_append_csv( 'monthly.csv', 'month,event,country,detail,count,users', rtb_totals( $lines, 7 ) );
		unlink( $path );
		@unlink( RTB_TELEMETRY_DIR . "/key-$month" );
	}
	flock( $lock, LOCK_UN );
	fclose( $lock );
}

/**
 * Rebuild the country tables from DB-IP's current monthly file if they are over a month old.
 *
 * @return string what happened, for the caller to report
 */
function rtb_geo_refresh(): string {
	$dir   = RTB_TELEMETRY_DIR . '/geo';
	$stamp = "$dir/ipv4.bin";
	if ( file_exists( $stamp ) && time() - filemtime( $stamp ) < 32 * DAY_SECONDS ) {
		return 'geo: current';
	}
	@mkdir( $dir, 0700, true );
	$gz = "$dir/dbip-country-lite.csv.gz";
	foreach ( array( gmdate( 'Y-m' ), gmdate( 'Y-m', strtotime( 'first day of last month' ) ) ) as $month ) {
		$data = @file_get_contents( sprintf( RTB_GEO_URL, $month ) );
		if ( $data && strlen( $data ) > 1000000 ) {
			file_put_contents( $gz, $data );
			break;
		}
	}
	if ( ! file_exists( $gz ) ) {
		return 'geo: download failed';
	}
	$v4 = array();
	$v6 = array();
	$in = gzopen( $gz, 'rb' );
	while ( ( $line = gzgets( $in ) ) !== false ) {
		list( $start, , $cc ) = explode( ',', trim( $line ) );
		$bin                  = @inet_pton( $start );
		if ( false === $bin || 2 !== strlen( $cc ) ) {
			continue;
		}
		if ( 4 === strlen( $bin ) ) {
			$v4[] = $bin . $cc;
		} else {
			$v6[] = $bin . $cc;
		}
	}
	gzclose( $in );
	sort( $v4, SORT_STRING );
	sort( $v6, SORT_STRING );
	file_put_contents( "$dir/ipv6.bin.tmp", implode( '', $v6 ) );
	file_put_contents( "$dir/ipv4.bin.tmp", implode( '', $v4 ) );
	rename( "$dir/ipv6.bin.tmp", "$dir/ipv6.bin" );
	rename( "$dir/ipv4.bin.tmp", "$dir/ipv4.bin" );
	unlink( $gz );
	return sprintf( 'geo: rebuilt, %d IPv4 and %d IPv6 ranges', count( $v4 ), count( $v6 ) );
}

