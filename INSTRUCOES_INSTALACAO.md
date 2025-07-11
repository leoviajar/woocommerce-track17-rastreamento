# Instruções de Instalação - Sistema de E-mails de Rastreamento

## Pré-requisitos

Antes de instalar as novas funcionalidades, certifique-se de que:

- WordPress 5.0 ou superior está instalado
- WooCommerce 6.0 ou superior está ativo
- PHP 7.4 ou superior está configurado
- O plugin WooCommerce Track17 Rastreamento base está funcionando

## Instalação

### Passo 1: Backup
**IMPORTANTE:** Sempre faça backup do seu site antes de qualquer atualização.

```bash
# Backup dos arquivos do plugin
cp -r wp-content/plugins/woocommerce-track17-rastreamento wp-content/plugins/woocommerce-track17-rastreamento-backup

# Backup do banco de dados (via wp-cli)
wp db export backup-$(date +%Y%m%d).sql
```

### Passo 2: Atualização dos Arquivos

1. **Substitua os arquivos existentes** pelos novos arquivos fornecidos:
   - `woocommerce-track17-rastreamento.php` (arquivo principal atualizado)
   - `includes/class-wc-track17-tracking-email.php` (novo)
   - `includes/class-wc-track17-email-manager.php` (novo)

2. **Adicione os novos diretórios e templates:**
   ```
   templates/
   ├── emails/
   │   ├── tracking-code-notification.php
   │   └── plain/
   │       └── tracking-code-notification.php
   ```

### Passo 3: Verificação da Instalação

1. **Acesse o painel administrativo** do WordPress
2. **Vá para Plugins** e verifique se o plugin está ativo
3. **Navegue para WooCommerce > Configurações > E-mails**
4. **Procure por "Código de Rastreamento Disponível"** na lista de e-mails

Se você vir o novo tipo de e-mail listado, a instalação foi bem-sucedida!

## Configuração Inicial

### Passo 1: Configurar o E-mail

1. **Acesse WooCommerce > Configurações > E-mails**
2. **Clique em "Gerenciar"** ao lado de "Código de Rastreamento Disponível"
3. **Configure as seguintes opções:**

   - **Habilitar/Desabilitar:** Marque para ativar o e-mail
   - **Assunto:** Personalize conforme necessário (padrão: "Seu pedido #{order_number} agora pode ser rastreado")
   - **Cabeçalho do E-mail:** Personalize o título principal (padrão: "Código de Rastreamento Disponível")
   - **Conteúdo Adicional:** Adicione texto extra se desejar
   - **Tipo de e-mail:** Recomendado "HTML"

4. **Clique em "Salvar alterações"**

### Passo 2: Teste Básico

1. **Crie um pedido de teste** ou use um existente
2. **Adicione um código de rastreamento** ao pedido
3. **Verifique se o e-mail foi enviado** (verifique a caixa de entrada do cliente)
4. **Confirme a nota no pedido** indicando que o e-mail foi enviado

## Configurações Avançadas

### Personalização do Assunto e Cabeçalho

Você pode usar as seguintes variáveis nos campos de assunto e cabeçalho:

- `{order_number}` - Número do pedido
- `{order_date}` - Data do pedido  
- `{tracking_code}` - Código de rastreamento

**Exemplo de assunto personalizado:**
```
Seu pedido #{order_number} está sendo enviado - Código: {tracking_code}
```

### Conteúdo Adicional

Use o campo "Conteúdo Adicional" para:
- Adicionar informações específicas da sua loja
- Incluir links para redes sociais
- Adicionar políticas de entrega
- Inserir informações de contato

**Exemplo:**
```
Dúvidas sobre seu pedido? Entre em contato conosco:
📞 (11) 1234-5678
📧 atendimento@minhaloja.com.br

Siga-nos nas redes sociais para novidades e promoções!
```

## Personalização de Templates

### Para Desenvolvedores

Se você quiser personalizar completamente o visual dos e-mails:

