# WooCommerce Custom Wallet

This simple plugin provides a wallet system for WooCommerce.
Users can deposit funds using PayPal and pay for orders using their balance.

## Features
- Stores user wallet balance in user meta.
- Provides a `[wallet_deposit]` shortcode for creating a balance top-up form that uses PayPal exclusively.
- Adds a `Wallet Balance` payment gateway for paying with stored balance.
- Hides PayPal from the normal checkout page but keeps it on the deposit page.

Install the plugin as usual by copying the `wc-wallet` folder into your WordPress plugins directory and activating it.
