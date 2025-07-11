<?php
/**
 * Classe principal do admin
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class WC_Track17_Admin {

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
        // Enfileira scripts e estilos do admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX para atualizar rastreamento individual
        add_action('wp_ajax_wc_track17_update_single_tracking', array($this, 'update_single_tracking_ajax'));
        
        // AJAX para atualizar todos os rastreamentos
        add_action('wp_ajax_wc_track17_update_all_tracking', array($this, 'update_all_tracking_ajax'));
        
        // Adiciona avisos administrativos
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Adiciona meta box personalizada na página do pedido
        add_action('add_meta_boxes', array($this, 'add_tracking_meta_box'));
        
        // Cron job para atualização automática
        add_action('wc_track17_update_tracking_cron', array($this, 'update_all_tracking_cron'));
        
        // Agenda o cron job se não estiver agendado
        if (!wp_next_scheduled('wc_track17_update_tracking_cron')) {
            $frequency = get_option('track17_update_frequency', 6);
            wp_schedule_event(time(), 'wc_track17_' . $frequency . 'hours', 'wc_track17_update_tracking_cron');
        }
        
        // Registra intervalos de cron personalizados
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
    }

    /**
     * Enfileira scripts e estilos do admin
     */
    public function enqueue_admin_assets($hook) {
        // Carrega apenas nas páginas relevantes
        if (in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            global $post_type;
            if ($post_type === 'shop_order') {
                wp_enqueue_style(
                    'wc-track17-admin',
                    WC_TRACK17_PLUGIN_URL . 'assets/css/admin.css',
                    array(),
                    WC_TRACK17_VERSION
                );
                
                wp_enqueue_script(
                    'wc-track17-admin',
                    WC_TRACK17_PLUGIN_URL . 'assets/js/admin.js',
                    array('jquery'),
                    WC_TRACK17_VERSION,
                    true
                );
                
                wp_localize_script('wc-track17-admin', 'wc_track17_admin', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wc_track17_admin'),
                    'strings' => array(
                        'updating' => __('Atualizando...', 'wc-track17-rastreamento'),
                        'update_success' => __('Rastreamento atualizado com sucesso!', 'wc-track17-rastreamento'),
                        'update_error' => __('Erro ao atualizar rastreamento:', 'wc-track17-rastreamento'),
                        'confirm_update_all' => __('Tem certeza que deseja atualizar todos os rastreamentos? Esta operação pode demorar alguns minutos.', 'wc-track17-rastreamento')
                    )
                ));
            }
        }
        
        // Carrega na página de configurações
        if ($hook === 'woocommerce_page_wc-track17-settings') {
            wp_enqueue_style(
                'wc-track17-settings',
                WC_TRACK17_PLUGIN_URL . 'assets/css/settings.css',
                array(),
                WC_TRACK17_VERSION
            );
        }
        
        // Carrega na página do dashboard
        if ($hook === 'toplevel_page_wc-track17-dashboard') {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1',
                true
            );
            
            wp_enqueue_style(
                'wc-track17-dashboard',
                WC_TRACK17_PLUGIN_URL . 'assets/css/dashboard.css',
                array(),
                WC_TRACK17_VERSION
            );
            
            wp_enqueue_script(
                'wc-track17-dashboard',
                WC_TRACK17_PLUGIN_URL . 'assets/js/dashboard.js',
                array('jquery', 'chart-js'),
                WC_TRACK17_VERSION,
                true
            );
        }
    }

    /**
     * AJAX para atualizar rastreamento individual
     */
    public function update_single_tracking_ajax() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_track17_update_tracking')) {
            wp_send_json_error(__('Erro de segurança.', 'wc-track17-rastreamento'));
        }
        
        // Verifica permissões
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(__('Permissão negada.', 'wc-track17-rastreamento'));
        }
        
        $order_id = absint($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(__('Pedido não encontrado.', 'wc-track17-rastreamento'));
        }
        
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $carrier_code = $order->get_meta('_wc_track17_carrier_code');
        
        if (empty($tracking_code)) {
            wp_send_json_error(__('Código de rastreamento não encontrado.', 'wc-track17-rastreamento'));
        }
        
        $api = WC_Track17_API::get_instance();
        
        // Primeiro, registra o rastreamento se ainda não foi registrado
        $is_registered = $order->get_meta('_wc_track17_registered');
        if (!$is_registered) {
            $register_result = $api->register_tracking($tracking_code, $carrier_code);
            if (!is_wp_error($register_result) && $register_result['success']) {
                $order->update_meta_data('_wc_track17_registered', 'yes');
            }
        }
        
        // Consulta as informações de rastreamento
        $tracking_data = array(
            array(
                'number' => $tracking_code,
                'carrier' => $carrier_code
            )
        );
        
        $result = $api->get_tracking_info($tracking_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        if (!$result['success']) {
            wp_send_json_error($result['message']);
        }
        
        // Processa os dados de rastreamento
        if (!empty($result['data'])) {
            $track_info = $result['data'][0]['track_info'] ?? array();
            $status = $api->determine_tracking_status($track_info);
            
            $order->update_meta_data('_wc_track17_tracking_status', $status);
            $order->update_meta_data('_wc_track17_last_update', current_time('mysql'));
            $order->save();
            
            wp_send_json_success(__('Rastreamento atualizado com sucesso!', 'wc-track17-rastreamento'));
        } else {
            wp_send_json_error(__('Nenhuma informação de rastreamento encontrada.', 'wc-track17-rastreamento'));
        }
    }

    /**
     * AJAX para atualizar todos os rastreamentos
     */
    public function update_all_tracking_ajax() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_track17_admin')) {
            wp_send_json_error(__('Erro de segurança.', 'wc-track17-rastreamento'));
        }
        
        // Verifica permissões
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permissão negada.', 'wc-track17-rastreamento'));
        }
        
        $result = $this->update_all_tracking();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Atualiza todos os rastreamentos
     */
    public function update_all_tracking() {
        $api = WC_Track17_API::get_instance();
        
        // Busca pedidos com código de rastreamento
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('processing', 'on-hold', 'completed'),
            'meta_query' => array(
                array(
                    'key' => '_wc_track17_tracking_code',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $updated_count = 0;
        $error_count = 0;
        $tracking_batch = array();
        
        foreach ($orders as $order) {
            $tracking_code = $order->get_meta('_wc_track17_tracking_code');
            $carrier_code = $order->get_meta('_wc_track17_carrier_code');
            
            if (empty($tracking_code)) {
                continue;
            }
            
            // Registra o rastreamento se ainda não foi registrado
            $is_registered = $order->get_meta('_wc_track17_registered');
            if (!$is_registered) {
                $register_result = $api->register_tracking($tracking_code, $carrier_code);
                if (!is_wp_error($register_result) && $register_result['success']) {
                    $order->update_meta_data('_wc_track17_registered', 'yes');
                    $order->save();
                }
            }
            
            // Adiciona ao lote para consulta
            $tracking_batch[] = array(
                'number' => $tracking_code,
                'carrier' => $carrier_code,
                'order_id' => $order->get_id()
            );
            
            // Processa em lotes de 40 (limite da API)
            if (count($tracking_batch) >= 40) {
                $batch_result = $this->process_tracking_batch($tracking_batch, $api);
                $updated_count += $batch_result['updated'];
                $error_count += $batch_result['errors'];
                $tracking_batch = array();
                
                // Pausa para evitar limite de taxa
                sleep(1);
            }
        }
        
        // Processa o último lote
        if (!empty($tracking_batch)) {
            $batch_result = $this->process_tracking_batch($tracking_batch, $api);
            $updated_count += $batch_result['updated'];
            $error_count += $batch_result['errors'];
        }
        
        $message = sprintf(
            __('%d rastreamentos atualizados com sucesso. %d erros encontrados.', 'wc-track17-rastreamento'),
            $updated_count,
            $error_count
        );
        
        return array(
            'success' => true,
            'message' => $message,
            'updated' => $updated_count,
            'errors' => $error_count
        );
    }

    /**
     * Processa um lote de rastreamentos
     */
    private function process_tracking_batch($tracking_batch, $api) {
        $updated_count = 0;
        $error_count = 0;
        
        // Prepara dados para a API
        $api_data = array();
        $order_map = array();
        
        foreach ($tracking_batch as $item) {
            $api_data[] = array(
                'number' => $item['number'],
                'carrier' => $item['carrier']
            );
            $order_map[$item['number']] = $item['order_id'];
        }
        
        // Consulta a API
        $result = $api->get_tracking_info($api_data);
        
        if (is_wp_error($result) || !$result['success']) {
            return array('updated' => 0, 'errors' => count($tracking_batch));
        }
        
        // Processa os resultados
        foreach ($result['data'] as $track_data) {
            $tracking_number = $track_data['number'] ?? '';
            $order_id = $order_map[$tracking_number] ?? 0;
            
            if (!$order_id) {
                $error_count++;
                continue;
            }
            
            $order = wc_get_order($order_id);
            if (!$order) {
                $error_count++;
                continue;
            }
            
            $track_info = $track_data['track_info'] ?? array();
            $status = $api->determine_tracking_status($track_info);
            
            $order->update_meta_data('_wc_track17_tracking_status', $status);
            $order->update_meta_data('_wc_track17_last_update', current_time('mysql'));
            $order->save();
            
            $updated_count++;
        }
        
        return array('updated' => $updated_count, 'errors' => $error_count);
    }

    /**
     * Cron job para atualização automática
     */
    public function update_all_tracking_cron() {
        $this->update_all_tracking();
    }

    /**
     * Adiciona intervalos de cron personalizados
     */
    public function add_cron_intervals($schedules) {
        $schedules['wc_track17_1hours'] = array(
            'interval' => 3600,
            'display' => __('A cada hora', 'wc-track17-rastreamento')
        );
        
        $schedules['wc_track17_3hours'] = array(
            'interval' => 10800,
            'display' => __('A cada 3 horas', 'wc-track17-rastreamento')
        );
        
        $schedules['wc_track17_6hours'] = array(
            'interval' => 21600,
            'display' => __('A cada 6 horas', 'wc-track17-rastreamento')
        );
        
        $schedules['wc_track17_12hours'] = array(
            'interval' => 43200,
            'display' => __('A cada 12 horas', 'wc-track17-rastreamento')
        );
        
        $schedules['wc_track17_24hours'] = array(
            'interval' => 86400,
            'display' => __('Uma vez por dia', 'wc-track17-rastreamento')
        );
        
        return $schedules;
    }

    /**
     * Exibe avisos administrativos
     */
    public function admin_notices() {
        // Verifica se a chave da API está configurada
        $api_key = get_option('track17_api_key', '');
        if (empty($api_key)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php 
                    echo sprintf(
                        __('O plugin Track17 Rastreamento precisa de uma chave da API para funcionar. %s', 'wc-track17-rastreamento'),
                        '<a href="' . admin_url('admin.php?page=wc-track17-settings') . '">' . __('Configure agora', 'wc-track17-rastreamento') . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Adiciona meta box personalizada na página do pedido
     */
    public function add_tracking_meta_box() {
        add_meta_box(
            'wc-track17-tracking-info',
            __('Informações de Rastreamento Track17', 'wc-track17-rastreamento'),
            array($this, 'tracking_meta_box_content'),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Conteúdo da meta box de rastreamento
     */
    public function tracking_meta_box_content($post) {
        $order = wc_get_order($post->ID);
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $tracking_status = $order->get_meta('_wc_track17_tracking_status');
        $last_update = $order->get_meta('_wc_track17_last_update');
        $is_registered = $order->get_meta('_wc_track17_registered');
        
        ?>
        <div class="wc-track17-meta-box">
            <?php if ($tracking_code) : ?>
                <p><strong><?php _e('Código:', 'wc-track17-rastreamento'); ?></strong> <?php echo esc_html($tracking_code); ?></p>
                
                <?php if ($tracking_status) : ?>
                    <p><strong><?php _e('Status:', 'wc-track17-rastreamento'); ?></strong> 
                        <span class="wc-track17-status wc-track17-status-<?php echo esc_attr($tracking_status); ?>">
                            <?php echo esc_html(WC_Track17_Order_Meta::get_instance()->get_status_label($tracking_status)); ?>
                        </span>
                    </p>
                <?php endif; ?>
                
                <?php if ($last_update) : ?>
                    <p><strong><?php _e('Última atualização:', 'wc-track17-rastreamento'); ?></strong><br>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update))); ?>
                    </p>
                <?php endif; ?>
                
                <p>
                    <strong><?php _e('Registrado na API:', 'wc-track17-rastreamento'); ?></strong>
                    <?php echo $is_registered ? __('Sim', 'wc-track17-rastreamento') : __('Não', 'wc-track17-rastreamento'); ?>
                </p>
                
                <p>
                    <a href="https://www.17track.net/en/track#nums=<?php echo urlencode($tracking_code); ?>" target="_blank" class="button button-secondary">
                        <?php _e('Ver no 17TRACK', 'wc-track17-rastreamento'); ?>
                    </a>
                </p>
            <?php else : ?>
                <p><?php _e('Nenhum código de rastreamento definido.', 'wc-track17-rastreamento'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}

