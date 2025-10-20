# Integração Vertex AI

## Configuração

1. **Criar projeto no Google Cloud Console**
2. **Habilitar Vertex AI API**
3. **Criar Service Account com permissões:**
   - Vertex AI User
   - AI Platform Developer

4. **Baixar credenciais JSON** e salvar como:
   ```
   credentials/service-account.json
   ```

5. **Configurar variáveis no .env:**
   ```env
   VERTEX_AI_PROJECT_ID=seu-project-id
   VERTEX_AI_LOCATION=us-central1
   VERTEX_AI_MODEL_ID=gemini-1.5-flash
   ```

## Teste

```bash
# Testar health endpoint
curl http://localhost:9501/health

# Testar classificação (via Consumer RabbitMQ)
# Enviar mensagem para fila q.message.receive
```

## Fallback

Se Vertex AI falhar, o sistema usa heurísticas como fallback automaticamente.
