# Implementação do Sistema de E-mails de Rastreamento - WooCommerce Track17

## Visão Geral

Este documento descreve a implementação completa do sistema de notificação por e-mail para códigos de rastreamento no plugin WooCommerce Track17 Rastreamento. A implementação segue as melhores práticas do WooCommerce e permite total personalização através do painel administrativo do WordPress.

## Arquivos Implementados

### 1. Classe de E-mail Personalizada
**Arquivo:** `includes/class-wc-track17-tracking-email.php`

Esta classe estende `WC_Email` e implementa todas as funcionalidades necessárias para o envio de e-mails de notificação de rastreamento.

### 2. Gerenciador de E-mails
**Arquivo:** `includes/class-wc-track17-email-manager.php`

Responsável por registrar a nova classe de e-mail no sistema do WooCommerce e gerenciar os triggers de envio.

### 3. Templates de E-mail
**Arquivos:**
- `templates/emails/tracking-code-notification.php` (HTML)
- `templates/emails/plain/tracking-code-notification.php` (Texto simples)

Templates que seguem o padrão visual dos e-mails nativos do WooCommerce.

## Funcionalidades Implementadas

### Envio Automático
- E-mail é enviado automaticamente quando um código de rastreamento é adicionado ao pedido
- Compatível com HPOS (High-Performance Order Storage)
- Previne envio duplicado para o mesmo código de rastreamento

### Personalização no Painel
- Configurações disponíveis em WooCommerce > Configurações > E-mails
- Permite editar assunto, cabeçalho e conteúdo adicional
- Opção para habilitar/desabilitar o e-mail
- Escolha do tipo de e-mail (HTML, texto simples ou multipart)

### Conteúdo do E-mail
- Saudação personalizada com nome do cliente
- Informações do pedido (número, data, código de rastreamento)
- Links para rastreamento (site próprio e 17Track)
- Detalhes completos do pedido
- Informações do cliente

## Como Usar

### Para Administradores

1. **Configuração Inicial:**
   - Acesse WooCommerce > Configurações > E-mails
   - Localize "Código de Rastreamento Disponível"
   - Clique em "Gerenciar" para configurar

2. **Personalização:**
   - Edite o assunto do e-mail
   - Personalize o cabeçalho
   - Adicione conteúdo adicional se necessário
   - Escolha o tipo de e-mail (HTML recomendado)

3. **Ativação:**
   - Marque "Habilitar esta notificação por e-mail"
   - Salve as configurações

### Para Desenvolvedores

#### Envio Manual de E-mail
```php
// Enviar e-mail manualmente
WC_Track17_Email_Manager::send_tracking_notification($order_id, $tracking_code);
```

#### Hooks Disponíveis
O sistema utiliza os seguintes hooks para detectar quando enviar e-mails:

- `updated_post_meta` - Para compatibilidade com posts
- `woocommerce_update_order` - Para HPOS

## Estrutura Técnica

### Fluxo de Funcionamento

1. **Detecção de Mudança:**
   - Sistema monitora mudanças no meta `_wc_track17_tracking_code`
   - Verifica se o código não está vazio

2. **Verificação de Duplicação:**
   - Compara com meta `_wc_track17_tracking_email_sent`
   - Evita envio duplicado para o mesmo código

3. **Preparação do E-mail:**
   - Carrega dados do pedido
   - Substitui variáveis no assunto/cabeçalho
   - Prepara conteúdo HTML e texto simples

4. **Envio:**
   - Utiliza sistema nativo do WooCommerce
   - Adiciona nota ao pedido
   - Atualiza meta para controle de duplicação

### Variáveis Disponíveis nos Templates

- `{order_number}` - Número do pedido
- `{order_date}` - Data do pedido
- `{tracking_code}` - Código de rastreamento
- `$order` - Objeto completo do pedido
- `$tracking_code` - Código de rastreamento
- `$email_heading` - Cabeçalho configurado
- `$additional_content` - Conteúdo adicional configurado

## Compatibilidade

### WooCommerce
- Versão mínima: 6.0
- Testado até: 8.5
- Compatível com HPOS

### WordPress
- Versão mínima: 5.0
- Testado até: 6.4

### PHP
- Versão mínima: 7.4

## Personalização Avançada

### Sobrescrevendo Templates
Os templates podem ser personalizados copiando-os para o tema ativo:

```
wp-content/themes/seu-tema/woocommerce/emails/tracking-code-notification.php
wp-content/themes/seu-tema/woocommerce/emails/plain/tracking-code-notification.php
```

### Filtros Disponíveis
```php
// Personalizar assunto padrão
add_filter('wc_track17_tracking_email_default_subject', function($subject) {
    return 'Seu pedido está a caminho!';
});

// Personalizar cabeçalho padrão
add_filter('wc_track17_tracking_email_default_heading', function($heading) {
    return 'Rastreamento Disponível';
});
```

## Resolução de Problemas

### E-mail não está sendo enviado
1. Verifique se o e-mail está habilitado nas configurações
2. Confirme se há um código de rastreamento válido no pedido
3. Verifique os logs de e-mail do WooCommerce

### E-mail sendo enviado múltiplas vezes
- O sistema possui proteção contra duplicação
- Se persistir, verifique se há conflitos com outros plugins

### Template não está sendo carregado
1. Verifique se os arquivos estão no diretório correto
2. Confirme as permissões dos arquivos
3. Limpe cache se estiver usando plugins de cache

## Considerações de Segurança

- Todos os dados são sanitizados antes do envio
- Templates utilizam funções de escape do WordPress
- Verificações de permissão antes do envio
- Proteção contra acesso direto aos arquivos

## Manutenção

### Atualizações Futuras
- Templates seguem versionamento para compatibilidade
- Estrutura modular facilita manutenção
- Documentação inline para desenvolvedores

### Logs
- Notas automáticas nos pedidos quando e-mails são enviados
- Integração com sistema de logs do WooCommerce

## Conclusão

A implementação do sistema de e-mails de rastreamento foi desenvolvida seguindo as melhores práticas do WooCommerce, garantindo:

- **Compatibilidade:** Funciona com versões atuais e futuras do WooCommerce
- **Flexibilidade:** Totalmente personalizável através do painel administrativo
- **Confiabilidade:** Sistema robusto com proteções contra duplicação
- **Usabilidade:** Interface familiar aos usuários do WooCommerce
- **Manutenibilidade:** Código bem estruturado e documentado

O sistema está pronto para uso em produção e pode ser facilmente estendido para funcionalidades adicionais no futuro.

