# Guia de Instalação - WooCommerce Track17 Rastreamento

Este guia fornece instruções detalhadas para instalar e configurar o plugin WooCommerce Track17 Rastreamento.

## 📋 Pré-requisitos

Antes de instalar o plugin, certifique-se de que seu ambiente atende aos seguintes requisitos:

### Requisitos do Sistema
- **WordPress**: 5.0 ou superior
- **WooCommerce**: 6.0 ou superior  
- **PHP**: 7.4 ou superior
- **MySQL**: 5.6 ou superior (ou MariaDB equivalente)
- **Memória PHP**: Mínimo 128MB (recomendado 256MB)
- **Conexão com Internet**: Para comunicação com API Track17

### Extensões PHP Necessárias
- `curl` - Para requisições HTTP
- `json` - Para processamento de dados JSON
- `mbstring` - Para manipulação de strings
- `openssl` - Para conexões HTTPS seguras

### Verificação de Compatibilidade
```bash
# Verificar versão do PHP
php -v

# Verificar extensões instaladas
php -m | grep -E "(curl|json|mbstring|openssl)"
```

## 🚀 Instalação

### Método 1: Upload via Painel Administrativo (Recomendado)

1. **Preparar o arquivo**:
   - Compacte a pasta `woocommerce-track17-rastreamento` em um arquivo `.zip`
   - Certifique-se de que o arquivo principal está na raiz do ZIP

2. **Upload no WordPress**:
   - Acesse o painel administrativo do WordPress
   - Vá em **Plugins > Adicionar Novo**
   - Clique em **Enviar Plugin**
   - Selecione o arquivo ZIP e clique em **Instalar Agora**

3. **Ativação**:
   - Após a instalação, clique em **Ativar Plugin**
   - O plugin aparecerá na lista de plugins ativos

### Método 2: Upload via FTP/SFTP

1. **Conectar ao servidor**:
   ```bash
   # Exemplo usando SFTP
   sftp usuario@seuservidor.com
   ```

2. **Upload dos arquivos**:
   - Navegue até `/wp-content/plugins/`
   - Faça upload da pasta `woocommerce-track17-rastreamento`
   - Certifique-se de que as permissões estão corretas (755 para pastas, 644 para arquivos)

3. **Ativação**:
   - No painel administrativo, vá em **Plugins**
   - Encontre "WooCommerce Track17 Rastreamento" e clique em **Ativar**

### Método 3: Via WP-CLI

```bash
# Navegar até o diretório do WordPress
cd /caminho/para/wordpress

# Instalar o plugin
wp plugin install /caminho/para/woocommerce-track17-rastreamento.zip

# Ativar o plugin
wp plugin activate woocommerce-track17-rastreamento
```

## ⚙️ Configuração Inicial

### 1. Verificar Instalação

Após a ativação, verifique se o plugin foi instalado corretamente:

- ✅ Menu "Track17 Dashboard" aparece no painel administrativo
- ✅ Submenu "Track17 Rastreamento" aparece em WooCommerce
- ✅ Não há erros ou avisos na tela

### 2. Obter Chave da API Track17

