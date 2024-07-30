<?php
/**
 * Woomorrintegration
 *
 * @package WOOMORRINTEGRATION
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function add_order_webhook() {
	if ( class_exists( 'WC_Data_Store' ) ) {
		$data_store = \WC_Data_Store::load( 'webhook' );
		$webhooks   = $data_store->search_webhooks(
			array(
				'status' => 'active',
			)
		);

		$_items     = array_map( 'wc_get_webhook', $webhooks );

		$existing_webhooks = array();

		foreach ( $_items as $_item ) {

			$existing_webhooks [] = array(
				// 'id'           => $_item->get_id(),
				// 'name'         => $_item->get_name(),
				'topic'        => $_item->get_topic(),
				'delivery_url' => $_item->get_delivery_url(),
			);

		}

		$target_url = 'https://appdemo.morr.biz/api/webhook/woo-order-events';
		$topics     = array( 'order.created', 'order.updated' );

		foreach ( $topics as $topic ) {
			$exists = false;
			foreach ( $existing_webhooks as $webhook ) {
				if ( $webhook['delivery_url'] === $target_url && $webhook['topic'] === $topic ) {
					$exists = true;
					break;
				}
			}

			if ( ! $exists ) {
				// Add the webhook if it doesn't exist.
				$webhook = new WC_Webhook();
				$webhook->set_name( 'Morr Order Webhook for ' . $topic );
				$webhook->set_status( 'active' );
				$webhook->set_topic( $topic );
				$webhook->set_delivery_url( $target_url );
				$webhook->save();
			}
		}
	}

}
add_action( 'woocommerce_init', 'add_order_webhook' );
