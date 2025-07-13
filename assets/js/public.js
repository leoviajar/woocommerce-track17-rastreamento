/**
 * JavaScript para o frontend público Track17
 * MODIFICADO: Suporte a busca por telefone além de e-mail
 * CORRIGIDO: Erro 'Cannot read properties of undefined (reading 'trim')'
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
            $(document).on("input", "#tracking-code-input, #email-input, #order-number-input", this.validateTrackingInput);
            
            // Limpar resultado ao focar no input
            $(document).on("focus", "#tracking-code-input, #email-input, #order-number-input", this.clearPreviousResult);
            
            // Enter key no input
            $(document).on("keypress", "#tracking-code-input, #email-input, #order-number-input", this.handleEnterKey);
        },

        /**
         * Manipula o formulário de rastreamento
         * MODIFICADO: Agora suporta busca por telefone além de e-mail
         * CORRIGIDO: Adicionadas verificações de existência dos elementos
         */
        handleTrackingForm: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var trackingCodeInput = form.find("#tracking-code-input");
            var emailInput = form.find("#email-input");
            var orderNumberInput = form.find("#order-number-input");
            var button = form.find("button[type=\"submit\"]");
            var resultDiv = $("#tracking-result");

            // CORREÇÃO: Verificar se os elementos existem antes de acessar .val()
            var trackingCode = trackingCodeInput.length ? trackingCodeInput.val().trim() : '';
            var emailOrPhone = emailInput.length ? emailInput.val().trim() : '';
            var orderNumber = orderNumberInput.length ? orderNumberInput.val().trim() : '';
            
            // Validação: ou código de rastreamento OU e-mail/telefone E número do pedido
            if ( ( !trackingCode && (!emailOrPhone || !orderNumber) ) || ( trackingCode && (emailOrPhone || orderNumber) ) ) {
                WCTrack17Public.showError(wc_track17_public.strings.invalid_input);
                return;
            }
            
            // NOVO: Validação específica para telefone/e-mail
            if (emailOrPhone && !WCTrack17Public.isValidEmailOrPhone(emailOrPhone)) {
                WCTrack17Public.showError('Por favor, insira um e-mail ou telefone válido.');
                return;
            }
            
            // Estado de loading
            button.prop("disabled", true).text(wc_track17_public.strings.searching);
            
            // CORREÇÃO: Verificar se os elementos existem antes de desabilitá-los
            if (trackingCodeInput.length) trackingCodeInput.prop("disabled", true);
            if (emailInput.length) emailInput.prop("disabled", true);
            if (orderNumberInput.length) orderNumberInput.prop("disabled", true);
            
            resultDiv.hide();
            
            var requestData = {};
            if (trackingCode) {
                requestData.tracking_code = trackingCode;
            } else {
                // MODIFICADO: Agora envia como "email" mas pode ser telefone também
                requestData.email = emailOrPhone;
                requestData.order_number = orderNumber;
            }

            // Requisição AJAX
            $.ajax({
                url: wc_track17_public.api_url,
                type: "POST",
                contentType: "application/json; charset=utf-8",
                data: JSON.stringify(requestData),
                beforeSend: function(xhr) {
                    xhr.setRequestHeader("X-WP-Nonce", wc_track17_public.nonce);
                },
                success: function(response) {
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
                    button.prop("disabled", false).text(button.data("original-text") || "Localizar");
                    
                    // CORREÇÃO: Verificar se os elementos existem antes de reabilitá-los
                    if (trackingCodeInput.length) trackingCodeInput.prop("disabled", false);
                    if (emailInput.length) emailInput.prop("disabled", false);
                    if (orderNumberInput.length) orderNumberInput.prop("disabled", false);
                }
            });
        },

        /**
         * NOVA FUNÇÃO: Valida se o input é um e-mail ou telefone válido
         */
        isValidEmailOrPhone: function(value) {
            // Verifica se é um e-mail válido
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(value)) {
                return true;
            }
            
            // Verifica se é um telefone válido (aceita vários formatos)
            var phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
            var cleanPhone = value.replace(/[^\d]/g, '');
            
            // Telefone deve ter pelo menos 8 dígitos e no máximo 15
            if (phoneRegex.test(value) && cleanPhone.length >= 8 && cleanPhone.length <= 15) {
                return true;
            }
            
            return false;
        },

        /**
         * NOVA FUNÇÃO: Detecta se o valor é um e-mail ou telefone
         */
        detectInputType: function(value) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(value)) {
                return 'email';
            }
            
            var phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
            if (phoneRegex.test(value)) {
                return 'phone';
            }
            
            return 'unknown';
        },

        /**
         * NOVA FUNÇÃO: Formata telefone para exibição
         */
        formatPhoneDisplay: function(phone) {
            // Remove caracteres não numéricos
            var clean = phone.replace(/[^\d]/g, '');
            
            // Formata telefone brasileiro
            if (clean.length === 11) {
                return '(' + clean.substring(0, 2) + ') ' + clean.substring(2, 7) + '-' + clean.substring(7);
            } else if (clean.length === 10) {
                return '(' + clean.substring(0, 2) + ') ' + clean.substring(2, 6) + '-' + clean.substring(6);
            }
            
            return phone; // Retorna original se não conseguir formatar
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
         * Limpa resultado anterior
         */
        clearPreviousResult: function() {
            $('#tracking-result').hide();
            $(this).removeClass('invalid valid');
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
            
            // NOVO: Adiciona dicas visuais para o campo e-mail/telefone
            $('#email-input').on('input', function() {
                var value = $(this).val().trim();
                var placeholder = $(this).attr('placeholder');
                
                if (value.length > 0) {
                    var type = WCTrack17Public.detectInputType(value);
                    if (type === 'email') {
                        $(this).attr('placeholder', 'E-mail detectado');
                    } else if (type === 'phone') {
                        $(this).attr('placeholder', 'Telefone detectado');
                    }
                } else {
                    $(this).attr('placeholder', 'E-mail / Telefone');
                }
            });
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

