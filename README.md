# 🤖 Bot Financeiro - WhatsApp + IA

Bot financeiro inteligente que processa mensagens via WhatsApp, classifica despesas usando OpenAI e gerencia despesas pessoais de forma automatizada.

## 🚀 Stack Tecnológica

- **Backend**: Hyperf (PHP 8.3) + Swoole
- **Banco de Dados**: MySQL 8.0
- **Cache/Estado**: Redis 7
- **Mensageria**: RabbitMQ 3.12
- **IA**: OpenAI (GPT-4o-mini)
- **ORM**: Eloquent (Hyperf Database)
- **Container**: Docker + Docker Compose

## 📋 Funcionalidades

- ✅ **Registro de despesas** via mensagens WhatsApp
- ✅ **Classificação automática** com IA (OpenAI GPT-4o-mini)
- ✅ **Consultas inteligentes** de despesas por período (hoje, ontem, semana, mês, últimos X dias, etc.)
- ✅ **Categorização automática** (mercado, farmácia, transporte, restaurante, etc.)
- ✅ **Resumo por categoria** com agrupamento e totais
- ✅ **Filtro por categoria** nas consultas
- ✅ **Gerenciamento de estado** de conversação via Redis
- ✅ **Cadastro de usuários** via WhatsApp
- ✅ **Integração RabbitMQ** para comunicação assíncrona com Worker WhatsApp

## 🏗️ Arquitetura

```
WhatsApp Worker (Go) ↔ RabbitMQ ↔ Hyperf API
                                    ├─→ MySQL
                                    ├─→ OpenAI API
                                    └─→ Redis (estados de conversação)
```

### Fluxo de Mensagens

1. **Recebimento**: Worker WhatsApp → `q.message.receive` → Hyperf Consumer
2. **Processamento**: 
   - Verifica se usuário existe (senão inicia cadastro)
   - OpenAI classifica a mensagem (despesa, consulta ou saudação)
   - Extrai dados (valor, descrição, categoria, período)
   - Processa ação (registra despesa ou consulta banco)
3. **Resposta**: Hyperf → `q.message.send` → Worker → WhatsApp

### Componentes Principais

- **AIService**: Comunicação com OpenAI para classificação e extração de dados
- **ConversationService**: Orquestra o fluxo de conversação
- **ConversationStateManager**: Gerencia estados de conversação no Redis
- **RegistrationHandler**: Processa cadastro de novos usuários
- **ExpenseService**: Lógica de negócio para despesas
- **Repositories**: Acesso aos dados (User, Category, Expense)

## 🛠️ Instalação

### Pré-requisitos

- Docker e Docker Compose
- Conta OpenAI com API Key
- Worker WhatsApp (projeto separado)

### 1. Clone o repositório

```bash
git clone <seu-repositorio>
```

### 2. Configure as variáveis de ambiente

```bash
cp .env.example .env
```

Edite o `.env` com suas configurações:

```env
# OpenAI
OPENAI_API_KEY=sk-proj-your-api-key-here

# Database
DB_HOST=mysql
DB_DATABASE=bot_financeiro
DB_USERNAME=root
DB_PASSWORD=root

# RabbitMQ
RABBITMQ_HOST=rabbitmq-bot-financeiro
RABBITMQ_DEFAULT_USER=admin
RABBITMQ_DEFAULT_PASS=admin123

# Redis
REDIS_HOST=redis
```

### 3. Build e suba os serviços

```bash
# Primeira vez ou após mudanças no Dockerfile
docker compose build

# Subir os serviços
docker compose up -d

# Ou fazer build e subir de uma vez
docker compose up -d --build
```

### 4. Execute as migrations

```bash
docker compose exec hyperf-app php bin/hyperf.php migrate -n
```

### 5. Popule as categorias

```bash
docker compose exec hyperf-app php bin/hyperf.php db:seed
```

## 🔧 Configuração

### OpenAI

