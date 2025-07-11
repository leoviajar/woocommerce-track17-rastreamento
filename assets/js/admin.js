/**
 * JavaScript para o admin do WooCommerce Track17
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Inicializa as funcionalidades do admin
        WCTrack17Admin.init();
    });

    var WCTrack17Admin = {
        
        /**
         * Inicializa o admin
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initEnhancedSelects();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Atualização de rastreamento individual
            $(document).on('click', '#wc-track17-update-tracking', this.updateSingleTracking);
            
            // Atualização de todos os rastreamentos
            $(document).on('click', '#wc-track17-update-all', this.updateAllTracking);
            
            // Validação de código de rastreamento
            $(document).on('blur', '#_wc_track17_tracking_code', this.validateTrackingCode);
            
            // Auto-seleção de transportadora baseada no código
            $(document).on('input', '#_wc_track17_tracking_code', this.autoSelectCarrier);
            
            // Copiar código de rastreamento
            $(document).on('click', '.wc-track17-copy-code', this.copyTrackingCode);
            
            // Confirmação antes de ações destrutivas
            $(document).on('click', '.wc-track17-confirm', this.confirmAction);
        },

        /**
         * Atualiza rastreamento individual
         */
        updateSingleTracking: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var spinner = $('#wc-track17-spinner');
            var orderId = button.data('order-id') || $('input[name="post_ID"]').val();
            
            if (!orderId) {
                WCTrack17Admin.showNotice('Erro: ID do pedido não encontrado.', 'error');
                return;
            }
            
            // Estado de loading
            button.prop('disabled', true).text(wc_track17_admin.strings.updating);
            spinner.addClass('is-active');
            
            $.ajax({
                url: wc_track17_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_track17_update_single_tracking',
                    order_id: orderId,
                    nonce: wc_track17_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WCTrack17Admin.showNotice(response.data, 'success');
                        // Recarrega a página após 2 segundos
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        WCTrack17Admin.showNotice(wc_track17_admin.strings.update_error + ' ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    WCTrack17Admin.showNotice('Erro na requisição: ' + error, 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('Atualizar Rastreamento');
                    spinner.removeClass('is-active');
                }
            });
        },

        /**
         * Atualiza todos os rastreamentos
         */
        updateAllTracking: function(e) {
            e.preventDefault();
            
            if (!confirm(wc_track17_admin.strings.confirm_update_all)) {
                return;
            }
            
            var button = $(this);
            var originalText = button.text();
            
            // Estado de loading
            button.prop('disabled', true).text(wc_track17_admin.strings.updating);
            
            $.ajax({
                url: wc_track17_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_track17_update_all_tracking',
                    nonce: wc_track17_admin.nonce
                },
                timeout: 300000, // 5 minutos
                success: function(response) {
                    if (response.success) {
                        WCTrack17Admin.showNotice(response.data, 'success');
                    } else {
                        WCTrack17Admin.showNotice(wc_track17_admin.strings.update_error + ' ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout') {
                        WCTrack17Admin.showNotice('A operação está demorando mais que o esperado. Verifique o dashboard em alguns minutos.', 'warning');
                    } else {
                        WCTrack17Admin.showNotice('Erro na requisição: ' + error, 'error');
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Valida código de rastreamento
         */
        validateTrackingCode: function() {
            var input = $(this);
            var code = input.val().trim();
            var feedback = input.siblings('.wc-track17-validation-feedback');
            
            // Remove feedback anterior
            feedback.remove();
            
            if (code === '') {
                return;
            }
            
            // Validações básicas
            var isValid = true;
            var message = '';
            
            if (code.length < 8) {
                isValid = false;
                message = 'Código muito curto (mínimo 8 caracteres)';
            } else if (code.length > 30) {
                isValid = false;
                message = 'Código muito longo (máximo 30 caracteres)';
            } else if (!/^[A-Z0-9]+$/i.test(code)) {
                isValid = false;
                message = 'Código deve conter apenas letras e números';
            }
            
            // Adiciona feedback visual
            var feedbackClass = isValid ? 'success' : 'error';
            var feedbackIcon = isValid ? '✓' : '✗';
            
            if (!isValid) {
                input.after('<span class="wc-track17-validation-feedback ' + feedbackClass + '">' + feedbackIcon + ' ' + message + '</span>');
            }
        },

        /**
         * Auto-seleção de transportadora baseada no código
         */
        autoSelectCarrier: function() {
            var input = $(this);
            var code = input.val().trim().toUpperCase();
            var carrierSelect = $('#_wc_track17_carrier_code');
            
            if (code === '' || carrierSelect.length === 0) {
                return;
            }
            
            // Padrões de códigos de rastreamento
            var patterns = {
                '2151': [/^[A-Z]{2}\d{9}[A-Z]{2}$/, /^\d{13}$/], // Correios
                '101052': [/^JAD\d+$/], // Jadlog
                '100457': [/^LOG\d+$/], // Loggi
                '190271': [/^[A-Z0-9]{10,}$/], // Cainiao
                '3011': [/^[0-9]{10}$/], // DHL
                '100003': [/^\d{12}$/, /^\d{14}$/] // FedEx
            };
            
            // Verifica padrões
            for (var carrierId in patterns) {
                var carrierPatterns = patterns[carrierId];
                for (var i = 0; i < carrierPatterns.length; i++) {
                    if (carrierPatterns[i].test(code)) {
                        carrierSelect.val(carrierId).trigger('change');
                        return;
                    }
                }
            }
        },

        /**
         * Copia código de rastreamento
         */
        copyTrackingCode: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var code = button.data('code') || button.closest('.tracking-info').find('.tracking-code').text();
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function() {
                    WCTrack17Admin.showCopyFeedback(button);
                }).catch(function() {
                    WCTrack17Admin.fallbackCopyText(code, button);
                });
            } else {
                WCTrack17Admin.fallbackCopyText(code, button);
            }
        },

        /**
         * Fallback para copiar texto
         */
        fallbackCopyText: function(text, button) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                WCTrack17Admin.showCopyFeedback(button);
            } catch (err) {
                console.error('Erro ao copiar texto:', err);
            }
            
            document.body.removeChild(textArea);
        },

        /**
         * Mostra feedback de cópia
         */
        showCopyFeedback: function(button) {
            var originalText = button.text();
            button.text('Copiado!').addClass('copied');
            
            setTimeout(function() {
                button.text(originalText).removeClass('copied');
            }, 2000);
        },

        /**
         * Confirmação de ação
         */
        confirmAction: function(e) {
            var message = $(this).data('confirm') || 'Tem certeza que deseja continuar?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * Inicializa tooltips
         */
        initTooltips: function() {
            // Adiciona tooltips para elementos com data-tooltip
            $('[data-tooltip]').each(function() {
                var element = $(this);
                var tooltip = element.data('tooltip');
                
                element.on('mouseenter', function() {
                    WCTrack17Admin.showTooltip(element, tooltip);
                }).on('mouseleave', function() {
                    WCTrack17Admin.hideTooltip();
                });
            });
        },

        /**
         * Mostra tooltip
         */
        showTooltip: function(element, text) {
            var tooltip = $('<div class="wc-track17-tooltip-popup">' + text + '</div>');
            $('body').append(tooltip);
            
            var offset = element.offset();
            var elementHeight = element.outerHeight();
            var tooltipWidth = tooltip.outerWidth();
            var tooltipHeight = tooltip.outerHeight();
            
            tooltip.css({
                position: 'absolute',
                top: offset.top - tooltipHeight - 10,
                left: offset.left + (element.outerWidth() / 2) - (tooltipWidth / 2),
                zIndex: 9999
            });
        },

        /**
         * Esconde tooltip
         */
        hideTooltip: function() {
            $('.wc-track17-tooltip-popup').remove();
        },

        /**
         * Inicializa selects aprimorados
         */
        initEnhancedSelects: function() {
            // Aplica select2 se disponível
            if ($.fn.selectWoo) {
                $('#_wc_track17_carrier_code').selectWoo({
                    placeholder: 'Selecione uma transportadora',
                    allowClear: true
                });
            }
        },

        /**
         * Mostra notificação
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var notice = $('<div class="notice notice-' + type + ' is-dismissible wc-track17-notice"><p>' + message + '</p></div>');
            
            // Remove notificações anteriores
            $('.wc-track17-notice').remove();
            
            // Adiciona nova notificação
            if ($('.wrap h1').length) {
                $('.wrap h1').after(notice);
            } else {
                $('.wrap').prepend(notice);
            }
            
            // Auto-remove após 5 segundos
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
            
            // Adiciona funcionalidade de dismiss
            notice.on('click', '.notice-dismiss', function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            });
        },

        /**
         * Utilitários
         */
        utils: {
            /**
             * Debounce function
             */
            debounce: function(func, wait, immediate) {
                var timeout;
                return function() {
                    var context = this, args = arguments;
                    var later = function() {
                        timeout = null;
                        if (!immediate) func.apply(context, args);
                    };
                    var callNow = immediate && !timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                    if (callNow) func.apply(context, args);
                };
            },

            /**
             * Formata número
             */
            formatNumber: function(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            },

            /**
             * Valida email
             */
            isValidEmail: function(email) {
                var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
        }
    };

    // Expõe o objeto globalmente para uso em outros scripts
    window.WCTrack17Admin = WCTrack17Admin;

})(jQuery);

