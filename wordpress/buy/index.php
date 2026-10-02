<?php
/**
 * "Buy" links that send each reader to the best place to buy the book: their Amazon store
 * where Peter is an Associate, otherwise the publisher.
 *
 *   https://petercorke.com/buy/rvc3p              one of Peter's books, store chosen by country
 *   https://petercorke.com/buy/rvc3p?store=au      a particular store where Peter is an Associate
 *                                                  (au, us, or a store domain such as co.uk)
 *   https://petercorke.com/buy/isbn/0262201623     any book by its print ISBN-10 ("Books I like")
 *
 * Readers in Australia go to amazon.com.au and readers in the USA to amazon.com, with
 * the Associates tag for that store. Readers in a country with its own Amazon store,
 * where Peter is not an Associate, go to the book's page at Springer (the publisher has
 * no commission programme, so that costs nothing and ships worldwide); everyone else
 * goes to amazon.com with the US tag, since that is where they would buy. Each click is
 * counted by country, book and destination (telemetry.php, no IP addresses kept).
 *
 * Other authors' books (/buy/isbn/...) have no publisher page to fall back on, so readers
 * in a country with its own Amazon store go there, untagged.
 *
 * Amazon's Associates rules forbid hiding which site a click came from. This is a plain
 * redirect, so the browser still tells Amazon the page the reader clicked on (on
 * petercorke.com), which must be listed as a site in each Associates account. Affiliate
 * links must not be used in email, PDFs or print.
 *
 * Source: github.com/petercorke/writings, wordpress/buy/.
 */

require dirname( __DIR__ ) . '/RTB/telemetry.php';

// book => print ISBN-10, which is also its Amazon ID in every store
const BOOKS = array(
	'rvc3p' => '3031064682', // RVC 3rd edition, Python (2023)
	'rvc3m' => '3031072618', // RVC 3rd edition, MATLAB (2023)
	'rvc2'  => '3319544128', // RVC 2nd edition (2017)
	'rvc1'  => '3642201431', // RVC 1st edition (2011)
);

// book => Springer DOI (the publisher's page: print and ebook)
const SPRINGER = array(
	'rvc3p' => '10.1007/978-3-031-06469-2',
	'rvc3m' => '10.1007/978-3-031-07262-8',
	'rvc2'  => '10.1007/978-3-319-54413-7',
	'rvc1'  => '10.1007/978-3-642-20144-8',
);

// stores where Peter is an Associate: store => Associates tag
const TAGS = array(
	'com'    => 'petercorke05-20',
	'com.au' => 'petercorke-22',
);

// country (ISO 3166) => its Amazon store, for countries that have one
const STORES = array(
	'US' => 'com', 'AU' => 'com.au', 'NZ' => 'com.au', 'CA' => 'ca', 'MX' => 'com.mx',
	'BR' => 'com.br', 'GB' => 'co.uk', 'IE' => 'co.uk', 'DE' => 'de', 'AT' => 'de',
	'CH' => 'de', 'FR' => 'fr', 'BE' => 'com.be', 'NL' => 'nl', 'IT' => 'it', 'ES' => 'es',
	'SE' => 'se', 'PL' => 'pl', 'TR' => 'com.tr', 'AE' => 'ae', 'SA' => 'sa', 'EG' => 'eg',
	'IN' => 'in', 'JP' => 'co.jp', 'SG' => 'sg',
);

header( 'Cache-Control: no-store, private' ); // the answer depends on the reader's country
header( 'X-Robots-Tag: noindex' );

$book = strtolower( trim( $_GET['book'] ?? '', '/' ) );
$isbn = strtoupper( $_GET['isbn'] ?? '' );
if ( 'isbn' === $book && preg_match( '/^[0-9]{9}[0-9X]$/', $isbn ) ) {
	$country = rtb_country( $_SERVER['REMOTE_ADDR'] ?? '' );
	$store   = STORES[ $country ] ?? 'com';
	$url     = 'https://www.amazon.' . $store . '/dp/' . $isbn . ( isset( TAGS[ $store ] ) ? '?tag=' . TAGS[ $store ] : '' );
	rtb_record( 'buy', "isbn:$isbn/$store" );
	header( 'Location: ' . $url, true, 302 );
	exit;
}
if ( ! isset( BOOKS[ $book ] ) ) {
	header( 'Location: https://petercorke.com/books/', true, 302 );
	exit;
}

$country = rtb_country( $_SERVER['REMOTE_ADDR'] ?? '' );
$want    = array( 'au' => 'com.au', 'us' => 'com' )[ strtolower( $_GET['store'] ?? '' ) ] ?? strtolower( $_GET['store'] ?? '' );
if ( isset( TAGS[ $want ] ) ) {
	$store = $want; // ?store=au, ?store=us, or any store we have a tag for (e.g. co.uk): for testing
} else {
	$store = STORES[ $country ] ?? 'com'; // no local store: amazon.com
}

if ( isset( TAGS[ $store ] ) ) {
	$url = 'https://www.amazon.' . $store . '/dp/' . BOOKS[ $book ] . '?tag=' . TAGS[ $store ];
} else {
	$store = 'springer'; // a local Amazon store that earns nothing: the publisher instead
	$url   = 'https://doi.org/' . SPRINGER[ $book ];
}

rtb_record( 'buy', "$book/$store" );
header( 'Location: ' . $url, true, 302 );
