<?php
/**
 * Classe para integração com a API Track17
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class WC_Track17_API {

    /**
     * Instância única da classe
     */
    private static $instance = null;

    /**
     * URL base da API Track17
     */
    private $api_base_url = 'https://api.17track.net/track/v2.2/';

    /**
     * Chave da API
     */
    private $api_key;

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
        $this->api_key = get_option('track17_api_key', '');
    }

    /**
     * Registra um número de rastreamento na API Track17
     *
     * @param string $tracking_number Número de rastreamento
     * @param string $carrier_code Código da transportadora (opcional)
     * @param array $additional_params Parâmetros adicionais (opcional)
     * @return array|WP_Error Resposta da API ou erro
     */
    public function register_tracking($tracking_number, $carrier_code = '', $additional_params = array(), $order_id = null) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Chave da API Track17 não configurada.', 'wc-track17-rastreamento'));
        }

        if (empty($tracking_number)) {
            return new WP_Error('no_tracking_number', __('Número de rastreamento é obrigatório.', 'wc-track17-rastreamento'));
        }

        $url = $this->api_base_url . 'register';
        
        // Prepara os dados para envio
        $tracking_data = array(
            'number' => $tracking_number
        );

        // Adiciona o código da transportadora se fornecido
        if (!empty($carrier_code)) {
            $tracking_data['carrier'] = $carrier_code;
        }

        if (!empty($order_id)) {
            $tracking_data['tag'] = 'Pedido #' . $order_id;
        }

        // Adiciona parâmetros adicionais se fornecidos
        if (!empty($additional_params)) {
            $tracking_data = array_merge($tracking_data, $additional_params);
        }

        $data = array($tracking_data);

        // Faz a requisição
        $response = $this->make_request($url, $data, 'POST');

        if (is_wp_error($response)) {
            return $response;
        }

        // Processa a resposta
        return $this->process_register_response($response);
    }

    /**
     * Consulta informações de rastreamento
     *
     * @param array $tracking_numbers Array de números de rastreamento
     * @return array|WP_Error Resposta da API ou erro
     */
    public function get_tracking_info($tracking_numbers) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Chave da API Track17 não configurada.', 'wc-track17-rastreamento'));
        }

        if (empty($tracking_numbers) || !is_array($tracking_numbers)) {
            return new WP_Error('no_tracking_numbers', __('Números de rastreamento são obrigatórios.', 'wc-track17-rastreamento'));
        }

        $url = $this->api_base_url . 'getTrackInfo';
        
        // Prepara os dados para envio
        $data = array();
        foreach ($tracking_numbers as $tracking_number) {
            if (is_array($tracking_number)) {
                $data[] = $tracking_number;
            } else {
                $data[] = array('number' => $tracking_number);
            }
        }

        // Faz a requisição
        $response = $this->make_request($url, $data, 'POST');

        if (is_wp_error($response)) {
            return $response;
        }

        // Processa a resposta
        return $this->process_tracking_response($response);
    }

    /**
     * Faz uma requisição para a API Track17
     *
     * @param string $url URL da requisição
     * @param array $data Dados para envio
     * @param string $method Método HTTP (GET, POST)
     * @return array|WP_Error Resposta da API ou erro
     */
    private function make_request($url, $data = array(), $method = 'GET') {
        $headers = array(
            '17token' => $this->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'WooCommerce Track17 Plugin/' . WC_TRACK17_VERSION
        );

        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => true
        );

        if ($method === 'POST' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(__('Erro na API Track17: %d - %s', 'wc-track17-rastreamento'), $response_code, $response_body)
            );
        }

        $decoded_response = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Erro ao decodificar resposta da API.', 'wc-track17-rastreamento'));
        }

        return $decoded_response;
    }

    /**
     * Processa a resposta do registro de rastreamento
     *
     * @param array $response Resposta da API
     * @return array Dados processados
     */
    private function process_register_response($response) {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );

        if (isset($response['code']) && $response['code'] === 0) {
            $result['success'] = true;
            $result['message'] = __('Rastreamento registrado com sucesso.', 'wc-track17-rastreamento');
            
            if (isset($response['data']['accepted'])) {
                $result['data']['accepted'] = $response['data']['accepted'];
            }
            
            if (isset($response['data']['rejected'])) {
                $result['data']['rejected'] = $response['data']['rejected'];
            }
        } else {
            $result['message'] = isset($response['message']) ? $response['message'] : __('Erro desconhecido ao registrar rastreamento.', 'wc-track17-rastreamento');
        }

        return $result;
    }

    /**
     * Processa a resposta da consulta de rastreamento
     *
     * @param array $response Resposta da API
     * @return array Dados processados
     */
    private function process_tracking_response($response) {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );

        if (isset($response['code']) && $response['code'] === 0) {
            $result['success'] = true;
            $result['message'] = __('Informações de rastreamento obtidas com sucesso.', 'wc-track17-rastreamento');
            
            if (isset($response['data']['accepted'])) {
                $result['data'] = $response['data']['accepted'];
            }
        } else {
            $result['message'] = isset($response['message']) ? $response['message'] : __('Erro desconhecido ao consultar rastreamento.', 'wc-track17-rastreamento');
        }

        return $result;
    }

    /**
     * Determina o status do rastreamento baseado na resposta da API
     *
     * @param array $track_info Informações de rastreamento da API
     * @return string Status do rastreamento
     */
    public function determine_tracking_status($track_info) {
        if (empty($track_info) || !isset($track_info['latest_status'])) {
            return 'sem_informacoes';
        }

        $status = $track_info['latest_status']['status'] ?? '';
        $sub_status = $track_info['latest_status']['sub_status'] ?? '';

        // Mapeamento de status
        switch ($status) {
            case 'Delivered':
                return 'entregue';
                
            case 'InTransit':
                if ($sub_status === 'InTransit_CustomsRequiringInformation') {
                    // Verifica se é taxado ou devolvido baseado nos eventos
                    if (isset($track_info['tracking']['providers'][0]['events'])) {
                        foreach ($track_info['tracking']['providers'][0]['events'] as $event) {
                            $description = strtolower($event['description'] ?? '');
                            if (strpos($description, 'pagamento') !== false || strpos($description, 'taxa') !== false) {
                                return 'taxado';
                            }
                            if (strpos($description, 'devolvido') !== false || strpos($description, 'returned') !== false) {
                                return 'devolvido';
                            }
                        }
                    }
                    return 'em_transito';
                }
                return 'em_transito';
                
            case 'DeliveryFailure':
                return 'falha_entrega';
                
            case 'Exception':
                return 'excecao';
                
            default:
                // Verifica sub_status para casos específicos
                switch ($sub_status) {
                    case 'InfoReceived':
                        return 'postado';
                    case 'NotFound_Other':
                        return 'sem_informacoes';
                    case 'Delivered_Other':
                        return 'entregue';
                    default:
                        return 'sem_informacoes';
                }
        }
    }

    /**
     * Obtém a lista de transportadoras suportadas
     *
     * @return array Lista de transportadoras
     */
    public function get_supported_carriers() {
        // Lista básica de transportadoras mais comuns
        // Esta lista pode ser expandida ou obtida dinamicamente da API
        return array(
            '2151' => 'Correios',
            '101052' => 'Jadlog',
            '100457' => 'Loggi',
            '190271' => 'Cainiao',
            '191076' => 'Cainiao CN',
            '190047' => 'Anjun',
            '190438' => 'Imile',
            '100797' => 'J&T Brasil',
        );
    }

    /**
     * Valida se a chave da API está configurada e é válida
     *
     * @return bool|WP_Error True se válida, WP_Error se inválida
     */
    public function validate_api_key() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Chave da API não configurada.', 'wc-track17-rastreamento'));
        }

        // Testa a API com uma requisição simples
        $test_response = $this->register_tracking('TEST123456789', '2151');
        
        if (is_wp_error($test_response)) {
            return $test_response;
        }

        return true;
    }
}