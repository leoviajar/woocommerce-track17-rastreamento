<?php
/**
 * Classe para gerenciar metadados de pedidos relacionados ao rastreamento
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class WC_Track17_Order_Meta {

    /**
     * Instância única da classe
     */
    private static $instance = null;

    /**
     * Obtém a instância única da classe
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa os hooks
     */
    private function init_hooks() {
        // Adiciona campos de rastreamento na página de edição do pedido (admin)
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'add_tracking_fields_to_order_admin'));
        
        // Salva os campos de rastreamento
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_tracking_fields'));
        
        // Adiciona informações de rastreamento na página do pedido do cliente
        add_action('woocommerce_view_order', array($this, 'display_tracking_info_customer'), 20);
        
        // Adiciona informações de rastreamento nos emails
        add_action('woocommerce_email_order_meta', array($this, 'add_tracking_info_to_emails'), 10, 3);
        
        // Adiciona coluna de rastreamento na lista de pedidos (admin)
        add_filter('manage_edit-shop_order_columns', array($this, 'add_tracking_column_to_orders_list'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'populate_tracking_column'), 10, 2);
        
        // Compatibilidade com HPOS
        add_filter('woocommerce_shop_order_list_table_columns', array($this, 'add_tracking_column_to_orders_list'));
        add_action('woocommerce_shop_order_list_table_custom_column', array($this, 'populate_tracking_column_hpos'), 10, 2);
        
        // Adiciona status de rastreamento à API REST
        add_filter('woocommerce_rest_prepare_shop_order_object', array($this, 'add_tracking_to_rest_api'), 10, 3);
        
        // Hook para registrar automaticamente o rastreamento quando um código for adicionado
        add_action('updated_post_meta', array($this, 'auto_register_tracking'), 10, 4);
        add_action('woocommerce_update_order', array($this, 'auto_register_tracking_hpos'));
    }

    /**
     * Adiciona campos de rastreamento na página de edição do pedido (admin)
     */
    public function add_tracking_fields_to_order_admin($order) {
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $carrier_code = $order->get_meta('_wc_track17_carrier_code');
        $tracking_status = $order->get_meta('_wc_track17_tracking_status');
        $last_update = $order->get_meta('_wc_track17_last_update');
        
        $carriers = WC_Track17_API::get_instance()->get_supported_carriers();
        ?>
        <div class="address">
            <p><strong><?php _e('Informações de Rastreamento', 'wc-track17-rastreamento'); ?></strong></p>
            
            <p class="form-field form-field-wide">
                <label for="_wc_track17_tracking_code"><?php _e('Código de Rastreamento:', 'wc-track17-rastreamento'); ?></label>
                <input type="text" class="short" name="_wc_track17_tracking_code" id="_wc_track17_tracking_code" value="<?php echo esc_attr($tracking_code); ?>" placeholder="<?php _e('Ex: BR123456789CN', 'wc-track17-rastreamento'); ?>" />
            </p>
            
            <p class="form-field form-field-wide">
                <label for="_wc_track17_carrier_code"><?php _e('Transportadora:', 'wc-track17-rastreamento'); ?></label>
                <select name="_wc_track17_carrier_code" id="_wc_track17_carrier_code" class="wc-enhanced-select">
                    <option value=""><?php _e('Selecione uma transportadora', 'wc-track17-rastreamento'); ?></option>
                    <?php foreach ($carriers as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($carrier_code, $code); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <?php if ($tracking_status) : ?>
            <p class="form-field form-field-wide">
                <label><?php _e('Status do Rastreamento:', 'wc-track17-rastreamento'); ?></label>
                <span class="wc-track17-status wc-track17-status-<?php echo esc_attr($tracking_status); ?>">
                    <?php echo esc_html($this->get_status_label($tracking_status)); ?>
                </span>
            </p>
            <?php endif; ?>
            
            <?php if ($last_update) : ?>
            <p class="form-field form-field-wide">
                <label><?php _e('Última Atualização:', 'wc-track17-rastreamento'); ?></label>
                <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update))); ?></span>
            </p>
            <?php endif; ?>
            
            <p class="form-field form-field-wide">
                <button type="button" class="button" id="wc-track17-update-tracking">
                    <?php _e('Atualizar Rastreamento', 'wc-track17-rastreamento'); ?>
                </button>
                <span class="spinner" id="wc-track17-spinner"></span>
            </p>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#wc-track17-update-tracking').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var spinner = $('#wc-track17-spinner');
                var orderId = <?php echo $order->get_id(); ?>;
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_track17_update_single_tracking',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('wc_track17_update_tracking'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Rastreamento atualizado com sucesso!', 'wc-track17-rastreamento'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Erro ao atualizar rastreamento: ', 'wc-track17-rastreamento'); ?>' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Erro na requisição.', 'wc-track17-rastreamento'); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Salva os campos de rastreamento
     */
    public function save_tracking_fields($order_id) {
        // Pega o objeto do pedido apenas uma vez no início.
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
    
        $data_changed = false;
    
        // Processa o código de rastreamento, se foi enviado.
        if (isset($_POST['_wc_track17_tracking_code'])) {
            $tracking_code = sanitize_text_field($_POST['_wc_track17_tracking_code']);
            $old_tracking_code = $order->get_meta('_wc_track17_tracking_code');
    
            // Apenas atualiza se o valor for diferente, para otimizar.
            if ($old_tracking_code !== $tracking_code) {
                $order->update_meta_data('_wc_track17_tracking_code', $tracking_code);
                
                // Limpa dados antigos relacionados ao status se o código mudou.
                $order->delete_meta_data('_wc_track17_tracking_status');
                $order->delete_meta_data('_wc_track17_last_update');
                $order->delete_meta_data('_wc_track17_registered');
                
                $data_changed = true;
            }
        }
        
        // Processa a transportadora, se foi enviada.
        if (isset($_POST['_wc_track17_carrier_code'])) {
            $carrier_code = sanitize_text_field($_POST['_wc_track17_carrier_code']);
            
            // Apenas atualiza se o valor for diferente.
            if ($order->get_meta('_wc_track17_carrier_code') !== $carrier_code) {
                $order->update_meta_data('_wc_track17_carrier_code', $carrier_code);
                $data_changed = true;
            }
        }
    
        // Salva o pedido UMA VEZ no final, mas somente se algo mudou.
        if ($data_changed) {
            $order->save();
        }
    }


    /**
     * Exibe informações de rastreamento na página do pedido do cliente
     */
    public function display_tracking_info_customer($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $tracking_status = $order->get_meta('_wc_track17_tracking_status');
        $last_update = $order->get_meta('_wc_track17_last_update');
        
        if (empty($tracking_code)) {
            return;
        }
        ?>
        <section class="woocommerce-order-tracking">
            <h2><?php _e('Rastreamento do Pedido', 'wc-track17-rastreamento'); ?></h2>
            
            <div class="wc-track17-tracking-info">
                <p>
                    <strong><?php _e('Código de Rastreamento:', 'wc-track17-rastreamento'); ?></strong>
                    <span class="tracking-code"><?php echo esc_html($tracking_code); ?></span>
                </p>
                
                <?php if ($tracking_status) : ?>
                <p>
                    <strong><?php _e('Status:', 'wc-track17-rastreamento'); ?></strong>
                    <span class="wc-track17-status wc-track17-status-<?php echo esc_attr($tracking_status); ?>">
                        <?php echo esc_html($this->get_status_label($tracking_status)); ?>
                    </span>
                </p>
                <?php endif; ?>
                
                <?php if ($last_update) : ?>
                <p>
                    <strong><?php _e('Última Atualização:', 'wc-track17-rastreamento'); ?></strong>
                    <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update))); ?></span>
                </p>
                <?php endif; ?>
                
                <p>
                    <a href="https://www.17track.net/en/track#nums=<?php echo urlencode($tracking_code); ?>" target="_blank" class="button">
                        <?php _e('Rastrear pedido', 'wc-track17-rastreamento'); ?>
                    </a>
                </p>
            </div>
        </section>
        <?php
    }

    /**
     * Adiciona informações de rastreamento nos emails
     */
    public function add_tracking_info_to_emails($order, $sent_to_admin, $plain_text) {
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $tracking_status = $order->get_meta('_wc_track17_tracking_status');
        
        if (empty($tracking_code)) {
            return;
        }
        
        if ($plain_text) {
            echo "\n" . __('Código de Rastreamento:', 'wc-track17-rastreamento') . ' ' . $tracking_code . "\n";
            if ($tracking_status) {
                echo __('Status:', 'wc-track17-rastreamento') . ' ' . $this->get_status_label($tracking_status) . "\n";
            }
            echo __('Rastrear em:', 'wc-track17-rastreamento') . ' https://www.17track.net/en/track#nums=' . urlencode($tracking_code) . "\n";
        } else {
            ?>
            <div style="margin-bottom: 40px;">
                <h2><?php _e('Informações de Rastreamento', 'wc-track17-rastreamento'); ?></h2>
                <p>
                    <strong><?php _e('Código de Rastreamento:', 'wc-track17-rastreamento'); ?></strong> <?php echo esc_html($tracking_code); ?>
                </p>
                <?php if ($tracking_status) : ?>
                <p>
                    <strong><?php _e('Status:', 'wc-track17-rastreamento'); ?></strong> <?php echo esc_html($this->get_status_label($tracking_status)); ?>
                </p>
                <?php endif; ?>
                <p>
                    <a href="https://www.17track.net/en/track#nums=<?php echo urlencode($tracking_code); ?>" target="_blank">
                        <?php _e('Rastrear pedido', 'wc-track17-rastreamento'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Adiciona coluna de rastreamento na lista de pedidos
     */
    public function add_tracking_column_to_orders_list($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            // Adiciona a coluna de rastreamento após a coluna de status
            if ($key === 'order_status') {
                $new_columns['tracking_status'] = __('Rastreamento', 'wc-track17-rastreamento');
            }
        }
        
        return $new_columns;
    }

    /**
     * Popula a coluna de rastreamento na lista de pedidos (posts)
     */
    public function populate_tracking_column($column, $order_id) {
        if ($column === 'tracking_status') {
            $order = wc_get_order($order_id);
            $tracking_code = $order->get_meta('_wc_track17_tracking_code');
            $tracking_status = $order->get_meta('_wc_track17_tracking_status');
            
            if ($tracking_code) {
                echo '<span class="wc-track17-status wc-track17-status-' . esc_attr($tracking_status) . '">';
                echo esc_html($this->get_status_label($tracking_status));
                echo '</span>';
            } else {
                echo '<span class="wc-track17-status wc-track17-status-sem-rastreio">';
                echo __('Sem rastreio', 'wc-track17-rastreamento');
                echo '</span>';
            }
        }
    }

    /**
     * Popula a coluna de rastreamento na lista de pedidos (HPOS)
     */
    public function populate_tracking_column_hpos($column, $order) {
        if ($column === 'tracking_status') {
            $tracking_code = $order->get_meta('_wc_track17_tracking_code');
            $tracking_status = $order->get_meta('_wc_track17_tracking_status');
            
            if ($tracking_code) {
                echo '<span class="wc-track17-status wc-track17-status-' . esc_attr($tracking_status) . '">';
                echo esc_html($this->get_status_label($tracking_status));
                echo '</span>';
            } else {
                echo '<span class="wc-track17-status wc-track17-status-sem-rastreio">';
                echo __('Sem rastreio', 'wc-track17-rastreamento');
                echo '</span>';
            }
        }
    }

    /**
     * Adiciona informações de rastreamento à API REST
     */
    public function add_tracking_to_rest_api($response, $order, $request) {
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $tracking_status = $order->get_meta('_wc_track17_tracking_status');
        $carrier_code = $order->get_meta('_wc_track17_carrier_code');
        $last_update = $order->get_meta('_wc_track17_last_update');
        
        $response->data['tracking_info'] = array(
            'tracking_code' => $tracking_code,
            'tracking_status' => $tracking_status,
            'tracking_status_label' => $this->get_status_label($tracking_status),
            'carrier_code' => $carrier_code,
            'last_update' => $last_update
        );
        
        return $response;
    }

    /**
     * Registra automaticamente o rastreamento quando um código for adicionado (posts)
     */
    public function auto_register_tracking($meta_id, $object_id, $meta_key, $meta_value) {
        if ($meta_key === '_wc_track17_tracking_code' && !empty($meta_value)) {
            $auto_register = get_option('track17_auto_register', 'yes');
            
            if ($auto_register === 'yes') {
                $this->register_tracking_for_order($object_id);
            }
        }
    }

    /**
     * Registra automaticamente o rastreamento quando um código for adicionado (HPOS)
     */
    public function auto_register_tracking_hpos($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $is_registered = $order->get_meta('_wc_track17_registered');
        
        if (!empty($tracking_code) && !$is_registered) {
            $auto_register = get_option('track17_auto_register', 'yes');
            
            if ($auto_register === 'yes') {
                $this->register_tracking_for_order($order_id);
            }
        }
    }

    /**
     * Registra o rastreamento para um pedido específico
     */
    public function register_tracking_for_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $carrier_code = $order->get_meta('_wc_track17_carrier_code');
        
        if (empty($tracking_code)) {
            return false;
        }
        
        $api = WC_Track17_API::get_instance();
        $result = $api->register_tracking($tracking_code, $carrier_code);
        
        if (!is_wp_error($result) && $result['success']) {
            $order->update_meta_data('_wc_track17_registered', 'yes');
            $order->update_meta_data('_wc_track17_last_update', current_time('mysql'));
            $order->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Obtém o rótulo do status de rastreamento
     */
    public function get_status_label($status) {
        $labels = array(
            'sem_informacoes' => __('Sem informações', 'wc-track17-rastreamento'),
            'postado' => __('Postado', 'wc-track17-rastreamento'),
            'em_transito' => __('Em trânsito', 'wc-track17-rastreamento'),
            'entregue' => __('Entregue', 'wc-track17-rastreamento'),
            'taxado' => __('Taxado', 'wc-track17-rastreamento'),
            'devolvido' => __('Devolvido', 'wc-track17-rastreamento'),
            'falha_entrega' => __('Falha na entrega', 'wc-track17-rastreamento'),
            'excecao' => __('Exceção', 'wc-track17-rastreamento'),
            'aguardando_retirada' => __('Aguardando retirada', 'wc-track17-rastreamento'),
        );
        
        return isset($labels[$status]) ? $labels[$status] : __('Desconhecido', 'wc-track17-rastreamento');
    }
}

