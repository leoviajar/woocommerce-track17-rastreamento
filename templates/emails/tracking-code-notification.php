<?php
/**
 * Template de e-mail para notificação de código de rastreamento (HTML)
 *
 * Este template pode ser sobrescrito copiando-o para yourtheme/woocommerce/emails/tracking-code-notification.php.
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

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(__('Olá %s,', 'wc-track17-rastreamento'), $order->get_billing_first_name()); ?></p>

<p><?php _e('Temos uma ótima notícia! Seu pedido agora pode ser rastreado.', 'wc-track17-rastreamento'); ?></p>

<div style="margin-bottom: 40px;">
    <h2><?php _e('Informações de Rastreamento', 'wc-track17-rastreamento'); ?></h2>
    
    <table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
        <tbody>
            <tr>
                <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e('Número do Pedido:', 'wc-track17-rastreamento'); ?></th>
                <td style="text-align:left; border: 1px solid #eee;"><?php echo $order->get_order_number(); ?></td>
            </tr>
            <tr>
                <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e('Código de Rastreamento:', 'wc-track17-rastreamento'); ?></th>
                <td style="text-align:left; border: 1px solid #eee;"><strong><?php echo esc_html($tracking_code); ?></strong></td>
            </tr>
            <tr>
                <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e('Data do Pedido:', 'wc-track17-rastreamento'); ?></th>
                <td style="text-align:left; border: 1px solid #eee;"><?php echo wc_format_datetime($order->get_date_created()); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div style="margin-bottom: 40px;">
    <h3><?php _e('Como rastrear seu pedido:', 'wc-track17-rastreamento'); ?></h3>
    <p><?php _e('Você pode acompanhar o status do seu pedido de duas formas:', 'wc-track17-rastreamento'); ?></p>
    
    <ol>
        <li>
            <strong><?php _e('Na sua conta:', 'wc-track17-rastreamento'); ?></strong><br>
            <a href="<?php echo esc_url($order->get_view_order_url()); ?>" style="color: #96588a; font-weight: normal; text-decoration: underline;">
                <?php _e('Clique aqui para ver os detalhes do seu pedido', 'wc-track17-rastreamento'); ?>
            </a>
        </li>
        <li style="margin-top: 10px;">
            <strong><?php _e('Pelo nosso site:', 'wc-track17-rastreamento'); ?></strong><br>
            <a href="<?php echo esc_url(home_url('/rastreio?codigo=' . urlencode($tracking_code))); ?>" target="_blank" style="color: #96588a; font-weight: normal; text-decoration: underline;">
                <?php _e("Rastrear em nosso site", "wc-track17-rastreamento"); ?>
            </a>
        </li>
    </ol>
</div>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 * @since 2.5.0
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?>

