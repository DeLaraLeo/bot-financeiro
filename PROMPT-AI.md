# Prompt de Classificação de Mensagens - OpenAI

Este documento contém o prompt completo utilizado para classificação de mensagens do bot financeiro via OpenAI.

## Estrutura do Prompt

O prompt é construído dinamicamente com base na data atual e nas categorias disponíveis no sistema. Ele instrui a IA a classificar mensagens em três intents principais:

1. **expense_registration**: Registro de despesas completas
2. **query_expenses**: Consultas sobre despesas
3. **greeting**: Mensagens ambíguas, incompletas ou saudações

## Regras Obrigatórias

1. Retorne APENAS um JSON válido
2. NÃO inclua explicações, textos ou comentários
3. NÃO use markdown ou code blocks
4. Use a data atual como referência

## Intents Disponíveis

### expense_registration
Usuário está registrando uma despesa COMPLETA com valor e descrição.

**Classificar como despesa APENAS se:**
- Mensagem contém um VALOR monetário explícito (ex: 25,50, R$ 100, 50 reais)
- Mensagem contém uma DESCRIÇÃO clara do que foi gasto
- Mensagem é COMPLETA e não ambígua

**NÃO classifique como despesa se:**
- Mensagem não tem valor (ex: "eu gastei", "gastei no mercado")
- Mensagem não tem descrição (ex: "gastei 50", "paguei 100")
- Mensagem é incompleta ou ambígua
- Mensagem é apenas um número sem contexto (ex: "1", "50", "100")

**Formato de retorno:**
```json
{
  "intent": "expense_registration",
  "confidence": 0.9,
  "data": {
    "amount_cents": valor_em_centavos,
    "description": "descrição",
    "category_hint": "categoria"
  }
}
```

### query_expenses
Usuário quer consultar suas despesas com período claro.

**Classificar como consulta APENAS se:**
- Mensagem pergunta explicitamente sobre gastos/despesas
- Mensagem menciona um período claro do PASSADO ou PRESENTE (ex: "este mês", "mês passado", "últimos 3 meses", "hoje", "ontem")
- Mensagem é uma pergunta completa e clara
- **IMPORTANTE:** Aceite variações coloquiais e abreviadas (veja lista abaixo)

**Variações coloquiais e abreviadas aceitas:**
- "qnt" = "quanto"
- "qto" = "quanto"
- "qt" = "quanto"
- "hj" = "hoje"
- "ont" = "ontem"
- "mes" = "mês"
- "sem" = "semana"
- "gastei" = "gastei"
- "gastos" = "gastos"
- "despesas" = "despesas"

**Exemplos de consultas válidas:**
- "quanto eu gastei hoje?" → query_expenses
- "qnt eu gastei hj?" → query_expenses
- "qto gastei hoje?" → query_expenses
- "quanto gastei ontem?" → query_expenses
- "qnt gastei ont?" → query_expenses
- "quanto gastei este mês?" → query_expenses
- "qnt gastei esse mes?" → query_expenses
- "quanto gastei semana passada?" → query_expenses
- "qnt gastei sem passada?" → query_expenses
- "quanto gastei nos últimos 10 dias?" → query_expenses
- "qnt gastei nos ultimos 10 dias?" → query_expenses
- "quanto gastei com mercado hoje?" → query_expenses
- "qnt gastei com mercado hj?" → query_expenses
- "quanto gastei no mercado este mês?" → query_expenses
- "qnt gastei no mercado esse mes?" → query_expenses

**NÃO classifique como consulta se:**
- Mensagem pede períodos FUTUROS (ex: "próximos X dias", "futuro", "daqui a X dias", "próxima semana", "semana que vem", "próximo mês", "amanhã", "depois de amanhã")
- Mensagem é apenas um número (ex: "1", "2", "10")
- Mensagem não menciona período ou consulta
- Mensagem é ambígua ou incompleta

**IMPORTANTE:** Se a mensagem pedir consulta de períodos FUTUROS, classifique como GREETING, pois não é possível consultar despesas futuras.

**Exemplos de períodos futuros (classificar como GREETING):**
- "quanto gastei nos próximos 10 dias?" → GREETING
- "quanto gastei semana que vem?" → GREETING
- "quanto gastei amanhã?" → GREETING
- "quanto gastei depois de amanhã?" → GREETING
- "quanto gastei na próxima semana?" → GREETING
- "quanto gastei no próximo mês?" → GREETING
- "quanto gastei daqui a 5 dias?" → GREETING
- "quanto gastei no futuro?" → GREETING
- "quanto gastei no mercado semana que vem?" → GREETING
- "quanto gastei no mercado amanhã?" → GREETING

**Formato de retorno:**
```json
{
  "intent": "query_expenses",
  "confidence": 0.9,
  "data": {
    "period": "período em português",
    "start_date": "YYYY-MM-DD HH:MM:SS",
    "end_date": "YYYY-MM-DD HH:MM:SS",
    "category_filter": "categoria_ou_null"
  }
}
```

**IMPORTANTE:** O campo "period" DEVE ser sempre em português legível, NUNCA use termos em inglês como "current_month". Use: "este mês", "mês passado", "últimos 2 meses", "hoje", "ontem", "semana passada", etc.

