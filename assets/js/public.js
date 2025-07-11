/**
 * JavaScript para o frontend público Track17
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        WCTrack17Public.init();
    });

    var WCTrack17Public = {
        
        /**
         * Inicializa o frontend público
         */
        init: function() {
            this.bindEvents();
            this.initTrackingForms();
            this.initCopyButtons();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Formulário de rastreamento
            $(document).on('submit', '#wc-track17-public-form', this.handleTrackingForm);
            
            // Botões de copiar código
            $(document).on('click', '.copy-code', this.copyTrackingCode);
            
            // Validação em tempo real
            $(document).on('input', '#tracking-code-input', this.validateTrackingInput);
            
            // Limpar resultado ao focar no input
            $(document).on('focus', '#tracking-code-input', this.clearPreviousResult);
            
            // Enter key no input
            $(document).on('keypress', '#tracking-code-input', this.handleEnterKey);
        },

        /**
         * Manipula o formulário de rastreamento
         */
        handleTrackingForm: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var input = form.find('#tracking-code-input');
            var button = form.find('button[type="submit"]');
            var resultDiv = $('#tracking-result');
            var trackingCode = input.val().trim();
            
            // Validação básica
            if (!trackingCode) {
                WCTrack17Public.showError(wc_track17_public.strings.invalid_code);
                input.focus();
                return;
            }
            
            // Estado de loading
            button.prop('disabled', true).text(wc_track17_public.strings.searching);
            input.prop('disabled', true);
            resultDiv.hide();
            
            // Requisição AJAX
            $.ajax({
                url: wc_track17_public.api_url,
                type: 'POST',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({
                    tracking_code: trackingCode
                }),
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wc_track17_public.nonce);
                },
                success: function(response) {
                    // A API REST já retorna o objeto de dados diretamente em caso de sucesso
                    // A função showTrackingResult espera um objeto 'data', que é o próprio 'response' aqui.
                    WCTrack17Public.showTrackingResult(response);
                },
                error: function(jqXHR) {
                    var errorMessage = wc_track17_public.strings.error;
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }
                    WCTrack17Public.showError(errorMessage);
                },
                complete: function() {
                    button.prop('disabled', false).text(form.find('button[type="submit"]').data('original-text') || 'Rastrear');
                    input.prop('disabled', false);
                }
            });
        },

        /**
         * MODIFICADO: Mostra resultado do rastreamento com timeline
         */
        showTrackingResult: function(data) {
            var resultDiv = $('#tracking-result');
            var html = '';
            
            html += '<div class="tracking-result success">';
            html += '<div class="tracking-header">';
            html += '<h4>Informações do Rastreamento</h4>';
            html += '<p><strong>Pedido:</strong> #' + data.order_number + '</p>';
            html += '<p><strong>Código:</strong> <span class="tracking-code">' + data.tracking_code + '</span></p>';
            
            if (data.tracking_status_label) {
                html += '<p><strong>Status Atual:</strong> <span class="status status-' + data.tracking_status + '">' + data.tracking_status_label + '</span></p>';
            }
            
            if (data.carrier_name) {
                html += '<p><strong>Transportadora:</strong> ' + data.carrier_name + '</p>';
            }
            
            html += '</div>';
            
            // NOVO: Exibe a timeline se disponível
            if (data.timeline && data.timeline.length > 0) {
                html += '<div class="tracking-timeline">';
                html += '<h5>Histórico de Rastreamento</h5>';
                html += '<div class="timeline-container">';
                
                for (var i = 0; i < data.timeline.length; i++) {
                    var event = data.timeline[i];
                    var isFirst = i === 0;
                    
                    html += '<div class="timeline-item ' + (isFirst ? 'timeline-item-current' : '') + '">';
                    html += '<div class="timeline-marker timeline-marker-' + event.status + '"></div>';
                    html += '<div class="timeline-content">';
                    html += '<div class="timeline-date">' + event.date + (event.time ? ' às ' + event.time : '') + '</div>';
                    html += '<div class="timeline-description">' + event.description + '</div>';
                    if (event.location) {
                        html += '<div class="timeline-location">' + event.location + '</div>';
                    }
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '</div>';
                html += '</div>';
            } else if (data.last_update) {
                // Fallback para quando não há timeline
                html += '<div class="tracking-simple">';
                html += '<p><strong>Última Atualização:</strong> ' + data.last_update + '</p>';
                html += '</div>';
            }
            
            html += '</div>';
            
            resultDiv.html(html).show();
            
            
            // Scroll suave para o resultado
            $('html, body').animate({
                scrollTop: resultDiv.offset().top - 20
            }, 500);
        },

        /**
         * Mostra erro
         */
        showError: function(message) {
            var resultDiv = $('#tracking-result');
            var html = '<div class="tracking-result error"><p>' + message + '</p></div>';
            
            resultDiv.html(html).show();
        },

        /**
         * Copia código de rastreamento
         */
        copyTrackingCode: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var code = button.data('code') || button.closest('.tracking-info').find('.code').text();
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function() {
                    WCTrack17Public.showCopyFeedback(button);
                }).catch(function() {
                    WCTrack17Public.fallbackCopyText(code, button);
                });
            } else {
                WCTrack17Public.fallbackCopyText(code, button);
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
                WCTrack17Public.showCopyFeedback(button);
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
         * Valida input de rastreamento
         */
        validateTrackingInput: function() {
            var input = $(this);
            var value = input.val().trim();
            var feedback = input.siblings('.validation-feedback');
            
            // Remove feedback anterior
            feedback.remove();
            
            if (value === '') {
                return;
            }
            
            // Validações básicas
            var isValid = true;
            var message = '';
            
            if (value.length < 8) {
                isValid = false;
                message = 'Código muito curto';
            } else if (value.length > 30) {
                isValid = false;
                message = 'Código muito longo';
            } else if (!/^[A-Z0-9]+$/i.test(value)) {
                isValid = false;
                message = 'Apenas letras e números são permitidos';
            }
            
            // Adiciona feedback visual
            if (!isValid) {
                input.after('<span class="validation-feedback error">' + message + '</span>');
                input.addClass('invalid');
            } else {
                input.removeClass('invalid');
            }
        },

        /**
         * Limpa resultado anterior
         */
        clearPreviousResult: function() {
            $('#tracking-result').hide();
            $(this).removeClass('invalid');
            $(this).siblings('.validation-feedback').remove();
        },

        /**
         * Manipula tecla Enter
         */
        handleEnterKey: function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $(this).closest('form').submit();
            }
        },

        /**
         * Inicializa formulários de rastreamento
         */
        initTrackingForms: function() {
            // Salva texto original dos botões
            $('.tracking-form button[type="submit"]').each(function() {
                var button = $(this);
                button.data('original-text', button.text());
            });
            
            // Auto-focus no primeiro input
            var firstInput = $('.tracking-form input[type="text"]:first');
            if (firstInput.length && !this.isMobile()) {
                setTimeout(function() {
                    firstInput.focus();
                }, 500);
            }
        },

        /**
         * Inicializa botões de copiar
         */
        initCopyButtons: function() {
            // Adiciona tooltip aos botões de copiar
            $('.copy-code').each(function() {
                var button = $(this);
                button.attr('title', 'Clique para copiar');
            });
        },

        /**
         * Detecta se é mobile
         */
        isMobile: function() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },

        /**
         * Formata código de rastreamento
         */
        formatTrackingCode: function(code) {
            // Remove espaços e converte para maiúsculo
            code = code.replace(/\s/g, '').toUpperCase();
            
            // Adiciona espaços para códigos dos Correios (formato BR123456789BR)
            if (/^[A-Z]{2}\d{9}[A-Z]{2}$/.test(code)) {
                return code.substring(0, 2) + ' ' + code.substring(2, 11) + ' ' + code.substring(11);
            }
            
            return code;
        },

        /**
         * Detecta transportadora pelo código
         */
        detectCarrier: function(code) {
            code = code.toUpperCase();
            
            // Padrões de códigos
            if (/^[A-Z]{2}\d{9}[A-Z]{2}$/.test(code) || /^\d{13}$/.test(code)) {
                return 'Correios';
            } else if (/^JAD\d+$/.test(code)) {
                return 'Jadlog';
            } else if (/^LOG\d+$/.test(code)) {
                return 'Loggi';
            } else if (/^\d{10}$/.test(code)) {
                return 'DHL';
            } else if (/^\d{12}$/.test(code) || /^\d{14}$/.test(code)) {
                return 'FedEx';
            }
            
            return 'Desconhecida';
        },

        /**
         * Animações
         */
        animations: {
            /**
             * Fade in
             */
            fadeIn: function(element, duration) {
                duration = duration || 300;
                element.hide().fadeIn(duration);
            },

            /**
             * Slide down
             */
            slideDown: function(element, duration) {
                duration = duration || 300;
                element.hide().slideDown(duration);
            },

            /**
             * Bounce
             */
            bounce: function(element) {
                element.addClass('bounce');
                setTimeout(function() {
                    element.removeClass('bounce');
                }, 1000);
            }
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
             * Scroll suave
             */
            smoothScroll: function(target, offset) {
                offset = offset || 0;
                $('html, body').animate({
                    scrollTop: $(target).offset().top - offset
                }, 500);
            },

            /**
             * Verifica se elemento está visível
             */
            isInViewport: function(element) {
                var elementTop = element.offset().top;
                var elementBottom = elementTop + element.outerHeight();
                var viewportTop = $(window).scrollTop();
                var viewportBottom = viewportTop + $(window).height();
                
                return elementBottom > viewportTop && elementTop < viewportBottom;
            }
        }
    };

    // Expõe o objeto globalmente
    window.WCTrack17Public = WCTrack17Public;

})(jQuery);

