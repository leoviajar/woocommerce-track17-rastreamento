<?php
/**
 * Plugin Name: WooCommerce Track17 Rastreamento
 * Plugin URI: https://github.com/seu-usuario/woocommerce-track17-rastreamento
 * Description: Plugin completo de rastreamento para WooCommerce com integração à API Track17. Permite configuração de API no painel administrativo e é compatível com HPOS.
 * Version: 1.0.0
 * Author: Seu Nome
 * Author URI: https://seusite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-track17-rastreamento
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Define constantes do plugin
define('WC_TRACK17_VERSION', '1.0.0');
define('WC_TRACK17_PLUGIN_FILE', __FILE__);
define('WC_TRACK17_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_TRACK17_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_TRACK17_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principal do plugin
 */
class WC_Track17_Rastreamento {

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
     * Construtor privado para implementar singleton
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa os hooks do WordPress
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Hook de ativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Hook de desativação
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Inicializa o plugin
     */
    public function init() {
        // Verifica se o WooCommerce está ativo
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Carrega as classes do plugin
        $this->load_classes();
        
        // Inicializa os componentes
        $this->init_components();
    }

    /**
     * Carrega o domínio de texto para tradução
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wc-track17-rastreamento',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Carrega as classes necessárias
     */
    private function load_classes() {
        // Inclui as classes principais
        require_once WC_TRACK17_PLUGIN_DIR . 'includes/class-wc-track17-api.php';
        require_once WC_TRACK17_PLUGIN_DIR . 'includes/class-wc-track17-order-meta.php';
        require_once WC_TRACK17_PLUGIN_DIR . 'includes/class-wc-track17-settings.php';
        require_once WC_TRACK17_PLUGIN_DIR . 'includes/class-wc-track17-email-manager.php';
        
        // Inclui as classes do admin
        if (is_admin()) {
            require_once WC_TRACK17_PLUGIN_DIR . 'admin/class-wc-track17-admin.php';
            require_once WC_TRACK17_PLUGIN_DIR . 'admin/class-wc-track17-dashboard.php';
        }
        
        // Inclui as classes do frontend
        if (!is_admin()) {
            require_once WC_TRACK17_PLUGIN_DIR . 'public/class-wc-track17-public.php';
        }
    }

    /**
     * Inicializa os componentes do plugin
     */
    private function init_components() {
        // Inicializa as configurações
        WC_Track17_Settings::get_instance();
        
        // Inicializa os metadados de pedido
        WC_Track17_Order_Meta::get_instance();
        
        // Inicializa o gerenciador de e-mails
        WC_Track17_Email_Manager::get_instance();
        
        // Inicializa o admin se estivermos no painel administrativo
        if (is_admin()) {
            WC_Track17_Admin::get_instance();
            WC_Track17_Dashboard::get_instance();
        }
        
        // Inicializa o frontend se não estivermos no admin
        if (!is_admin()) {
            WC_Track17_Public::get_instance();
        }
    }

    /**
     * Função executada na ativação do plugin
     */
    public function activate() {
        // Cria as opções padrão
        $default_options = array(
            'track17_api_key' => '',
            'track17_webhook_url' => '',
            'track17_auto_register' => 'yes',
            'track17_update_frequency' => '6', // horas
        );
        
        foreach ($default_options as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value);
            }
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Função executada na desativação do plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Exibe aviso se o WooCommerce não estiver ativo
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                echo sprintf(
                    __('O plugin %s requer o WooCommerce para funcionar. Por favor, instale e ative o WooCommerce.', 'wc-track17-rastreamento'),
                    '<strong>WooCommerce Track17 Rastreamento</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Declara compatibilidade com HPOS
     */
    public static function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }
}

// Declara compatibilidade com HPOS antes da inicialização
add_action('before_woocommerce_init', array('WC_Track17_Rastreamento', 'declare_hpos_compatibility'));

// Inicializa o plugin
function wc_track17_rastreamento() {
    return WC_Track17_Rastreamento::get_instance();
}

// Inicia o plugin
wc_track17_rastreamento();

