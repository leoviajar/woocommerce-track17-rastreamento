# WooCommerce Track17 Rastreamento

Plugin completo de rastreamento para WordPress/WooCommerce com integração à API Track17. Permite configuração de API no painel administrativo e é totalmente compatível com HPOS (High-Performance Order Storage).

## 🚀 Características

- **Integração completa com API Track17**: Registra e consulta rastreamentos automaticamente
- **Compatibilidade HPOS**: Totalmente compatível com o novo sistema de armazenamento de pedidos do WooCommerce
- **Dashboard administrativo**: Visualize estatísticas e gráficos de rastreamento
- **Interface para clientes**: Página dedicada para rastreamento na área da conta
- **Configuração flexível**: Configure sua própria chave API no painel administrativo
- **Webhooks**: Suporte para notificações automáticas da Track17
- **Shortcode e Widget**: Adicione formulários de rastreamento em qualquer lugar
- **Responsivo**: Interface otimizada para desktop e mobile

## 📋 Requisitos

- WordPress 5.0 ou superior
- WooCommerce 6.0 ou superior
- PHP 7.4 ou superior
- Chave da API Track17 (gratuita para até 100 rastreamentos/mês)

## 🔧 Instalação

1. **Download**: Baixe o plugin ou clone este repositório
2. **Upload**: Faça upload da pasta `woocommerce-track17-rastreamento` para `/wp-content/plugins/`
3. **Ativação**: Ative o plugin no painel administrativo do WordPress
4. **Configuração**: Vá em WooCommerce > Track17 Rastreamento para configurar sua chave API

## ⚙️ Configuração

### 1. Obtenha sua chave da API Track17

1. Acesse [17track.net](https://www.17track.net/en/apikey)
2. Crie uma conta ou faça login
3. Vá em Settings > Security > Access Key
4. Copie sua chave da API

### 2. Configure o plugin

1. No WordPress, vá em **WooCommerce > Track17 Rastreamento**
2. Cole sua chave da API no campo correspondente
3. Configure as opções conforme necessário:
   - **Registro Automático**: Registra códigos automaticamente na API
   - **Frequência de Atualização**: Define com que frequência buscar atualizações
   - **URL do Webhook**: Para receber notificações automáticas

### 3. Configure webhooks (opcional)

Para receber atualizações automáticas:

1. No painel da Track17, configure o webhook para: `https://seusite.com/wp-json/wc-track17/v1/webhook`
2. Isso permitirá atualizações em tempo real dos status de rastreamento

## 📖 Como Usar

### Para Administradores

#### Adicionando códigos de rastreamento

1. Edite um pedido no WooCommerce
2. Na seção "Informações de Rastreamento Track17":
   - Insira o código de rastreamento
   - Selecione a transportadora (opcional - será detectada automaticamente)
3. Salve o pedido

#### Dashboard de rastreamento

- Acesse **Track17 Dashboard** no menu administrativo
- Visualize estatísticas em tempo real
- Veja gráficos de transportadoras e status
- Atualize todos os rastreamentos com um clique

### Para Clientes

#### Na área da conta

1. Clientes podem acessar **Minha Conta > Rastreamento**
2. Visualizar todos os pedidos com rastreamento
3. Copiar códigos e acessar links diretos para rastreamento

#### Formulário público

Use o shortcode `[wc_track17_tracking]` para adicionar um formulário de rastreamento em qualquer página.

**Parâmetros do shortcode:**
```
[wc_track17_tracking title="Rastrear Pedido" placeholder="Digite o código" button_text="Rastrear"]
```

## 🎨 Personalização

### CSS Personalizado

O plugin inclui classes CSS que podem ser personalizadas:

```css
/* Status de rastreamento */
.wc-track17-status-entregue { background: #27ae60; }
.wc-track17-status-em-transito { background: #f39c12; }

/* Formulário de rastreamento */
.wc-track17-tracking-form { /* seus estilos */ }
```

### Hooks e Filtros

O plugin oferece vários hooks para desenvolvedores:

```php
// Filtro para personalizar status de rastreamento
add_filter('wc_track17_tracking_status', 'custom_tracking_status', 10, 2);

// Ação após atualização de rastreamento
add_action('wc_track17_tracking_updated', 'custom_tracking_action', 10, 2);
```

## 🔌 API e Webhooks

### Endpoint do Webhook

- **URL**: `/wp-json/wc-track17/v1/webhook`
- **Método**: POST
- **Autenticação**: Não requerida (validação por IP da Track17)

### API REST

O plugin adiciona informações de rastreamento à API REST do WooCommerce:

```json
{
  "tracking_info": {
    "tracking_code": "BR123456789CN",
    "tracking_status": "em_transito",
    "tracking_status_label": "Em Trânsito",
    "carrier_code": "2151",
    "last_update": "2024-01-15 10:30:00"
  }
}
```

## 🚨 Solução de Problemas

### Problemas Comuns

**1. "Chave da API inválida"**
- Verifique se a chave foi copiada corretamente
- Certifique-se de que a conta Track17 está ativa

**2. "Rastreamentos não atualizam"**
- Verifique a frequência de atualização nas configurações
- Teste a conexão com a API usando o botão "Testar API"

**3. "Plugin incompatível com HPOS"**
- Este plugin é totalmente compatível com HPOS
- Certifique-se de estar usando a versão mais recente

### Logs de Debug

Para ativar logs de debug, adicione ao `wp-config.php`:

```php
define('WC_TRACK17_DEBUG', true);
```

Os logs serão salvos em `/wp-content/uploads/wc-logs/`

## 🔄 Atualizações

O plugin verifica automaticamente por atualizações. Para atualizar manualmente:

1. Faça backup do site
2. Desative o plugin
3. Substitua os arquivos
4. Reative o plugin

## 🤝 Suporte

- **Documentação**: [Link para documentação completa]
- **Issues**: [Link para issues no GitHub]
- **Suporte**: [Link para suporte]

## 📄 Licença

Este plugin é licenciado sob GPL v2 ou posterior.

## 🙏 Créditos

- Desenvolvido para integração com [17TRACK](https://www.17track.net/)
- Compatível com [WooCommerce](https://woocommerce.com/)
- Ícones por [Dashicons](https://developer.wordpress.org/resource/dashicons/)

## 📊 Estatísticas

- **Transportadoras suportadas**: 40+
- **Status de rastreamento**: 9 diferentes
- **Idiomas**: Português (mais idiomas em breve)
- **Compatibilidade**: WordPress 5.0+ / WooCommerce 6.0+

---

**Versão**: 1.0.0  
**Testado até**: WordPress 6.4 / WooCommerce 8.5  
**Requer PHP**: 7.4+

