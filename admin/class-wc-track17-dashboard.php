<?php
/**
 * Classe do dashboard administrativo
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class WC_Track17_Dashboard {

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
        // Adiciona o menu do dashboard
        add_action('admin_menu', array($this, 'add_dashboard_menu'));
        
        // AJAX para atualizar estatísticas
        add_action('wp_ajax_wc_track17_update_stats', array($this, 'update_stats_ajax'));
        
        // AJAX para obter dados do gráfico
        add_action('wp_ajax_wc_track17_get_chart_data', array($this, 'get_chart_data_ajax'));
    }

    /**
     * Adiciona o menu do dashboard
     */
    public function add_dashboard_menu() {
        add_menu_page(
            __('Dashboard Rastreamento', 'wc-track17-rastreamento'),
            __('Dashboard Rastreamento', 'wc-track17-rastreamento'),
            'manage_woocommerce',
            'wc-track17-dashboard',
            array($this, 'dashboard_page_content'),
            'dashicons-location',
            56
        );
    }

    /**
     * Conteúdo da página do dashboard
     */
    public function dashboard_page_content() {
        $stats = $this->get_tracking_stats();
        ?>
        <div class="wrap wc-track17-dashboard">
            <div class="wc-track17-header">
                <h1><?php _e('Dashboard Rastreamento', 'wc-track17-rastreamento'); ?></h1>
                <button type="button" id="wc-track17-update-stats" class="button button-primary">
                    <?php _e('Atualizar Estatísticas', 'wc-track17-rastreamento'); ?>
                </button>
            </div>

            <div class="wc-track17-stats-grid">
                <!-- Card: Sem Rastreio -->
                <div class="wc-track17-stat-card sem-rastreio">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-dismiss"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['sem_rastreio']); ?></h3>
                        <p><?php _e('Sem Rastreio', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Sem Informações -->
                <div class="wc-track17-stat-card sem-informacoes">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['sem_informacoes']); ?></h3>
                        <p><?php _e('Sem Informações', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Postado -->
                <div class="wc-track17-stat-card postado">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-email"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['postado']); ?></h3>
                        <p><?php _e('Postado', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Em Trânsito -->
                <div class="wc-track17-stat-card em-transito">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-truck"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['em_transito']); ?></h3>
                        <p><?php _e('Em Trânsito', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Taxado -->
                <div class="wc-track17-stat-card taxado">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['taxado']); ?></h3>
                        <p><?php _e('Taxado', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Falha de Entrega -->
                <div class="wc-track17-stat-card falha-entrega">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['falha_entrega']); ?></h3>
                        <p><?php _e('Falha de Entrega', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Aguardando Retirada -->
                <div class="wc-track17-stat-card aguardando-retirada">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-store"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['aguardando_retirada']); ?></h3>
                        <p><?php _e('Aguardando Retirada', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Devolvido -->
                <div class="wc-track17-stat-card devolvido">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-undo"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['devolvido']); ?></h3>
                        <p><?php _e('Devolvido', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>

                <!-- Card: Entregue -->
                <div class="wc-track17-stat-card entregue">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format_i18n($stats['entregue']); ?></h3>
                        <p><?php _e('Entregue', 'wc-track17-rastreamento'); ?></p>
                    </div>
                </div>
            </div>

            <div class="wc-track17-charts-container">
                <div class="wc-track17-chart-wrapper">
                    <h2><?php _e('Top Transportadoras', 'wc-track17-rastreamento'); ?></h2>
                    <canvas id="wc-track17-carriers-chart"></canvas>
                </div>

                <div class="wc-track17-chart-wrapper">
                    <h2><?php _e('Status de Rastreamento', 'wc-track17-rastreamento'); ?></h2>
                    <canvas id="wc-track17-status-chart"></canvas>
                </div>
            </div>

            <div class="wc-track17-info-section">
                <h2><?php _e('Informações do Sistema', 'wc-track17-rastreamento'); ?></h2>
                <div class="wc-track17-info-grid">
                    <div class="info-item">
                        <strong><?php _e('Total de Pedidos:', 'wc-track17-rastreamento'); ?></strong>
                        <?php echo number_format_i18n($stats['total_orders']); ?>
                    </div>
                    <div class="info-item">
                        <strong><?php _e('Pedidos com Rastreio:', 'wc-track17-rastreamento'); ?></strong>
                        <?php echo number_format_i18n($stats['orders_with_tracking']); ?>
                    </div>
                    <div class="info-item">
                        <strong><?php _e('Última Atualização:', 'wc-track17-rastreamento'); ?></strong>
                        <?php 
                        $last_update = get_option('wc_track17_last_stats_update', '');
                        echo $last_update ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update)) : __('Nunca', 'wc-track17-rastreamento');
                        ?>
                    </div>
                    <div class="info-item">
                        <strong><?php _e('Chave da API:', 'wc-track17-rastreamento'); ?></strong>
                        <?php 
                        $api_key = get_option('track17_api_key', '');
                        echo $api_key ? __('Configurada', 'wc-track17-rastreamento') : '<span style="color: red;">' . __('Não configurada', 'wc-track17-rastreamento') . '</span>';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Inicializa os gráficos
            initCharts();
            
            // Atualizar estatísticas
            $('#wc-track17-update-stats').on('click', function() {
                var button = $(this);
                var originalText = button.text();
                
                button.prop('disabled', true).text('<?php _e('Atualizando...', 'wc-track17-rastreamento'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_track17_update_stats',
                        nonce: '<?php echo wp_create_nonce('wc_track17_dashboard'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Erro ao atualizar estatísticas:', 'wc-track17-rastreamento'); ?> ' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Erro na requisição.', 'wc-track17-rastreamento'); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            function initCharts() {
                // Gráfico de transportadoras
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_track17_get_chart_data',
                        chart_type: 'carriers',
                        nonce: '<?php echo wp_create_nonce('wc_track17_dashboard'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            createCarriersChart(response.data);
                        }
                    }
                });
                
                // Gráfico de status
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_track17_get_chart_data',
                        chart_type: 'status',
                        nonce: '<?php echo wp_create_nonce('wc_track17_dashboard'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            createStatusChart(response.data);
                        }
                    }
                });
            }
            
            function createCarriersChart(data) {
                var ctx = document.getElementById('wc-track17-carriers-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: '<?php _e('Número de Pedidos', 'wc-track17-rastreamento'); ?>',
                            data: data.values,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 159, 64, 0.8)',
                                'rgba(199, 199, 199, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(199, 199, 199, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
            
            function createStatusChart(data) {
                var ctx = document.getElementById('wc-track17-status-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                '#e74c3c', // sem_rastreio
                                '#95a5a6', // sem_informacoes
                                '#3498db', // postado
                                '#f39c12', // em_transito
                                '#e67e22', // taxado
                                '#e74c3c', // falha_entrega
                                '#9b59b6', // aguardando_retirada
                                '#34495e', // devolvido
                                '#27ae60'  // entregue
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Obtém as estatísticas de rastreamento
     */
    private function get_tracking_stats() {
        // Busca todos os pedidos relevantes
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('processing', 'on-hold', 'completed', 'pending')
        ));

        $stats = array(
            'total_orders' => count($orders),
            'orders_with_tracking' => 0,
            'sem_rastreio' => 0,
            'sem_informacoes' => 0,
            'postado' => 0,
            'em_transito' => 0,
            'entregue' => 0,
            'taxado' => 0,
            'devolvido' => 0,
            'falha_entrega' => 0,
            'aguardando_retirada' => 0,
            'excecao' => 0
        );

        foreach ($orders as $order) {
            $tracking_code = $order->get_meta('_wc_track17_tracking_code');
            $tracking_status = $order->get_meta('_wc_track17_tracking_status');

            if (empty($tracking_code)) {
                $stats['sem_rastreio']++;
            } else {
                $stats['orders_with_tracking']++;
                
                if (empty($tracking_status)) {
                    $stats['sem_informacoes']++;
                } else {
                    $stats[$tracking_status] = isset($stats[$tracking_status]) ? $stats[$tracking_status] + 1 : 1;
                }
            }
        }

        return $stats;
    }

    /**
     * AJAX para atualizar estatísticas
     */
    public function update_stats_ajax() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_track17_dashboard')) {
            wp_send_json_error(__('Erro de segurança.', 'wc-track17-rastreamento'));
        }
        
        // Verifica permissões
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permissão negada.', 'wc-track17-rastreamento'));
        }
        
        // Atualiza todos os rastreamentos
        $admin = WC_Track17_Admin::get_instance();
        $result = $admin->update_all_tracking();
        
        // Atualiza timestamp da última atualização
        update_option('wc_track17_last_stats_update', current_time('mysql'));
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX para obter dados do gráfico
     */
    public function get_chart_data_ajax() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_track17_dashboard')) {
            wp_send_json_error(__('Erro de segurança.', 'wc-track17-rastreamento'));
        }
        
        // Verifica permissões
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permissão negada.', 'wc-track17-rastreamento'));
        }
        
        $chart_type = sanitize_text_field($_POST['chart_type']);
        
        if ($chart_type === 'carriers') {
            $data = $this->get_carriers_chart_data();
        } elseif ($chart_type === 'status') {
            $data = $this->get_status_chart_data();
        } else {
            wp_send_json_error(__('Tipo de gráfico inválido.', 'wc-track17-rastreamento'));
        }
        
        wp_send_json_success($data);
    }

    /**
     * Obtém dados do gráfico de transportadoras
     */
    private function get_carriers_chart_data() {
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('processing', 'on-hold', 'completed', 'pending'),
            'meta_query' => array(
                array(
                    'key' => '_wc_track17_carrier_code',
                    'compare' => 'EXISTS'
                )
            )
        ));

        $carriers_count = array();
        $carriers = WC_Track17_API::get_instance()->get_supported_carriers();

        foreach ($orders as $order) {
            $carrier_code = $order->get_meta('_wc_track17_carrier_code');
            
            if (!empty($carrier_code) && isset($carriers[$carrier_code])) {
                $carrier_name = $carriers[$carrier_code];
                $carriers_count[$carrier_name] = isset($carriers_count[$carrier_name]) ? $carriers_count[$carrier_name] + 1 : 1;
            }
        }

        // Ordena por quantidade e pega os top 7
        arsort($carriers_count);
        $top_carriers = array_slice($carriers_count, 0, 7, true);

        return array(
            'labels' => array_keys($top_carriers),
            'values' => array_values($top_carriers)
        );
    }

    /**
     * Obtém dados do gráfico de status
     */
    private function get_status_chart_data() {
        $stats = $this->get_tracking_stats();
        
        // Remove total_orders e orders_with_tracking dos dados do gráfico
        unset($stats['total_orders']);
        unset($stats['orders_with_tracking']);
        
        // Filtra apenas status com valores > 0
        $filtered_stats = array_filter($stats, function($value) {
            return $value > 0;
        });
        
        $order_meta = WC_Track17_Order_Meta::get_instance();
        $labels = array();
        
        foreach (array_keys($filtered_stats) as $status) {
            $labels[] = $order_meta->get_status_label($status);
        }
        
        return array(
            'labels' => $labels,
            'values' => array_values($filtered_stats)
        );
    }
}