### greeting
Mensagens incompletas, ambíguas, sem sentido, ou qualquer outra coisa.

**Use para:**
- Mensagens incompletas ou ambíguas
- Mensagens sem contexto suficiente
- Números isolados (ex: "1", "50", "100")
- Mensagens que não se encaixam em despesa ou consulta
- Saudações, ajuda, ou qualquer outra coisa
- Consultas de períodos FUTUROS (qualquer referência a "próximos", "futuro", "daqui a", "próxima semana", "semana que vem", "próximo mês", "amanhã", "depois de amanhã", etc.)

**Formato de retorno para período futuro:**
```json
{
  "intent": "greeting",
  "confidence": 0.9,
  "data": {
    "is_future_period": true
  }
}
```

**Formato de retorno padrão:**
```json
{
  "intent": "greeting",
  "confidence": 0.9,
  "data": {}
}
```

## Regras para Cálculo de Datas

### 1. "últimos X dias" (ex: "últimos 10 dias", "últimos 17 dias", "últimos 30 dias")

**Fórmulas:**
- `end_date` = HOJE (data atual)
- `start_date` = HOJE - (X - 1) dias

**Regras críticas:**
- `end_date` NUNCA pode ser amanhã ou futuro, SEMPRE deve ser HOJE
- Se hoje é a data atual, `end_date` DEVE ser o final do dia de hoje, NUNCA amanhã

**Exemplos:**
- Se hoje é 2025-11-09, "últimos 10 dias" = `start_date: 2025-10-31 00:00:00`, `end_date: 2025-11-09 23:59:59`
- Se hoje é 2025-11-09, "últimos 17 dias" = `start_date: 2025-10-24 00:00:00`, `end_date: 2025-11-09 23:59:59`
- Se hoje é 2025-11-09, "últimos 30 dias" = `start_date: 2025-10-11 00:00:00`, `end_date: 2025-11-09 23:59:59`

### 2. "últimas X semanas"

**Fórmulas:**
- `end_date`: HOJE (final do dia)
- `start_date`: HOJE - (X * 7 - 1) dias

### 3. "últimos X meses"

**Fórmulas:**
- `end_date`: HOJE (final do dia)
- `start_date`: primeiro dia do mês de (hoje - X meses)

### 4. "semana passada" (CRÍTICO - SEGUIR EXATAMENTE)

**Definição:**
- "semana passada" = semana anterior completa (segunda a domingo da semana anterior)
- NÃO é os últimos 7 dias
- NÃO inclui dias da semana atual

**Fórmula exata:**
- `start_date` = segunda-feira da semana anterior
- `end_date` = domingo da semana anterior

**Regras críticas:**
- `end_date` NUNCA pode ser HOJE ou qualquer dia da semana atual
- Se hoje é a data atual, `end_date` DEVE ser o domingo da semana anterior, NUNCA hoje

**Exemplo:**
- Se hoje é 2025-11-09 (sábado), "semana passada" = `start_date: 2025-11-03 00:00:00` (segunda), `end_date: 2025-11-09 23:59:59` (domingo) - **ERRADO**
- Se hoje é 2025-11-09 (sábado), "semana passada" = `start_date: 2025-10-27 00:00:00` (segunda da semana anterior), `end_date: 2025-11-02 23:59:59` (domingo da semana anterior) - **CORRETO**

### 5. Regra Absoluta

`end_date` SEMPRE deve ser a data de HOJE, NUNCA amanhã ou qualquer data futura. 

**EXCEÇÃO:** Para "semana passada" e "mês passado", onde `end_date` é o último dia daquela semana/mês.

## Exemplos de Datas

Com base na data atual, os seguintes períodos são calculados:

- **hoje**: `{todayStart}` a `{todayEnd}` (period: "hoje")
- **ontem**: `{yesterdayStart}` a `{yesterdayEnd}` (period: "ontem")
- **esta semana**: `{currentWeekStart}` a `{currentWeekEnd}` (period: "esta semana")
- **semana passada**: `{lastWeekStart}` a `{lastWeekEnd}` (period: "semana passada")
- **este mês**: `{thisMonthStart}` a `{thisMonthEnd}` (period: "este mês")
- **mês passado**: `{lastMonthStart}` a `{lastMonthEnd}` (period: "mês passado")
- **últimos 10 dias**: `{last10DaysStart}` a `{todayEnd}` (period: "últimos 10 dias")
- **últimos 17 dias**: `{last17DaysStart}` a `{todayEnd}` (period: "últimos 17 dias")
- **últimos 30 dias**: `{last30DaysStart}` a `{todayEnd}` (period: "últimos 30 dias")

**LEMBRE-SE:** Para QUALQUER quantidade de dias (10, 17, 30, 50, 100, etc.), `end_date` SEMPRE é HOJE, NUNCA amanhã!

## Localização do Prompt

O prompt é definido no arquivo `config/autoload/ai_prompts.php` na função `classification_prompt`.

## Notas Importantes

- O prompt é construído dinamicamente com base na data atual e nas categorias disponíveis
- Todas as variáveis de data são calculadas em tempo de execução
- O prompt instrui a IA a retornar APENAS JSON válido, sem explicações ou formatação adicional
- A IA deve aceitar variações coloquiais e abreviadas comuns em português brasileiro

