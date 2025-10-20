# 🤖 Bot Financeiro - WhatsApp + IA

Bot financeiro inteligente que processa mensagens via WhatsApp, classifica gastos usando Vertex AI e gerencia despesas pessoais.

## 🚀 Stack Tecnológica

- **Backend**: Hyperf (PHP 8.3) + Swoole
- **Banco**: MySQL 8.0
- **Cache**: Redis 7
- **Mensageria**: RabbitMQ 3.12
- **IA**: Google Vertex AI (Gemini)
- **Container**: Docker + Docker Compose

## 📋 Funcionalidades

- ✅ **Registro de gastos** via mensagens WhatsApp
- ✅ **Classificação automática** com IA (Vertex AI)
- ✅ **Consultas de resumo** de gastos por período
- ✅ **Categorização inteligente** (mercado, farmácia, combustível, etc.)
- ✅ **Integração RabbitMQ** para comunicação com Worker WhatsApp
- ✅ **API REST** para consultas e health checks

## 🏗️ Arquitetura

```
WhatsApp Worker (Go) ↔ RabbitMQ ↔ Hyperf API ↔ MySQL
                                    ↓
                               Vertex AI
                                    ↓
                                 Redis
```

### Fluxo de Mensagens

1. **Recebimento**: Worker → `q.message.receive` → Hyperf
2. **Processamento**: IA classifica → Extrai dados → Registra gasto
3. **Resposta**: Hyperf → `q.message.send` → Worker → WhatsApp

## 🛠️ Instalação

### 1. Clone o repositório
```bash
git clone <seu-repositorio>
cd bot-financeiro
```

### 2. Configure as credenciais do Google Cloud
```bash
# Copie o arquivo de credenciais
cp credentials/service-account.json.example credentials/service-account.json
# Edite com suas credenciais reais do Google Cloud
```

### 3. Configure as variáveis de ambiente
```bash
cp .env.example .env
# Edite o .env com suas configurações
```

### 4. Suba os serviços
```bash
docker compose up -d
```

### 5. Execute as migrations
```bash
docker compose exec hyperf-app php bin/hyperf.php migrate -n
```

### 6. Popule as categorias
```bash
docker compose exec hyperf-app php bin/hyperf.php db:seed
```

## 🔧 Configuração

### Google Cloud / Vertex AI

1. **Crie um projeto** no Google Cloud Console
2. **Habilite as APIs**:
   - Vertex AI API
   - AI Platform Training & Prediction API
3. **Crie uma Service Account** com permissões:
   - Vertex AI User
   - AI Platform Developer
4. **Baixe a chave JSON** e coloque em `credentials/service-account.json`
5. **Configure no .env**:
   ```env
   VERTEX_AI_PROJECT_ID=seu-project-id
   VERTEX_AI_LOCATION=us-central1
   VERTEX_AI_MODEL_ID=gemini-1.5-flash
   ```

### RabbitMQ

- **Host**: `rabbitmq`
- **Porta**: `5672`
- **Usuário**: `admin`
- **Senha**: `admin123`
- **Filas**: 
  - `q.message.receive` (Worker → Hyperf)
  - `q.message.send` (Hyperf → Worker)

## 📊 Banco de Dados

### Tabelas

- **users**: Usuários (phone_e164, name)
- **categories**: Categorias padrão (mercado, farmácia, etc.)
- **expenses**: Gastos (user_id, amount_cents, description, category_id)
- **message_logs**: Logs de mensagens (direction, payload)

### Migrations

```bash
# Executar migrations
docker compose exec hyperf-app php bin/hyperf.php migrate -n

# Ver status
docker compose exec hyperf-app php bin/hyperf.php migrate:status
```

## 🧪 Testes

### Health Check
```bash
curl http://localhost:9501/health
```

### Teste de Mensagem (via RabbitMQ)
```bash
# Simular mensagem recebida
docker compose exec rabbitmq rabbitmqadmin publish \
  exchange=message routing_key=receive \
  payload='{"message_type":"text","message_id":"test123","sender_number":"5511999999999","message_body":"gastei 50 reais na farmacia","transaction_id":"test-uuid"}'
```

## 📱 Exemplos de Uso

### Registro de Gastos
```
Usuário: "gastei 25,50 no mercado"
Bot: "✅ Gasto registrado: R$ 25,50 - mercado"
```

### Consulta de Resumo
```
Usuário: "resumo dos gastos"
Bot: "📊 Resumo dos seus gastos:
Total: R$ 150,00
Quantidade: 5 gastos"
```

### Listar Categorias
```
Usuário: "categorias"
Bot: "📋 Categorias disponíveis:
• Mercado (mercado)
• Farmácia (farmacia)
• Combustível (combustivel)
..."
```

## 🔍 Monitoramento

### Logs
```bash
# Logs da aplicação
docker compose logs -f hyperf-app

# Logs do RabbitMQ
docker compose logs -f rabbitmq
```

### Adminer (Banco de Dados)
- **URL**: http://localhost:8080
- **Servidor**: `mysql`
- **Usuário**: `root`
- **Senha**: `root`
- **Base**: `bot_financeiro`

### RabbitMQ Management
- **URL**: http://localhost:15672
- **Usuário**: `admin`
- **Senha**: `admin123`

## 🚀 Deploy

### Produção
1. Configure as variáveis de ambiente de produção
2. Use um banco MySQL externo
3. Configure Redis externo
4. Configure RabbitMQ externo
5. Configure credenciais do Google Cloud
6. Execute as migrations
7. Popule as categorias

### Docker
```bash
# Build da imagem
docker compose build

# Subir serviços
docker compose up -d

# Parar serviços
docker compose down
```

## 📁 Estrutura do Projeto

```
bot-financeiro/
├── app/
│   ├── Controller/          # Controllers (coordenação)
│   ├── Service/            # Services (lógica de negócio)
│   ├── Repository/         # Repositories (data access)
│   ├── Amqp/              # RabbitMQ (consumers/producers)
│   └── Model/             # Models
├── config/                # Configurações
├── database/
│   ├── migrations/        # Migrations
│   └── seeders/          # Seeders
├── credentials/          # Credenciais Google Cloud
├── docker-compose.yml    # Stack Docker
└── README.md            # Este arquivo
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🆘 Suporte

- **Issues**: [GitHub Issues](https://github.com/seu-usuario/bot-financeiro/issues)
- **Documentação**: [Wiki do Projeto](https://github.com/seu-usuario/bot-financeiro/wiki)

---

**Desenvolvido com ❤️ usando Hyperf + Vertex AI**