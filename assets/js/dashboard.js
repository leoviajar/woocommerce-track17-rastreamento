/**
 * JavaScript para o dashboard Track17
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        WCTrack17Dashboard.init();
    });

    var WCTrack17Dashboard = {
        
        charts: {},
        
        /**
         * Inicializa o dashboard
         */
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.startAutoRefresh();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Atualização de estatísticas
            $(document).on('click', '#wc-track17-update-stats', this.updateStats);
            
            // Refresh dos gráficos
            $(document).on('click', '.wc-track17-refresh-chart', this.refreshChart);
            
            // Filtros de período
            $(document).on('change', '#wc-track17-period-filter', this.filterByPeriod);
            
            // Export de dados
            $(document).on('click', '.wc-track17-export', this.exportData);
            
            // Redimensionamento da janela
            $(window).on('resize', this.handleResize.bind(this));
        },

        /**
         * Atualiza estatísticas
         */
        updateStats: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var originalText = button.text();
            
            // Estado de loading
            button.prop('disabled', true).text('Atualizando...');
            $('.wc-track17-stats-grid').addClass('wc-track17-loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_track17_update_stats',
                    nonce: $('#wc-track17-dashboard-nonce').val()
                },
                timeout: 300000, // 5 minutos
                success: function(response) {
                    if (response.success) {
                        WCTrack17Dashboard.showNotice(response.data, 'success');
                        // Recarrega a página após 2 segundos
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        WCTrack17Dashboard.showNotice('Erro ao atualizar: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout') {
                        WCTrack17Dashboard.showNotice('A atualização está demorando mais que o esperado. Verifique novamente em alguns minutos.', 'warning');
                    } else {
                        WCTrack17Dashboard.showNotice('Erro na requisição: ' + error, 'error');
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                    $('.wc-track17-stats-grid').removeClass('wc-track17-loading');
                }
            });
        },

        /**
         * Inicializa os gráficos
         */
        initCharts: function() {
            this.initCarriersChart();
            this.initStatusChart();
        },

        /**
         * Inicializa gráfico de transportadoras
         */
        initCarriersChart: function() {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_track17_get_chart_data',
                    chart_type: 'carriers',
                    nonce: $('#wc-track17-dashboard-nonce').val()
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.createCarriersChart(response.data);
                    }
                },
                error: function() {
                    console.error('Erro ao carregar dados do gráfico de transportadoras');
                }
            });
        },

        /**
         * Inicializa gráfico de status
         */
        initStatusChart: function() {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_track17_get_chart_data',
                    chart_type: 'status',
                    nonce: $('#wc-track17-dashboard-nonce').val()
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.createStatusChart(response.data);
                    }
                },
                error: function() {
                    console.error('Erro ao carregar dados do gráfico de status');
                }
            });
        },

        /**
         * Cria gráfico de transportadoras
         */
        createCarriersChart: function(data) {
            var ctx = document.getElementById('wc-track17-carriers-chart');
            
            if (!ctx) {
                return;
            }
            
            // Destrói gráfico anterior se existir
            if (this.charts.carriers) {
                this.charts.carriers.destroy();
            }
            
            this.charts.carriers = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Número de Pedidos',
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
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 6,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return context[0].label;
                                },
                                label: function(context) {
                                    return context.parsed.y + ' pedidos';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return Number.isInteger(value) ? value : '';
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        },

        /**
         * Cria gráfico de status
         */
        createStatusChart: function(data) {
            var ctx = document.getElementById('wc-track17-status-chart');
            
            if (!ctx) {
                return;
            }
            
            // Destrói gráfico anterior se existir
            if (this.charts.status) {
                this.charts.status.destroy();
            }
            
            this.charts.status = new Chart(ctx, {
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
                        ],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverBorderWidth: 3,
                        hoverBorderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var total = context.dataset.data.reduce(function(a, b) {
                                        return a + b;
                                    }, 0);
                                    var percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeOutQuart'
                    },
                    cutout: '60%'
                }
            });
        },

        /**
         * Refresh de gráfico específico
         */
        refreshChart: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var chartType = button.data('chart');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Atualizando...');
            
            if (chartType === 'carriers') {
                WCTrack17Dashboard.initCarriersChart();
            } else if (chartType === 'status') {
                WCTrack17Dashboard.initStatusChart();
            }
            
            setTimeout(function() {
                button.prop('disabled', false).text(originalText);
            }, 2000);
        },

        /**
         * Filtro por período
         */
        filterByPeriod: function() {
            var period = $(this).val();
            
            // Implementar filtro por período
            console.log('Filtrar por período:', period);
            
            // Recarregar gráficos com novo período
            WCTrack17Dashboard.initCharts();
        },

        /**
         * Export de dados
         */
        exportData: function(e) {
            e.preventDefault();
            
            var format = $(this).data('format') || 'csv';
            var type = $(this).data('type') || 'stats';
            
            // Implementar export
            console.log('Exportar dados:', format, type);
            
            WCTrack17Dashboard.showNotice('Funcionalidade de export em desenvolvimento.', 'info');
        },

        /**
         * Redimensionamento da janela
         */
        handleResize: function() {
            // Redimensiona gráficos
            Object.keys(this.charts).forEach(function(key) {
                if (WCTrack17Dashboard.charts[key]) {
                    WCTrack17Dashboard.charts[key].resize();
                }
            });
        },

        /**
         * Auto-refresh do dashboard
         */
        startAutoRefresh: function() {
            // Auto-refresh a cada 5 minutos
            setInterval(function() {
                WCTrack17Dashboard.refreshStats();
            }, 300000);
        },

        /**
         * Refresh silencioso das estatísticas
         */
        refreshStats: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_track17_get_stats',
                    nonce: $('#wc-track17-dashboard-nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        WCTrack17Dashboard.updateStatsDisplay(response.data);
                    }
                },
                error: function() {
                    console.log('Erro no auto-refresh das estatísticas');
                }
            });
        },

        /**
         * Atualiza display das estatísticas
         */
        updateStatsDisplay: function(stats) {
            $('.wc-track17-stat-card').each(function() {
                var card = $(this);
                var statType = card.attr('class').match(/wc-track17-stat-card\s+([^\s]+)/);
                
                if (statType && statType[1] && stats[statType[1]] !== undefined) {
                    var value = stats[statType[1]];
                    card.find('.stat-content h3').text(WCTrack17Dashboard.formatNumber(value));
                }
            });
        },

        /**
         * Mostra notificação
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var notice = $('<div class="notice notice-' + type + ' is-dismissible wc-track17-dashboard-notice"><p>' + message + '</p></div>');
            
            // Remove notificações anteriores
            $('.wc-track17-dashboard-notice').remove();
            
            // Adiciona nova notificação
            $('.wc-track17-header').after(notice);
            
            // Auto-remove após 5 segundos
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        },

        /**
         * Formata número
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        },

        /**
         * Utilitários
         */
        utils: {
            /**
             * Gera cores aleatórias para gráficos
             */
            generateColors: function(count) {
                var colors = [];
                var hueStep = 360 / count;
                
                for (var i = 0; i < count; i++) {
                    var hue = i * hueStep;
                    colors.push('hsl(' + hue + ', 70%, 60%)');
                }
                
                return colors;
            },

            /**
             * Converte dados para CSV
             */
            toCSV: function(data, headers) {
                var csv = headers.join(',') + '\n';
                
                data.forEach(function(row) {
                    csv += row.join(',') + '\n';
                });
                
                return csv;
            },

            /**
             * Download de arquivo
             */
            downloadFile: function(content, filename, contentType) {
                var blob = new Blob([content], { type: contentType });
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        }
    };

    // Expõe o objeto globalmente
    window.WCTrack17Dashboard = WCTrack17Dashboard;

})(jQuery);

