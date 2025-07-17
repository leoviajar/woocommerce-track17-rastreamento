<?php
/**
 * Classe para o frontend público
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class WC_Track17_Public {

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
        // Enfileira scripts e estilos do frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // Adiciona endpoint para rastreamento
        add_action('init', array($this, 'add_tracking_endpoint'));
        
        // Adiciona conteúdo do endpoint
        add_action('woocommerce_account_tracking_endpoint', array($this, 'tracking_endpoint_content'));
        
        // Adiciona item ao menu da conta
        add_filter('woocommerce_account_menu_items', array($this, 'add_tracking_menu_item'));
        
        // Shortcode para rastreamento
        add_shortcode('wc_track17_tracking', array($this, 'tracking_shortcode'));
        
        // AJAX para buscar rastreamento (público)
        add_action('wp_ajax_wc_track17_public_tracking', array($this, 'public_tracking_ajax'));
        add_action('wp_ajax_nopriv_wc_track17_public_tracking', array($this, 'public_tracking_ajax'));
        
        // Widget de rastreamento
        add_action('widgets_init', array($this, 'register_tracking_widget'));
        
        // API REST para webhook
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
        
        add_action('rest_api_init', array($this, 'register_custom_ajax_endpoint'));
    }
    
    /**
     * Registra um endpoint REST personalizado para a busca de rastreamento.
     * Esta é uma alternativa mais moderna e robusta ao admin-ajax.php.
     */
    public function register_custom_ajax_endpoint() {
        register_rest_route('wc-track17/v1', '/track', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'public_tracking_ajax_rest_handler'),
            'permission_callback' => '__return_true', // Aberto para todos, a segurança é via nonce
        ));
    }
    
    /**
     * Manipulador para o endpoint REST. Reutiliza a lógica original
     * mas adapta a forma como os dados são recebidos.
     * MODIFICADO: Agora busca e processa a timeline completa da API 17TRACK
     * MODIFICADO: Agora suporta busca por telefone além de e-mail
     */
    public function public_tracking_ajax_rest_handler(WP_REST_Request $request) {
        $nonce = $request->get_header("X-WP-Nonce");
        $params = $request->get_json_params();
        $tracking_code = isset($params["tracking_code"]) ? sanitize_text_field($params["tracking_code"]) : "";
        $email_or_phone = isset($params["email"]) ? sanitize_text_field($params["email"]) : "";
        $order_number = isset($params["order_number"]) ? sanitize_text_field($params["order_number"]) : "";

        // Verificação de Nonce (adaptada para a API REST)
        if (!$nonce || !wp_verify_nonce($nonce, "wp_rest")) {
            return new WP_Error("rest_nonce_invalid", __("Erro de segurança.", "wc-track17-rastreamento"), array("status" => 403));
        }

        if (empty($tracking_code) && (empty($email_or_phone) || empty($order_number))) {
            return new WP_Error("bad_request", __("Por favor, insira um código de rastreamento ou e-mail/telefone e número do pedido.", "wc-track17-rastreamento"), array("status" => 400));
        }

        $orders = array();

        if (!empty($tracking_code)) {
            // Busca por código de rastreamento
            $orders = wc_get_orders(array(
                "limit"      => 1,
                "meta_query" => array(
                    array(
                        "key"     => "_wc_track17_tracking_code",
                        "value"   => $tracking_code,
                        "compare" => "=",
                    ),
                ),
            ));
        } elseif (!empty($email_or_phone) && !empty($order_number)) {
            // MODIFICADO: Busca por e-mail OU telefone e número do pedido
            $orders = $this->find_order_by_email_or_phone_and_number($email_or_phone, $order_number);
        }

        if (empty($orders)) {
            return new WP_Error("not_found", __("Pedido não encontrado com os dados fornecidos.", "wc-track17-rastreamento"), array("status" => 404));
        }

        // Se encontrou, monta a resposta de sucesso
        $order = $orders[0];
        $order_meta = WC_Track17_Order_Meta::get_instance();
        $carriers = WC_Track17_API::get_instance()->get_supported_carriers();
        $carrier_code = $order->get_meta("_wc_track17_carrier_code");
        $tracking_code_found = $order->get_meta("_wc_track17_tracking_code");

        // NOVO: Busca a timeline completa da API 17TRACK
        $timeline = $this->get_tracking_timeline($tracking_code_found);

        $result = array(
            "order_number"         => $order->get_order_number(),
            "tracking_code"        => $tracking_code_found,
            "tracking_status"      => $order->get_meta("_wc_track17_tracking_status"),
            "tracking_status_label"=> $order_meta->get_status_label($order->get_meta("_wc_track17_tracking_status")),
            "carrier_name"         => isset($carriers[$carrier_code]) ? $carriers[$carrier_code] : "",
            "last_update"          => $order->get_meta("_wc_track17_last_update") ? date_i18n(get_option("date_format") . " " . get_option("time_format"), strtotime($order->get_meta("_wc_track17_last_update"))) : "",
            "track_url"            => "https://www.17track.net/en/track#nums=" . urlencode($tracking_code_found),
            "timeline"             => $timeline, // NOVO: Adiciona a timeline
        );

        return new WP_REST_Response($result, 200);
    }

    /**
     * NOVA FUNÇÃO: Busca pedido por e-mail OU telefone e número do pedido
     */
    private function find_order_by_email_or_phone_and_number($email_or_phone, $order_number) {
        $orders = array();
        
        // Primeiro, tenta identificar se é um e-mail ou telefone
        if ($this->is_email($email_or_phone)) {
            // É um e-mail - busca por usuário
            $user = get_user_by('email', $email_or_phone);
            if ($user) {
                $customer_orders = wc_get_orders(array(
                    "limit"      => -1,
                    "customer"   => $user->ID,
                ));

                foreach ($customer_orders as $customer_order) {
                    if ($customer_order->get_order_number() == $order_number) {
                        $orders[] = $customer_order;
                        break;
                    }
                }
            }
        } else {
            // Assume que é um telefone - busca por meta de telefone
            $orders = $this->find_order_by_phone_and_number($email_or_phone, $order_number);
        }
        
        return $orders;
    }

    /**
     * NOVA FUNÇÃO: Verifica se o valor é um e-mail válido
     */
    private function is_email($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * NOVA FUNÇÃO: Busca pedido por telefone e número do pedido
     */
    private function find_order_by_phone_and_number($phone, $order_number) {
        // Limpa o telefone removendo caracteres especiais
        $clean_phone = $this->clean_phone_number($phone);
        
        // Busca todos os pedidos que tenham o número especificado
        $all_orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('processing', 'on-hold', 'completed', 'pending'),
        ));
        
        foreach ($all_orders as $order) {
            // Verifica se o número do pedido corresponde
            if ($order->get_order_number() != $order_number) {
                continue;
            }
            
            // Verifica telefone de cobrança
            $billing_phone = $this->clean_phone_number($order->get_billing_phone());
            if ($billing_phone === $clean_phone) {
                return array($order);
            }
            
            // Verifica telefone de entrega (se diferente)
            $shipping_phone = $this->clean_phone_number($order->get_shipping_phone());
            if ($shipping_phone && $shipping_phone === $clean_phone) {
                return array($order);
            }
            
            // Verifica telefones em meta customizados (caso existam)
            $meta_phones = array(
                $order->get_meta('_billing_cellphone'),
                $order->get_meta('_shipping_cellphone'),
                $order->get_meta('_billing_phone_2'),
                $order->get_meta('_shipping_phone_2'),
            );
            
            foreach ($meta_phones as $meta_phone) {
                if ($meta_phone && $this->clean_phone_number($meta_phone) === $clean_phone) {
                    return array($order);
                }
            }
        }
        
        return array();
    }

    /**
     * NOVA FUNÇÃO: Limpa número de telefone para comparação
     */
    private function clean_phone_number($phone) {
        if (empty($phone)) {
            return '';
        }
        
        // Remove todos os caracteres que não sejam números
        $clean = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove códigos de país comuns do Brasil
        if (strlen($clean) === 13 && substr($clean, 0, 2) === '55') {
            $clean = substr($clean, 2); // Remove +55
        }
        
        // Remove zero inicial de DDD se presente
        if (strlen($clean) === 11 && substr($clean, 0, 1) === '0') {
            $clean = substr($clean, 1);
        }
        
        return $clean;
    }

    /**
     * NOVA FUNÇÃO: Busca a timeline completa de rastreamento da API 17TRACK
     */
    private function get_tracking_timeline($tracking_code) {
        $api = WC_Track17_API::get_instance();
        
        // Busca informações detalhadas da API
        $tracking_info = $api->get_tracking_info(array($tracking_code));
        
        if (is_wp_error($tracking_info) || !$tracking_info['success'] || empty($tracking_info['data'])) {
            return array(); // Retorna array vazio se não conseguir buscar
        }

        $timeline = array();
        
        // Processa os dados da API para extrair a timeline
        foreach ($tracking_info['data'] as $track_data) {
            if (isset($track_data['track_info']['tracking']['providers'][0]['events'])) {
                $events = $track_data['track_info']['tracking']['providers'][0]['events'];
                
                // Ordena os eventos por data (mais recente primeiro)
                usort($events, function($a, $b) {
                    return strtotime($b['time_iso']) - strtotime($a['time_iso']);
                });
                
                foreach ($events as $event) {
                    $timeline[] = array(
                        'date' => $this->format_event_date($event['time_iso']),
                        'time' => $this->format_event_time($event['time_iso']),
                        'location' => $event['location'] ?? '',
                        'description' => $event['description'] ?? '',
                        'status' => $this->determine_event_status($event['description'] ?? ''),
                    );
                }
                
                break; // Usa apenas o primeiro provider
            }
        }
        
        return $timeline;
    }

    /**
     * NOVA FUNÇÃO: Formata a data do evento
     */
    private function format_event_date($iso_date) {
        if (empty($iso_date)) {
            return '';
        }
        
        try {
            $date = new DateTime($iso_date);
            return $date->format('d/m/Y');
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * NOVA FUNÇÃO: Formata a hora do evento
     */
    private function format_event_time($iso_date) {
        if (empty($iso_date)) {
            return '';
        }
        
        try {
            $date = new DateTime($iso_date);
            return $date->format('H:i');
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * NOVA FUNÇÃO: Determina o status do evento baseado na descrição
     */
    private function determine_event_status($description) {
        $description_lower = strtolower($description);
        
        if (strpos($description_lower, 'entregue') !== false || strpos($description_lower, 'delivered') !== false) {
            return 'delivered';
        } elseif (strpos($description_lower, 'saiu para entrega') !== false || strpos($description_lower, 'out for delivery') !== false) {
            return 'out_for_delivery';
        } elseif (strpos($description_lower, 'em trânsito') !== false || strpos($description_lower, 'in transit') !== false) {
            return 'in_transit';
        } elseif (strpos($description_lower, 'postado') !== false || strpos($description_lower, 'posted') !== false) {
            return 'posted';
        } elseif (strpos($description_lower, 'coletado') !== false || strpos($description_lower, 'picked up') !== false) {
            return 'picked_up';
        } else {
            return 'info';
        }
    }

    /**
     * Enfileira scripts e estilos do frontend
     */
    public function enqueue_public_assets() {
        // Carrega apenas nas páginas relevantes
        if (is_account_page() || is_wc_endpoint_url('tracking') || has_shortcode(get_post()->post_content ?? '', 'wc_track17_tracking')) {
            wp_enqueue_style(
                'wc-track17-public',
                WC_TRACK17_PLUGIN_URL . 'assets/css/public.css',
                array(),
                WC_TRACK17_VERSION
            );
            
            wp_enqueue_script(
                'wc-track17-public',
                WC_TRACK17_PLUGIN_URL . 'assets/js/public.js',
                array('jquery'),
                WC_TRACK17_VERSION,
                true
            );
            
            wp_localize_script('wc-track17-public', 'wc_track17_public', array(
            'api_url'   => esc_url_raw(rest_url('wc-track17/v1/track')), // Nova URL da API
            'nonce'     => wp_create_nonce('wp_rest'), // Nonce padrão da API REST
            'strings'   => array(
                'searching'    => __('Buscando...', 'wc-track17-rastreamento'),
                'not_found'    => __('Código de rastreamento não encontrado.', 'wc-track17-rastreamento'),
                'error'        => __('Ocorreu um erro no servidor. Por favor, tente novamente mais tarde.', 'wc-track17-rastreamento'),
                'invalid_code' => __('Por favor, insira um código de rastreamento válido.', 'wc-track17-rastreamento'),
                'invalid_input' => __('Por favor, insira um código de rastreamento ou e-mail/telefone e número do pedido.', 'wc-track17-rastreamento'),
            ),
        ));
        }
    }

    /**
     * Adiciona endpoint para rastreamento
     */
    public function add_tracking_endpoint() {
        add_rewrite_endpoint('tracking', EP_ROOT | EP_PAGES);
    }

    /**
     * Adiciona item ao menu da conta
     */
    public function add_tracking_menu_item($items) {
        // Adiciona o item antes de "Sair"
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);
        
        $items['tracking'] = __('Rastreamento', 'wc-track17-rastreamento');
        $items['customer-logout'] = $logout;
        
        return $items;
    }

    /**
     * Conteúdo do endpoint de rastreamento
     */
    public function tracking_endpoint_content() {
        $user_id = get_current_user_id();
        
        // Busca pedidos do usuário com rastreamento
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => -1,
            'status' => array('processing', 'on-hold', 'completed'),
            'meta_query' => array(
                array(
                    'key' => '_wc_track17_tracking_code',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        ?>
        <div class="wc-track17-my-tracking">
            <h2><?php _e('Meus Rastreamentos', 'wc-track17-rastreamento'); ?></h2>
            
            <?php if (empty($orders)) : ?>
                <p><?php _e('Você não possui pedidos com rastreamento.', 'wc-track17-rastreamento'); ?></p>
            <?php else : ?>
                <div class="wc-track17-orders-list">
                    <?php foreach ($orders as $order) : 
                        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
                        $tracking_status = $order->get_meta('_wc_track17_tracking_status');
                        $last_update = $order->get_meta('_wc_track17_last_update');
                        $order_meta = WC_Track17_Order_Meta::get_instance();
                    ?>
                        <div class="wc-track17-order-item">
                            <div class="order-header">
                                <h3>
                                    <?php printf(__('Pedido #%s', 'wc-track17-rastreamento'), $order->get_order_number()); ?>
                                    <span class="order-date"><?php echo $order->get_date_created()->date_i18n(get_option('date_format')); ?></span>
                                </h3>
                            </div>
                            
                            <div class="tracking-info">
                                <div class="tracking-code">
                                    <strong><?php _e('Código de Rastreamento:', 'wc-track17-rastreamento'); ?></strong>
                                    <span class="code"><?php echo esc_html($tracking_code); ?></span>
                                    <button type="button" class="copy-code" data-code="<?php echo esc_attr($tracking_code); ?>">
                                        <?php _e('Copiar', 'wc-track17-rastreamento'); ?>
                                    </button>
                                </div>
                                
                                <?php if ($tracking_status) : ?>
                                <div class="tracking-status">
                                    <strong><?php _e('Status:', 'wc-track17-rastreamento'); ?></strong>
                                    <span class="status status-<?php echo esc_attr($tracking_status); ?>">
                                        <?php echo esc_html($order_meta->get_status_label($tracking_status)); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($last_update) : ?>
                                <div class="last-update">
                                    <strong><?php _e('Última Atualização:', 'wc-track17-rastreamento'); ?></strong>
                                    <span><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update)); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="tracking-actions">
                                    <a href="<?php echo $order->get_view_order_url(); ?>" class="button view-order">
                                        <?php _e('Ver Pedido', 'wc-track17-rastreamento'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Shortcode para rastreamento
     */
public function tracking_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder_tracking' => __('Número de Rastreio', 'wc-track17-rastreamento'),
            'placeholder_email' => __('E-mail / Telefone', 'wc-track17-rastreamento'),
            'placeholder_order' => __('Número do Pedido', 'wc-track17-rastreamento'),
            'button_text' => __('Localizar', 'wc-track17-rastreamento')
        ), $atts);
        
        // NOVO: Obtém código de rastreio da URL se presente
        $tracking_code_from_url = isset($_GET['codigo']) ? sanitize_text_field($_GET['codigo']) : '';
        
        ob_start();
        ?>
        <div class="wc-track17-container">
            
            <div class="wc-track17-tracking-form">
                
                <form id="wc-track17-public-form" class="tracking-form">
    
                    <div class="form-group-wrapper">
    
                        <div class="form-group form-group-email-order">
                            <input type="text" 
                                   id="email-input" 
                                   name="email" 
                                   placeholder="<?php echo esc_attr($atts['placeholder_email']); ?>" />
                            <input type="text" 
                                   id="order-number-input" 
                                   name="order_number" 
                                   placeholder="<?php echo esc_attr($atts['placeholder_order']); ?>" />
                            <button type="submit" class="button">
                                <?php echo esc_html($atts['button_text']); ?>
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="or-separator"><span><?php _e('Ou', 'wc-track17-rastreamento'); ?></span></div>
                
                <form id="wc-track17-public-form" class="tracking-form">
    
                    <div class="form-group-wrapper">
    
                        <div class="form-group form-group-tracking-code">
                            <input type="text" 
                                   id="tracking-code-input" 
                                   name="tracking_code" 
                                   placeholder="<?php echo esc_attr($atts['placeholder_tracking']); ?>"
                                   value="<?php echo esc_attr($tracking_code_from_url); ?>" />
                            <button type="submit" class="button">
                                <?php echo esc_html($atts['button_text']); ?>
                            </button>
                        </div>
                        
                    </div>
                </form>
            </div>
            <div id="tracking-result" class="tracking-result" style="display: none;"></div>
        </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX para buscar rastreamento público
     */
    public function public_tracking_ajax() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_track17_public')) {
            wp_send_json_error(__('Erro de segurança.', 'wc-track17-rastreamento'));
        }
        
        $tracking_code = sanitize_text_field($_POST['tracking_code']);
        
        if (empty($tracking_code)) {
            wp_send_json_error(__('Código de rastreamento é obrigatório.', 'wc-track17-rastreamento'));
        }
        
        // Busca o pedido pelo código de rastreamento
        $orders = wc_get_orders(array(
            'limit' => 1,
            'meta_query' => array(
                array(
                    'key' => '_wc_track17_tracking_code',
                    'value' => $tracking_code,
                    'compare' => '='
                )
            )
        ));
        
        if (empty($orders)) {
            wp_send_json_error(__('Código de rastreamento não encontrado.', 'wc-track17-rastreamento'));
        }
        
        $order = $orders[0];
        $tracking_status = $order->get_meta('_wc_track17_tracking_status');
        $last_update = $order->get_meta('_wc_track17_last_update');
        $carrier_code = $order->get_meta('_wc_track17_carrier_code');
        
        $order_meta = WC_Track17_Order_Meta::get_instance();
        $carriers = WC_Track17_API::get_instance()->get_supported_carriers();
        
        $result = array(
            'order_number' => $order->get_order_number(),
            'tracking_code' => $tracking_code,
            'tracking_status' => $tracking_status,
            'tracking_status_label' => $order_meta->get_status_label($tracking_status),
            'carrier_name' => isset($carriers[$carrier_code]) ? $carriers[$carrier_code] : '',
            'last_update' => $last_update ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update)) : '',
            'track_url' => 'https://www.17track.net/en/track#nums=' . urlencode($tracking_code)
        );
        
        wp_send_json_success($result);
    }

    /**
     * Registra o widget de rastreamento
     */
    public function register_tracking_widget() {
        register_widget('WC_Track17_Tracking_Widget');
    }

    /**
     * Registra endpoint do webhook
     */
    public function register_webhook_endpoint() {
        register_rest_route('wc-track17/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Manipula o webhook da Track17
     */
    public function handle_webhook($request) {
        $data = $request->get_json_params();
        
        if (empty($data) || !isset($data['event'])) {
            return new WP_Error('invalid_data', __('Dados inválidos.', 'wc-track17-rastreamento'), array('status' => 400));
        }
        
        // Verifica se é um evento de atualização de rastreamento
        if ($data['event'] !== 'TRACKING_UPDATED') {
            return new WP_REST_Response(array('message' => 'Event ignored'), 200);
        }
        
        if (!isset($data['data']['accepted']) || empty($data['data']['accepted'])) {
            return new WP_Error('no_tracking_data', __('Nenhum dado de rastreamento encontrado.', 'wc-track17-rastreamento'), array('status' => 400));
        }
        
        $api = WC_Track17_API::get_instance();
        
        foreach ($data['data']['accepted'] as $tracking_data) {
            $tracking_number = $tracking_data['number'] ?? '';
            
            if (empty($tracking_number)) {
                continue;
            }
            
            // Busca o pedido pelo código de rastreamento
            $orders = wc_get_orders(array(
                'limit' => 1,
                'meta_query' => array(
                    array(
                        'key' => '_wc_track17_tracking_code',
                        'value' => $tracking_number,
                        'compare' => '='
                    )
                )
            ));
            
            if (empty($orders)) {
                continue;
            }
            
            $order = $orders[0];
            $track_info = $tracking_data['track_info'] ?? array();
            $status = $api->determine_tracking_status($track_info);
            
            // Atualiza o status do pedido
            $order->update_meta_data('_wc_track17_tracking_status', $status);
            $order->update_meta_data('_wc_track17_last_update', current_time('mysql'));
            $order->save();
            
            // Adiciona nota ao pedido
            $order->add_order_note(
                sprintf(
                    __('Status de rastreamento atualizado via webhook: %s', 'wc-track17-rastreamento'),
                    WC_Track17_Order_Meta::get_instance()->get_status_label($status)
                )
            );
        }
        
        return new WP_REST_Response(array('message' => 'Webhook processed successfully'), 200);
    }
}

/**
 * Widget de rastreamento
 */
class WC_Track17_Tracking_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'wc_track17_tracking_widget',
            __('Track17 Rastreamento', 'wc-track17-rastreamento'),
            array('description' => __('Widget para rastreamento de pedidos.', 'wc-track17-rastreamento'))
        );
    }

    public function widget($args, $instance) {
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Digite o código de rastreamento', 'wc-track17-rastreamento');
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        echo do_shortcode('[wc_track17_tracking title="" placeholder="' . esc_attr($placeholder) . '"]');
        
        echo $args['after_widget'];
    }

    public function form($instance) {
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Digite o código de rastreamento', 'wc-track17-rastreamento');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Título:', 'wc-track17-rastreamento'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('placeholder')); ?>"><?php _e('Placeholder:', 'wc-track17-rastreamento'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('placeholder')); ?>" name="<?php echo esc_attr($this->get_field_name('placeholder')); ?>" type="text" value="<?php echo esc_attr($placeholder); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['placeholder'] = (!empty($new_instance['placeholder'])) ? sanitize_text_field($new_instance['placeholder']) : '';
        return $instance;
    }
}