1. **Criar conta na Track17**:
   - Acesse [17track.net](https://www.17track.net/en/apikey)
   - Crie uma conta gratuita ou faça login

2. **Gerar chave da API**:
   - Vá em **Settings > Security > Access Key**
   - Clique em **Generate** para criar uma nova chave
   - Copie a chave gerada (formato: `XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX`)

3. **Limites da conta gratuita**:
   - 100 rastreamentos por mês
   - Atualizações a cada 6-12 horas
   - Suporte para 40+ transportadoras

### 3. Configurar o Plugin

1. **Acessar configurações**:
   - No WordPress, vá em **WooCommerce > Track17 Rastreamento**

2. **Inserir chave da API**:
   - Cole sua chave da API no campo correspondente
   - Clique em **Testar API** para verificar a conexão
   - Aguarde a confirmação de sucesso

3. **Configurar opções**:
   ```
   ✅ Registro Automático: Ativado (recomendado)
   ✅ Frequência de Atualização: 6 horas (padrão)
   ✅ URL do Webhook: Deixar padrão ou personalizar
   ```

4. **Salvar configurações**:
   - Clique em **Salvar Alterações**
   - Verifique se aparece a mensagem de sucesso

### 4. Configurar Webhooks (Opcional)

Para receber atualizações automáticas:

1. **No painel da Track17**:
   - Vá em **Settings > Webhook**
   - Adicione a URL: `https://seusite.com/wp-json/wc-track17/v1/webhook`
   - Selecione eventos: **Tracking Updated**

2. **Testar webhook**:
   - Use a ferramenta de teste da Track17
   - Verifique os logs do WordPress se necessário

## 🧪 Teste da Instalação

### 1. Teste Básico

1. **Criar pedido de teste**:
   - Crie um pedido no WooCommerce
   - Adicione um código de rastreamento válido (ex: `BR123456789CN`)
   - Selecione a transportadora (ex: Correios)

2. **Verificar funcionamento**:
   - O código deve ser registrado automaticamente na Track17
   - Status deve aparecer na página do pedido
   - Cliente deve ver o rastreamento na área da conta

### 2. Teste do Dashboard

1. **Acessar dashboard**:
   - Vá em **Track17 Dashboard**
   - Verifique se os gráficos carregam
   - Teste o botão "Atualizar Estatísticas"

2. **Verificar dados**:
   - Estatísticas devem refletir os pedidos
   - Gráficos devem mostrar transportadoras
   - Informações do sistema devem estar corretas

### 3. Teste do Frontend

1. **Área da conta**:
   - Faça login como cliente
   - Vá em **Minha Conta > Rastreamento**
   - Verifique se os pedidos aparecem

2. **Shortcode**:
   - Adicione `[wc_track17_tracking]` em uma página
   - Teste a busca por código de rastreamento
   - Verifique se os resultados aparecem

## 🔧 Solução de Problemas

### Problemas Comuns

**1. Plugin não ativa**
```
Erro: "Plugin não pôde ser ativado"
Solução: Verificar compatibilidade PHP e WooCommerce
```

**2. Chave da API inválida**
```
Erro: "API key is invalid"
Solução: Verificar se a chave foi copiada corretamente
```

**3. Rastreamentos não atualizam**
```
Erro: Dados não são atualizados
Solução: Verificar conexão com internet e limites da API
```

**4. Erro de permissões**
```
Erro: "Permission denied"
Solução: Ajustar permissões dos arquivos (755/644)
```

### Logs de Debug

Para ativar logs detalhados:

1. **Adicionar ao wp-config.php**:
   ```php
   define('WC_TRACK17_DEBUG', true);
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Localizar logs**:
   - Logs do plugin: `/wp-content/uploads/wc-logs/`
   - Logs do WordPress: `/wp-content/debug.log`

### Verificação de Saúde

Execute estas verificações se houver problemas:

```bash
# Verificar permissões
find wp-content/plugins/woocommerce-track17-rastreamento -type f -exec ls -la {} \;

# Verificar conectividade
curl -I https://api.17track.net/track/v2.2/

# Verificar logs
tail -f wp-content/debug.log
```

## 🔄 Atualização

### Backup Antes da Atualização

1. **Backup completo**:
   - Banco de dados
   - Arquivos do WordPress
   - Configurações do plugin

2. **Backup específico**:
   ```bash
   # Backup da pasta do plugin
   tar -czf track17-backup.tar.gz wp-content/plugins/woocommerce-track17-rastreamento/
   
   # Backup das configurações (via WP-CLI)
   wp option get track17_api_key > track17-config-backup.txt
   ```

### Processo de Atualização

1. **Desativar plugin**:
   - Vá em **Plugins** e desative o plugin

2. **Substituir arquivos**:
   - Faça upload da nova versão
   - Substitua todos os arquivos

3. **Reativar plugin**:
   - Ative o plugin novamente
   - Verifique se as configurações foram mantidas

4. **Testar funcionalidade**:
   - Execute os testes básicos novamente
   - Verifique se não há erros

## 📞 Suporte

Se você encontrar problemas durante a instalação:

1. **Documentação**: Consulte o README.md
2. **Logs**: Verifique os logs de erro
3. **Comunidade**: Procure ajuda nos fóruns
4. **Suporte técnico**: Entre em contato através dos canais oficiais

---

**Importante**: Sempre faça backup antes de instalar ou atualizar plugins em ambiente de produção.

