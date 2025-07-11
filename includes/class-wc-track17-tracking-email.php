<?php
/**
 * Classe de e-mail personalizada para notificação de código de rastreamento
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe WC_Track17_Tracking_Email
 * 
 * Estende WC_Email para criar um e-mail personalizado que é enviado
 * quando um código de rastreamento é adicionado a um pedido.
 */
class WC_Track17_Tracking_Email extends WC_Email {

    /**
     * Construtor da classe
     */
    public function __construct() {
        
        // ID único para o e-mail personalizado
        $this->id = 'wc_track17_tracking_email';
        
        // É um e-mail para o cliente
        $this->customer_email = true;
        
        // Título do campo nas configurações de e-mail do WooCommerce
        $this->title = __('Código de Rastreamento Disponível', 'wc-track17-rastreamento');
        
        // Descrição do campo nas configurações de e-mail do WooCommerce
        $this->description = __('E-mail enviado ao cliente quando um código de rastreamento é adicionado ao pedido.', 'wc-track17-rastreamento');
        
        // Assunto e cabeçalho padrão nas configurações de e-mail do WooCommerce
        $this->subject = apply_filters(
            'wc_track17_tracking_email_default_subject',
            __('Seu pedido #{order_number} agora pode ser rastreado', 'wc-track17-rastreamento')
        );
        
        $this->heading = apply_filters(
            'wc_track17_tracking_email_default_heading',
            __('Código de Rastreamento Disponível', 'wc-track17-rastreamento')
        );
        
        // Define os locais dos templates que este e-mail deve usar
        $this->template_base = WC_TRACK17_PLUGIN_DIR . 'templates/';
        $this->template_html = 'emails/tracking-code-notification.php';
        $this->template_plain = 'emails/plain/tracking-code-notification.php';
        
        // Chama o construtor pai para carregar outros padrões não definidos explicitamente aqui
        parent::__construct();
        
        // Inicializa os campos do formulário
        $this->init_form_fields();
    }

    /**
     * Prepara o conteúdo do e-mail e dispara o envio
     *
     * @param int $order_id ID do pedido
     * @param string $tracking_code Código de rastreamento
     */
    public function trigger($order_id, $tracking_code = '') {
        
        // Sai se não houver ID do pedido
        if (!$order_id) {
            return;
        }
        
        // Obtém o objeto do pedido
        $this->object = wc_get_order($order_id);
        
        if (!$this->object) {
            return;
        }
        
        // Verifica se o e-mail já foi enviado para este código de rastreamento
        $email_sent_meta = $this->object->get_meta('_wc_track17_tracking_email_sent');
        $current_tracking_code = $this->object->get_meta('_wc_track17_tracking_code');
        
        // Se já foi enviado para este código específico, não envia novamente
        if ($email_sent_meta === $current_tracking_code && !empty($email_sent_meta)) {
            return;
        }
        
        // Define o destinatário
        $this->recipient = $this->object->get_billing_email();
        
        // Armazena o código de rastreamento para uso no template
        $this->tracking_code = $tracking_code ?: $current_tracking_code;
        
        // Substitui variáveis no assunto/cabeçalhos
        $this->find[] = '{order_date}';
        $this->replace[] = wc_format_datetime($this->object->get_date_created());
        
        $this->find[] = '{order_number}';
        $this->replace[] = $this->object->get_order_number();
        
        $this->find[] = '{tracking_code}';
        $this->replace[] = $this->tracking_code;
        
        // Verifica se o e-mail está habilitado e se há destinatário
        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }
        
        // Envia o e-mail
        $sent = $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
        
        if ($sent) {
            // Adiciona nota ao pedido sobre o envio do e-mail
            $this->object->add_order_note(
                sprintf(
                    __('%s enviado para o cliente. Código de rastreamento: %s', 'wc-track17-rastreamento'),
                    $this->title,
                    $this->tracking_code
                )
            );
            
            // Define meta para indicar que o e-mail foi enviado para este código
            $this->object->update_meta_data('_wc_track17_tracking_email_sent', $this->tracking_code);
            $this->object->save();
        }
    }

    /**
     * Obtém o conteúdo HTML do e-mail
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order' => $this->object,
                'tracking_code' => $this->tracking_code,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Obtém o conteúdo em texto simples do e-mail
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order' => $this->object,
                'tracking_code' => $this->tracking_code,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Inicializa os campos do formulário de configurações
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Habilitar/Desabilitar', 'wc-track17-rastreamento'),
                'type' => 'checkbox',
                'label' => __('Habilitar esta notificação por e-mail', 'wc-track17-rastreamento'),
                'default' => 'yes'
            ),
            'subject' => array(
                'title' => __('Assunto', 'wc-track17-rastreamento'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => sprintf(
                    __('Controla a linha de assunto do e-mail. Deixe em branco para usar o assunto padrão: <code>%s</code>.', 'wc-track17-rastreamento'),
                    $this->subject
                ),
                'placeholder' => '',
                'default' => '',
            ),
            'heading' => array(
                'title' => __('Cabeçalho do E-mail', 'wc-track17-rastreamento'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => sprintf(
                    __('Controla o cabeçalho principal contido na notificação por e-mail. Deixe em branco para usar o cabeçalho padrão: <code>%s</code>.', 'wc-track17-rastreamento'),
                    $this->heading
                ),
                'placeholder' => '',
                'default' => '',
            ),
            'additional_content' => array(
                'title' => __('Conteúdo Adicional', 'wc-track17-rastreamento'),
                'description' => __('Texto a ser exibido abaixo do conteúdo principal do e-mail.', 'wc-track17-rastreamento'),
                'css' => 'width:400px; height: 75px;',
                'placeholder' => __('N/A', 'wc-track17-rastreamento'),
                'type' => 'textarea',
                'default' => $this->get_default_additional_content(),
                'desc_tip' => true,
            ),
            'email_type' => array(
                'title' => __('Tipo de e-mail', 'wc-track17-rastreamento'),
                'type' => 'select',
                'description' => __('Escolha qual formato de e-mail enviar.', 'wc-track17-rastreamento'),
                'default' => 'html',
                'class' => 'email_type wc-enhanced-select',
                'options' => $this->get_email_type_options(),
                'desc_tip' => true,
            ),
        );
    }

    /**
     * Obtém o conteúdo adicional padrão
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __('Obrigado por escolher nossa loja!', 'wc-track17-rastreamento');
    }

    /**
     * Obtém o assunto padrão
     *
     * @return string
     */
    public function get_default_subject() {
        return apply_filters(
            'wc_track17_tracking_email_default_subject',
            __('Seu pedido #{order_number} agora pode ser rastreado', 'wc-track17-rastreamento')
        );
    }

    /**
     * Obtém o cabeçalho padrão
     *
     * @return string
     */
    public function get_default_heading() {
        return apply_filters(
            'wc_track17_tracking_email_default_heading',
            __('Código de Rastreamento Disponível', 'wc-track17-rastreamento')
        );
    }
}

