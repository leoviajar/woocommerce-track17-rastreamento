<?php
/**
 * Template de e-mail para notificação de código de rastreamento (Texto Simples)
 *
 * Este template pode ser sobrescrito copiando-o para yourtheme/woocommerce/emails/plain/tracking-code-notification.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WC_Track17_Rastreamento/Templates
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Olá %s,', 'wc-track17-rastreamento'), $order->get_billing_first_name()) . "\n\n";

echo __('Temos uma ótima notícia! Seu pedido agora pode ser rastreado.', 'wc-track17-rastreamento') . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo __('INFORMAÇÕES DE RASTREAMENTO', 'wc-track17-rastreamento') . "\n\n";

echo __('Número do Pedido:', 'wc-track17-rastreamento') . ' ' . $order->get_order_number() . "\n";
echo __('Código de Rastreamento:', 'wc-track17-rastreamento') . ' ' . $tracking_code . "\n";
echo __('Data do Pedido:', 'wc-track17-rastreamento') . ' ' . wc_format_datetime($order->get_date_created()) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo __('COMO RASTREAR SEU PEDIDO:', 'wc-track17-rastreamento') . "\n\n";

echo __('Você pode acompanhar o status do seu pedido de duas formas:', 'wc-track17-rastreamento') . "\n\n";

echo "1. " . __('Em nosso site:', 'wc-track17-rastreamento') . "\n";
echo "   " . $order->get_view_order_url() . "\n\n";

echo "2. " . __('No site dos Correios/Transportadora:', 'wc-track17-rastreamento') . "\n";
echo "   https://www.17track.net/pt/track#nums=" . urlencode($tracking_code) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 * @since 2.5.0
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
?>