1. **Crie uma conta** na [OpenAI](https://platform.openai.com/)
2. **Gere uma API Key** em [API Keys](https://platform.openai.com/api-keys)
3. **Configure no .env**:
   ```env
   OPENAI_API_KEY=sk-proj-sua-chave-aqui
   ```

### RabbitMQ

- **Host**: `rabbitmq-bot-financeiro` (dentro do Docker) ou `localhost` (fora)
- **Porta**: `5672`
- **Management UI**: http://localhost:15672
- **Usuário**: `admin`
- **Senha**: `admin123`
- **Filas**: 
  - `q.message.receive` (Worker → Hyperf)
  - `q.message.send` (Hyperf → Worker)

## 📊 Banco de Dados

### Tabelas

- **users**: Usuários do bot
  - `id`, `phone_e164` (único), `name`, `created_at`, `updated_at`
  
- **categories**: Categorias de despesas
  - `id`, `code` (único), `name`, `created_at`, `updated_at`
  - Exemplos: mercado, farmácia, transporte, restaurante, etc.
  
- **expenses**: Despesas registradas
  - `id`, `user_id`, `amount_cents`, `currency` (BRL), `category_id`, `description`
  - `occurred_at`, `created_at`, `updated_at`

### Migrations

```bash
# Executar migrations
docker compose exec hyperf-app php bin/hyperf.php migrate -n

# Ver status
docker compose exec hyperf-app php bin/hyperf.php migrate:status

# Reverter última migration
docker compose exec hyperf-app php bin/hyperf.php migrate:rollback
```

### Seeders

```bash
# Popular categorias
docker compose exec hyperf-app php bin/hyperf.php db:seed

# Popular despesas de teste (500 despesas)
# NOTA: Para testar, altere as variáveis de nome e número de telefone no arquivo seeders/expense_seeder.php
docker compose exec hyperf-app php bin/hyperf.php db:seed --class=ExpenseSeeder
```

## 📱 Exemplos de Uso

### Cadastro de Usuário

```
Usuário: "Olá"
Bot: "Olá! Bem-vindo ao assistente financeiro. Qual é o seu nome?"
Usuário: "João Silva"
Bot: "Bem-vindo, João Silva! 🎉 Agora você pode registrar suas despesas."
```

### Registro de Despesas

```
Usuário: "gastei 25,50 no mercado"
Bot: "Despesa registrada com sucesso! 💰"

Usuário: "paguei 150 reais na mensalidade da academia"
Bot: "Despesa registrada com sucesso! 💰"
```

### Consultas de Despesas

```
Usuário: "quanto eu gastei hoje?"
Bot: "📊 Resumo das suas despesas (hoje) (08/11/2025 a 08/11/2025):
• Mercado: R$ 25,50
• Farmácia: R$ 10,00
💰 Total: R$ 35,50"

Usuário: "quanto eu gastei semana passada?"
Bot: "📊 Resumo das suas despesas (semana passada) (28/10/2025 a 03/11/2025):
• Mercado: R$ 125,00
• Restaurante: R$ 85,50
• Transporte: R$ 45,00
💰 Total: R$ 255,50"

Usuário: "quanto eu gastei nos últimos 10 dias?"
Bot: "📊 Resumo das suas despesas (últimos 10 dias) (29/10/2025 a 08/11/2025):
• Mercado: R$ 250,00
• Farmácia: R$ 50,00
• Transporte: R$ 100,00
💰 Total: R$ 400,00"

Usuário: "quanto eu gastei mês passado com restaurante?"
Bot: "📊 Resumo das suas despesas (mês passado) (01/10/2025 a 31/10/2025):
• Restaurante: R$ 450,00
💰 Total: R$ 450,00"
```

### Mensagens de Ajuda

```
Usuário: "olá"
Bot: "Olá, João! Sou seu assistente financeiro. O que gostaria de fazer?

Você pode:
• 📝 Registrar despesas: 'gastei 25,50 no mercado'
• 💰 Consultar despesas: 'quanto gastei este mês?'
• ⚠️ Registre apenas uma despesa por mensagem."
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

### Verificar Estado no Redis

```bash
docker compose exec redis redis-cli
> KEYS *
> GET "conversation:state:5511999999999"
```

## 🔍 Monitoramento

### Logs

```bash
# Logs da aplicação
docker compose logs -f hyperf-app

# Logs do RabbitMQ
docker compose logs -f rabbitmq-bot-financeiro

# Logs do MySQL
docker compose logs -f mysql

# Logs do Redis
docker compose logs -f redis
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

## 📁 Estrutura do Projeto

```
bot-financeiro/
├── app/
│   ├── Amqp/
│   │   ├── Consumer/
│   │   │   └── MessageReceiveConsumer.php    # Consumer RabbitMQ
│   │   └── Message/
│   │       └── MessageSendProducer.php        # Producer RabbitMQ
│   ├── Command/
│   │   └── StartConsumerCommand.php
│   ├── Controller/
│   │   ├── HealthController.php               # Health check
│   │   └── IndexController.php
│   ├── Model/
│   │   ├── Category.php                      # Model Eloquent
│   │   ├── Expense.php                       # Model Eloquent
│   │   └── User.php                          # Model Eloquent
│   ├── Repository/
│   │   ├── CategoryRepository.php            # Acesso a dados
│   │   ├── ExpenseRepository.php              # Acesso a dados
│   │   └── UserRepository.php                # Acesso a dados
│   └── Service/
│       ├── AIService.php                     # Integração OpenAI
│       ├── ConversationService.php           # Orquestração
│       ├── ConversationStateManager.php      # Gerenciamento de estado
│       ├── ExpenseService.php                # Lógica de despesas
│       ├── RegistrationHandler.php           # Cadastro de usuários
│       └── UserService.php                   # Lógica de usuários
├── config/
│   └── autoload/
│       ├── ai_prompts.php                    # Prompts OpenAI
│       ├── amqp.php                          # Config RabbitMQ
│       ├── databases.php                     # Config MySQL
│       └── redis.php                         # Config Redis
├── migrations/
│   ├── create_users_table.php
│   ├── create_categories_table.php
│   └── create_expenses_table.php
├── seeders/
│   ├── category_seeder.php
│   └── expense_seeder.php
├── docker-compose.yml
├── Dockerfile
└── README.md
```

## 🎯 Funcionalidades da IA

### Classificação de Mensagens

A IA classifica mensagens em 3 intents:

1. **expense_registration**: Registro de despesa completa
   - Extrai: `amount_cents`, `description`, `category_hint`
   
2. **query_expenses**: Consulta de despesas
   - Extrai: `period`, `start_date`, `end_date`, `category_filter`
   - Suporta: hoje, ontem, esta semana, semana passada, este mês, mês passado, últimos X dias/semanas/meses
   
3. **greeting**: Saudações ou mensagens ambíguas
   - Retorna mensagem de ajuda

### Prompts

Os prompts estão configurados em `config/autoload/ai_prompts.php` e incluem:

- Regras rigorosas de classificação
- Exemplos de cálculos de datas
- Lista de categorias disponíveis
- Instruções para extração de dados

**📄 Para visualizar o prompt completo formatado, consulte:** [`PROMPT-AI.md`](./PROMPT-AI.md)

Este arquivo contém a documentação completa do prompt utilizado para classificação de mensagens, incluindo todas as regras, exemplos e formatos de retorno esperados.

## 🚀 Deploy

### Produção

1. Configure as variáveis de ambiente de produção
2. Use um banco MySQL externo (ou mantenha no Docker)
3. Configure Redis externo (ou mantenha no Docker)
4. Configure RabbitMQ externo (ou mantenha no Docker)
5. Configure a API Key da OpenAI
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

# Rebuild sem cache
docker compose build --no-cache hyperf-app
docker compose up -d
```

## 🔒 Segurança

- API Key da OpenAI armazenada em variáveis de ambiente
- Validação de dados de entrada
- Sanitização de mensagens
- Validação de datas (nunca permite datas futuras)

## 🐛 Troubleshooting

### Consumer não está processando mensagens

```bash
# Verificar se o consumer está rodando
docker compose logs hyperf-app | grep Consumer

# Reiniciar o container
docker compose restart hyperf-app
```

### Erro de conexão com RabbitMQ

```bash
# Verificar se RabbitMQ está rodando
docker compose ps rabbitmq-bot-financeiro

# Verificar logs
docker compose logs rabbitmq-bot-financeiro
```

### Erro de OpenAI

```bash
# Verificar se a API Key está configurada
docker compose exec hyperf-app php -r "echo getenv('OPENAI_API_KEY') ? 'OK' : 'NOT SET';"
```