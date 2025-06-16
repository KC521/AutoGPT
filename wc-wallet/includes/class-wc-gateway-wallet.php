<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Wallet extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'wallet';
        $this->method_title       = __( 'Wallet Balance', 'wc-wallet' );
        $this->method_description = __( 'Pay using your wallet balance.', 'wc-wallet' );
        $this->has_fields         = false;
        $this->supports           = [ 'products' ];

        $this->title = __( 'Wallet Balance', 'wc-wallet' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function is_available() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        $balance = WC_Custom_Wallet::get_balance();
        return $balance > 0;
    }

    public function process_payment( $order_id ) {
        $order   = wc_get_order( $order_id );
        $total   = (float) $order->get_total();
        $balance = WC_Custom_Wallet::get_balance( $order->get_user_id() );

        if ( $balance < $total ) {
            wc_add_notice( __( 'Insufficient wallet balance.', 'wc-wallet' ), 'error' );
            return false;
        }

        WC_Custom_Wallet::deduct_balance( $total, $order->get_user_id() );
        $order->payment_complete();
        $order->add_order_note( __( 'Paid using wallet balance.', 'wc-wallet' ) );

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
    }
}
