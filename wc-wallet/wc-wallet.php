<?php
/*
Plugin Name: WooCommerce Custom Wallet
Description: Adds a simple wallet system allowing users to deposit via PayPal and pay orders using balance.
Version: 1.0.0
Author: AutoGPT
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_Custom_Wallet {
    const META_KEY = 'wc_wallet_balance';

    public static function init() {
        add_action( 'plugins_loaded', [ __CLASS__, 'includes' ] );
        add_filter( 'woocommerce_payment_gateways', [ __CLASS__, 'add_gateway' ] );
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'handle_topup_order' ] );
        add_filter( 'woocommerce_available_payment_gateways', [ __CLASS__, 'filter_gateways' ] );
        add_shortcode( 'wallet_deposit', [ __CLASS__, 'deposit_shortcode' ] );
    }

    public static function includes() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-gateway-wallet.php';
    }

    public static function add_gateway( $gateways ) {
        $gateways[] = 'WC_Gateway_Wallet';
        return $gateways;
    }

    public static function get_balance( $user_id = 0 ) {
        $user_id = $user_id ? $user_id : get_current_user_id();
        return (float) get_user_meta( $user_id, self::META_KEY, true );
    }

    public static function add_balance( $amount, $user_id = 0 ) {
        $user_id = $user_id ? $user_id : get_current_user_id();
        $balance = self::get_balance( $user_id );
        $balance += $amount;
        update_user_meta( $user_id, self::META_KEY, $balance );
    }

    public static function deduct_balance( $amount, $user_id = 0 ) {
        $user_id = $user_id ? $user_id : get_current_user_id();
        $balance = self::get_balance( $user_id );
        $balance -= $amount;
        if ( $balance < 0 ) {
            $balance = 0;
        }
        update_user_meta( $user_id, self::META_KEY, $balance );
    }

    // Handle orders created from the deposit page
    public static function handle_topup_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order && $order->get_meta( '_wallet_topup' ) ) {
            $amount = (float) $order->get_total();
            self::add_balance( $amount, $order->get_user_id() );
        }
    }

    // Hide PayPal on checkout, unless on deposit page
    public static function filter_gateways( $gateways ) {
        if ( is_checkout() && ! self::is_deposit_page() ) {
            unset( $gateways['paypal'] );
        }
        if ( self::is_deposit_page() ) {
            foreach ( $gateways as $id => $gateway ) {
                if ( 'paypal' !== $id ) {
                    unset( $gateways[ $id ] );
                }
            }
        }
        return $gateways;
    }

    public static function is_deposit_page() {
        return is_page() && has_shortcode( get_post()->post_content, 'wallet_deposit' );
    }

    public static function deposit_shortcode() {
        if ( ! is_user_logged_in() ) {
            return __( 'You need to log in to deposit funds.', 'wc-wallet' );
        }
        ob_start();
        ?>
        <form method="post">
            <p>
                <label><?php _e( 'Deposit amount', 'wc-wallet' ); ?></label>
                <input type="number" name="wallet_amount" min="1" step="0.01" required />
            </p>
            <button type="submit" name="wallet_deposit_submit"><?php _e( 'Add to Balance via PayPal', 'wc-wallet' ); ?></button>
        </form>
        <?php
        if ( isset( $_POST['wallet_deposit_submit'] ) && isset( $_POST['wallet_amount'] ) ) {
            $amount = floatval( $_POST['wallet_amount'] );
            $order  = wc_create_order();
            $order->add_product( wc_get_product( 0 ), 1 );
            $order->set_total( $amount );
            $order->set_customer_id( get_current_user_id() );
            $order->update_meta_data( '_wallet_topup', true );
            $order->save();
            wc_add_notice( __( 'Top-up order created. Please complete payment.', 'wc-wallet' ) );
            return wc_get_checkout_url() . '?order_id=' . $order->get_id();
        }
        return ob_get_clean();
    }
}
WC_Custom_Wallet::init();
