<?php
/**
 * Classe para gerenciar as configurações do plugin
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class WC_Track17_Settings {

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
        // Adiciona a página de configurações
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Registra as configurações
        add_action('admin_init', array($this, 'register_settings'));
        
        // Adiciona link de configurações na página de plugins
        add_filter('plugin_action_links_' . WC_TRACK17_PLUGIN_BASENAME, array($this, 'add_settings_link'));
        
        // AJAX para testar a API
        add_action('wp_ajax_wc_track17_test_api', array($this, 'test_api_ajax'));
    }

    /**
     * Adiciona a página de configurações no menu do WordPress
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Configurações Track17', 'wc-track17-rastreamento'),
            __('Track17 Rastreamento', 'wc-track17-rastreamento'),
            'manage_options',
            'wc-track17-settings',
            array($this, 'settings_page_content')
        );
    }

    /**
     * Registra as configurações do plugin
     */
    public function register_settings() {
        // Seção principal
        add_settings_section(
            'wc_track17_main_section',
            __('Configurações da API Track17', 'wc-track17-rastreamento'),
            array($this, 'main_section_callback'),
            'wc-track17-settings'
        );

        // Campo da chave da API
        add_settings_field(
            'track17_api_key',
            __('Chave da API Track17', 'wc-track17-rastreamento'),
            array($this, 'api_key_field_callback'),
            'wc-track17-settings',
            'wc_track17_main_section'
        );

        // Campo de registro automático
        add_settings_field(
            'track17_auto_register',
            __('Registro Automático', 'wc-track17-rastreamento'),
            array($this, 'auto_register_field_callback'),
            'wc-track17-settings',
            'wc_track17_main_section'
        );

        // Campo de frequência de atualização
        add_settings_field(
            'track17_update_frequency',
            __('Frequência de Atualização', 'wc-track17-rastreamento'),
            array($this, 'update_frequency_field_callback'),
            'wc-track17-settings',
            'wc_track17_main_section'
        );

        // Campo de webhook URL
        add_settings_field(
            'track17_webhook_url',
            __('URL do Webhook', 'wc-track17-rastreamento'),
            array($this, 'webhook_url_field_callback'),
            'wc-track17-settings',
            'wc_track17_main_section'
        );

        // Registra as opções
        register_setting('wc_track17_settings_group', 'track17_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('wc_track17_settings_group', 'track17_auto_register', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('wc_track17_settings_group', 'track17_update_frequency', array(
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('wc_track17_settings_group', 'track17_webhook_url', array(
            'sanitize_callback' => 'esc_url_raw'
        ));
    }

    /**
     * Callback da seção principal
     */
    public function main_section_callback() {
        echo '<p>' . __('Configure as opções do plugin Track17 Rastreamento abaixo.', 'wc-track17-rastreamento') . '</p>';
        echo '<p>' . sprintf(
            __('Para obter sua chave da API, acesse o %s e vá em Settings > Security > Access Key.', 'wc-track17-rastreamento'),
            '<a href="https://www.17track.net/en/apikey" target="_blank">painel da 17TRACK</a>'
        ) . '</p>';
    }

    /**
     * Callback do campo da chave da API
     */
    public function api_key_field_callback() {
        $api_key = get_option('track17_api_key', '');
        ?>
        <input type="text" 
               id="track17_api_key" 
               name="track17_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text" 
               placeholder="<?php _e('Cole sua chave da API aqui', 'wc-track17-rastreamento'); ?>" />
        <button type="button" id="test-api-key" class="button button-secondary">
            <?php _e('Testar API', 'wc-track17-rastreamento'); ?>
        </button>
        <span id="api-test-result"></span>
        <p class="description">
            <?php _e('Sua chave da API Track17. Esta chave é necessária para registrar e consultar rastreamentos.', 'wc-track17-rastreamento'); ?>
        </p>
        <?php
    }

    /**
     * Callback do campo de registro automático
     */
    public function auto_register_field_callback() {
        $auto_register = get_option('track17_auto_register', 'yes');
        ?>
        <label>
            <input type="checkbox" 
                   name="track17_auto_register" 
                   value="yes" 
                   <?php checked($auto_register, 'yes'); ?> />
            <?php _e('Registrar automaticamente códigos de rastreamento na API Track17', 'wc-track17-rastreamento'); ?>
        </label>
        <p class="description">
            <?php _e('Quando ativado, os códigos de rastreamento serão automaticamente registrados na API Track17 assim que forem adicionados aos pedidos.', 'wc-track17-rastreamento'); ?>
        </p>
        <?php
    }

    /**
     * Callback do campo de frequência de atualização
     */
    public function update_frequency_field_callback() {
        $frequency = get_option('track17_update_frequency', 6);
        ?>
        <select name="track17_update_frequency" id="track17_update_frequency">
            <option value="1" <?php selected($frequency, 1); ?>><?php _e('A cada hora', 'wc-track17-rastreamento'); ?></option>
            <option value="3" <?php selected($frequency, 3); ?>><?php _e('A cada 3 horas', 'wc-track17-rastreamento'); ?></option>
            <option value="6" <?php selected($frequency, 6); ?>><?php _e('A cada 6 horas', 'wc-track17-rastreamento'); ?></option>
            <option value="12" <?php selected($frequency, 12); ?>><?php _e('A cada 12 horas', 'wc-track17-rastreamento'); ?></option>
            <option value="24" <?php selected($frequency, 24); ?>><?php _e('Uma vez por dia', 'wc-track17-rastreamento'); ?></option>
        </select>
        <p class="description">
            <?php _e('Com que frequência o plugin deve verificar atualizações de rastreamento na API Track17.', 'wc-track17-rastreamento'); ?>
        </p>
        <?php
    }

    /**
     * Callback do campo de webhook URL
     */
    public function webhook_url_field_callback() {
        $webhook_url = get_option('track17_webhook_url', '');
        $site_url = site_url('/wp-json/wc-track17/v1/webhook');
        ?>
        <input type="url" 
               id="track17_webhook_url" 
               name="track17_webhook_url" 
               value="<?php echo esc_attr($webhook_url); ?>" 
               class="regular-text" 
               placeholder="<?php echo esc_attr($site_url); ?>" />
        <button type="button" id="copy-webhook-url" class="button button-secondary">
            <?php _e('Copiar URL Sugerida', 'wc-track17-rastreamento'); ?>
        </button>
        <p class="description">
            <?php _e('URL do webhook para receber notificações automáticas da Track17. Deixe em branco para usar a URL padrão.', 'wc-track17-rastreamento'); ?>
            <br>
            <strong><?php _e('URL sugerida:', 'wc-track17-rastreamento'); ?></strong> <code><?php echo esc_html($site_url); ?></code>
        </p>
        <?php
    }

    /**
     * Conteúdo da página de configurações
     */
    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configurações Track17 Rastreamento', 'wc-track17-rastreamento'); ?></h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_track17_settings_group');
                do_settings_sections('wc-track17-settings');
                submit_button();
                ?>
            </form>
            
            <div class="wc-track17-info-box" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
                <h3><?php _e('Informações Importantes', 'wc-track17-rastreamento'); ?></h3>
                <ul>
                    <li><?php _e('• A API Track17 oferece 100 rastreamentos gratuitos por mês.', 'wc-track17-rastreamento'); ?></li>
                    <li><?php _e('• Os rastreamentos são atualizados automaticamente a cada 6-12 horas pela Track17.', 'wc-track17-rastreamento'); ?></li>
                    <li><?php _e('• Para usar webhooks, configure a URL no painel da Track17.', 'wc-track17-rastreamento'); ?></li>
                    <li><?php _e('• Este plugin é compatível com HPOS (High-Performance Order Storage).', 'wc-track17-rastreamento'); ?></li>
                </ul>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Teste da API
            $('#test-api-key').on('click', function() {
                var button = $(this);
                var result = $('#api-test-result');
                var apiKey = $('#track17_api_key').val();
                
                if (!apiKey) {
                    result.html('<span style="color: red;"><?php _e('Por favor, insira uma chave da API.', 'wc-track17-rastreamento'); ?></span>');
                    return;
                }
                
                button.prop('disabled', true).text('<?php _e('Testando...', 'wc-track17-rastreamento'); ?>');
                result.html('<span style="color: #666;"><?php _e('Testando conexão...', 'wc-track17-rastreamento'); ?></span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_track17_test_api',
                        api_key: apiKey,
                        nonce: '<?php echo wp_create_nonce('wc_track17_test_api'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            result.html('<span style="color: green;">✓ <?php _e('API funcionando corretamente!', 'wc-track17-rastreamento'); ?></span>');
                        } else {
                            result.html('<span style="color: red;">✗ ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        result.html('<span style="color: red;">✗ <?php _e('Erro na requisição.', 'wc-track17-rastreamento'); ?></span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Testar API', 'wc-track17-rastreamento'); ?>');
                    }
                });
            });
            
            // Copiar URL do webhook
            $('#copy-webhook-url').on('click', function() {
                var webhookUrl = '<?php echo esc_js(site_url('/wp-json/wc-track17/v1/webhook')); ?>';
                $('#track17_webhook_url').val(webhookUrl);
                
                // Feedback visual
                var button = $(this);
                var originalText = button.text();
                button.text('<?php _e('Copiado!', 'wc-track17-rastreamento'); ?>');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
        });
        </script>
        <?php
    }

    /**
     * Adiciona link de configurações na página de plugins
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-track17-settings') . '">' . __('Configurações', 'wc-track17-rastreamento') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * AJAX para testar a API
     */
    public function test_api_ajax() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_track17_test_api')) {
            wp_die(__('Erro de segurança.', 'wc-track17-rastreamento'));
        }
        
        // Verifica permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissão negada.', 'wc-track17-rastreamento'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error(__('Chave da API é obrigatória.', 'wc-track17-rastreamento'));
        }
        
        // Testa a API fazendo uma requisição simples
        $url = 'https://api.17track.net/track/v2.2/register';
        $headers = array(
            '17token' => $api_key,
            'Content-Type' => 'application/json'
        );
        
        $data = array(
            array(
                'number' => 'TEST123456789',
                'carrier' => '2151' // Correios
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(__('Erro de conexão: ', 'wc-track17-rastreamento') . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            $decoded = json_decode($response_body, true);
            if (isset($decoded['code'])) {
                wp_send_json_success(__('API funcionando corretamente!', 'wc-track17-rastreamento'));
            } else {
                wp_send_json_error(__('Resposta inesperada da API.', 'wc-track17-rastreamento'));
            }
        } elseif ($response_code === 401) {
            wp_send_json_error(__('Chave da API inválida.', 'wc-track17-rastreamento'));
        } elseif ($response_code === 429) {
            wp_send_json_error(__('Limite de requisições excedido. Tente novamente mais tarde.', 'wc-track17-rastreamento'));
        } else {
            wp_send_json_error(sprintf(__('Erro HTTP %d: %s', 'wc-track17-rastreamento'), $response_code, $response_body));
        }
    }

    /**
     * Obtém uma configuração específica
     */
    public static function get_setting($key, $default = '') {
        return get_option($key, $default);
    }

    /**
     * Atualiza uma configuração específica
     */
    public static function update_setting($key, $value) {
        return update_option($key, $value);
    }
}

