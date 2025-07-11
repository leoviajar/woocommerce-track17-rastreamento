<?php
/**
 * Classe gerenciadora de e-mails do Track17
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe WC_Track17_Email_Manager
 * 
 * Responsável por gerenciar os e-mails personalizados do plugin,
 * incluindo o registro no sistema de e-mails do WooCommerce.
 */
class WC_Track17_Email_Manager {

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
        // Adiciona o e-mail personalizado à lista de e-mails do WooCommerce
        add_filter('woocommerce_email_classes', array($this, 'add_tracking_email_class'));
        
        // Hook para disparar o e-mail quando um código de rastreamento for adicionado
        add_action('updated_post_meta', array($this, 'trigger_tracking_email_on_meta_update'), 10, 4);
        
        // Hook para HPOS (High-Performance Order Storage)
        add_action('woocommerce_update_order', array($this, 'trigger_tracking_email_on_order_update'));
    }

    /**
     * Adiciona a classe de e-mail de rastreamento à lista de e-mails do WooCommerce
     *
     * @param array $email_classes Classes de e-mail existentes
     * @return array Classes de e-mail com nossa classe adicionada
     */
    public function add_tracking_email_class($email_classes) {
        
        // Inclui a classe de e-mail de rastreamento
        require_once WC_TRACK17_PLUGIN_DIR . 'includes/class-wc-track17-tracking-email.php';
        
        // Adiciona à lista de classes de e-mail que o WooCommerce carrega
        $email_classes['WC_Track17_Tracking_Email'] = new WC_Track17_Tracking_Email();
        
        return $email_classes;
    }

    /**
     * Dispara o e-mail de rastreamento quando um meta é atualizado (para posts)
     *
     * @param int $meta_id ID do meta
     * @param int $object_id ID do objeto (pedido)
     * @param string $meta_key Chave do meta
     * @param mixed $meta_value Valor do meta
     */
    public function trigger_tracking_email_on_meta_update($meta_id, $object_id, $meta_key, $meta_value) {
        
        // Verifica se é o meta do código de rastreamento e se não está vazio
        if ($meta_key === '_wc_track17_tracking_code' && !empty($meta_value)) {
            
            // Verifica se é um pedido válido
            $order = wc_get_order($object_id);
            if (!$order) {
                return;
            }
            
            // Dispara o e-mail
            $this->send_tracking_email($object_id, $meta_value);
        }
    }

    /**
     * Dispara o e-mail de rastreamento quando um pedido é atualizado (HPOS)
     *
     * @param int $order_id ID do pedido
     */
    public function trigger_tracking_email_on_order_update($order_id) {
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        $email_sent_meta = $order->get_meta('_wc_track17_tracking_email_sent');
        
        // Se há código de rastreamento e ainda não foi enviado e-mail para este código
        if (!empty($tracking_code) && $email_sent_meta !== $tracking_code) {
            $this->send_tracking_email($order_id, $tracking_code);
        }
    }

    /**
     * Envia o e-mail de rastreamento
     *
     * @param int $order_id ID do pedido
     * @param string $tracking_code Código de rastreamento
     */
    private function send_tracking_email($order_id, $tracking_code) {
        
        // Obtém a instância do gerenciador de e-mails do WooCommerce
        $mailer = WC()->mailer();
        
        // Obtém todas as classes de e-mail
        $emails = $mailer->get_emails();
        
        // Verifica se nossa classe de e-mail existe
        if (isset($emails['WC_Track17_Tracking_Email'])) {
            
            // Dispara o e-mail
            $emails['WC_Track17_Tracking_Email']->trigger($order_id, $tracking_code);
        }
    }

    /**
     * Envia e-mail de rastreamento manualmente (para uso em outras partes do código)
     *
     * @param int $order_id ID do pedido
     * @param string $tracking_code Código de rastreamento (opcional)
     * @return bool True se enviado com sucesso, false caso contrário
     */
    public static function send_tracking_notification($order_id, $tracking_code = '') {
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        // Se não foi fornecido código, pega do pedido
        if (empty($tracking_code)) {
            $tracking_code = $order->get_meta('_wc_track17_tracking_code');
        }
        
        if (empty($tracking_code)) {
            return false;
        }
        
        // Obtém a instância do gerenciador de e-mails do WooCommerce
        $mailer = WC()->mailer();
        
        // Obtém todas as classes de e-mail
        $emails = $mailer->get_emails();
        
        // Verifica se nossa classe de e-mail existe
        if (isset($emails['WC_Track17_Tracking_Email'])) {
            
            // Dispara o e-mail
            $emails['WC_Track17_Tracking_Email']->trigger($order_id, $tracking_code);
            return true;
        }
        
        return false;
    }
}

