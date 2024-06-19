<?php
if ( ! defined( 'WP_USE_THEMES' ) ) {
	define( 'WP_USE_THEMES', false );
}
if ( ! isset( $_GET['order_id'] ) ) {
	echo 'nothing to display';
	exit;
}

require __DIR__ . '/../vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
use Spipu\Html2Pdf\Html2Pdf;

function format_number( $number ) {
	return number_format( (float) $number, 2, '.', '' );
}

$order    = wc_get_order( $_GET['order_id'] );
$currency = $order->get_currency();
ob_start();
require dirname( __FILE__ ) . '/res/example1.php';
$content = ob_get_clean();

if ( isset( $_GET['view'] ) ) {
	echo $content;
	exit;
}

$html2pdf = new Html2Pdf( 'P', 'A4', 'fr', true, 'UTF-8', array( 5, 5, 5, 8 ) );
$html2pdf->writeHTML( $content );
$html2pdf->output( 'example08.pdf' );