1. **Copie os templates** para o seu tema:
   ```bash
   mkdir -p wp-content/themes/seu-tema/woocommerce/emails/plain
   cp wp-content/plugins/woocommerce-track17-rastreamento/templates/emails/tracking-code-notification.php wp-content/themes/seu-tema/woocommerce/emails/
   cp wp-content/plugins/woocommerce-track17-rastreamento/templates/emails/plain/tracking-code-notification.php wp-content/themes/seu-tema/woocommerce/emails/plain/
   ```

2. **Edite os arquivos** copiados conforme necessário
3. **Mantenha a estrutura** dos hooks do WooCommerce para compatibilidade

### Filtros Disponíveis

Para personalizações via código, use estes filtros no `functions.php` do seu tema:

```php
// Personalizar assunto padrão
add_filter('wc_track17_tracking_email_default_subject', function($subject) {
    return 'Novidade: Seu pedido #{order_number} já pode ser rastreado!';
});

// Personalizar cabeçalho padrão
add_filter('wc_track17_tracking_email_default_heading', function($heading) {
    return 'Rastreamento Liberado!';
});
```

## Resolução de Problemas

### Problema: E-mail não aparece nas configurações

**Solução:**
1. Verifique se todos os arquivos foram copiados corretamente
2. Confirme se não há erros de PHP (verifique logs de erro)
3. Desative e reative o plugin
4. Limpe qualquer cache existente

### Problema: E-mails não estão sendo enviados

**Possíveis causas e soluções:**

1. **E-mail não está habilitado:**
   - Vá para WooCommerce > Configurações > E-mails
   - Verifique se "Código de Rastreamento Disponível" está marcado como habilitado

2. **Problemas de SMTP:**
   - Teste outros e-mails do WooCommerce
   - Configure um plugin de SMTP se necessário

3. **Código de rastreamento vazio:**
   - Certifique-se de que há um código válido no pedido
   - Verifique se o campo não está em branco

### Problema: E-mails sendo enviados múltiplas vezes

**Solução:**
- O sistema tem proteção contra duplicação
- Se persistir, pode haver conflito com outros plugins
- Verifique se não há hooks customizados interferindo

### Problema: Template não carrega corretamente

**Soluções:**
1. Verifique permissões dos arquivos (644 para arquivos, 755 para diretórios)
2. Confirme se o caminho dos templates está correto
3. Limpe cache do site e do navegador

## Manutenção

### Atualizações Futuras

Quando atualizar o plugin:

1. **Sempre faça backup** antes da atualização
2. **Verifique compatibilidade** com sua versão do WooCommerce
3. **Teste em ambiente de desenvolvimento** primeiro
4. **Mantenha personalizações** no tema para evitar perda

### Monitoramento

Para monitorar o funcionamento:

1. **Verifique regularmente** as notas dos pedidos
2. **Monitore logs** de e-mail do WooCommerce
3. **Teste periodicamente** com pedidos reais
4. **Colete feedback** dos clientes sobre recebimento

## Suporte

### Logs e Debugging

Para investigar problemas:

1. **Ative logs do WooCommerce:**
   - WooCommerce > Status > Logs
   - Procure por logs relacionados a e-mails

2. **Ative debug do WordPress** (wp-config.php):
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

3. **Verifique logs do servidor** para erros de PHP

### Informações para Suporte

Ao solicitar suporte, forneça:

- Versão do WordPress
- Versão do WooCommerce  
- Versão do PHP
- Lista de plugins ativos
- Logs de erro relevantes
- Descrição detalhada do problema

## Conclusão

Seguindo estas instruções, você terá o sistema de e-mails de rastreamento funcionando perfeitamente em sua loja WooCommerce. O sistema foi projetado para ser robusto e fácil de usar, proporcionando uma melhor experiência para seus clientes.

Lembre-se de sempre testar em um ambiente de desenvolvimento antes de implementar em produção, e mantenha backups regulares do seu site.

