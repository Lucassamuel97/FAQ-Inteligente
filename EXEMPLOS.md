# Exemplos de Uso - Sistema MCP RAG

Este arquivo contém exemplos práticos de como usar o sistema e perguntas para testar a funcionalidade RAG.

## 🧪 Perguntas para Testar o Sistema

### 1. Perguntas sobre Alvará de Funcionamento
- "Como tirar alvará de funcionamento?"
- "Quais documentos preciso para abrir um comércio?"
- "Qual o prazo para análise do alvará?"
- "Preciso de projeto de instalações elétricas?"

### 2. Perguntas sobre Documentos
- "Como solicitar 2ª via de certidão de casamento?"
- "Qual o valor da taxa para certidão de nascimento?"
- "Quanto tempo demora para emitir certidão negativa?"
- "Quais documentos preciso para certidão de óbito?"

### 3. Perguntas sobre Taxas e Impostos
- "Qual o valor da taxa de coleta de lixo?"
- "Quem está isento da taxa de coleta?"
- "Como é cobrada a taxa de coleta?"
- "A taxa de coleta vem junto com o IPTU?"

### 4. Perguntas sobre Leis Municipais
- "Quais são as normas de posturas municipais?"
- "Qual o horário de funcionamento dos estabelecimentos?"
- "É permitido som alto após as 22h?"
- "Quais são as regras para placas e letreiros?"

### 5. Perguntas sobre Serviços Públicos
- "Como funciona o atendimento na prefeitura?"
- "Quais são os canais de atendimento?"
- "Como faço para agendar atendimento?"
- "Quais são os horários de funcionamento?"

## 🔍 Exemplos de Busca Semântica

### Busca por Sinônimos
- **Pergunta**: "Como abrir um negócio?"
- **Sistema deve encontrar**: Documentos sobre alvará de funcionamento

### Busca por Conceitos Relacionados
- **Pergunta**: "Quais são as regras para comércio?"
- **Sistema deve encontrar**: Leis de posturas municipais e regulamentos de alvará

### Busca por Contexto
- **Pergunta**: "Preciso de documentos para regularizar minha situação?"
- **Sistema deve encontrar**: Documentos sobre certidões e alvarás

## 📊 Exemplos de Respostas Esperadas

### Exemplo 1: Alvará de Funcionamento
**Pergunta**: "Como tirar alvará de funcionamento?"

**Resposta Esperada**:
```
## Resposta para: Como tirar alvará de funcionamento?

### Resumo
Com base na legislação e regulamentos municipais, encontrei as seguintes informações relevantes:

📄 Regulamento de Alvará de Funcionamento
- Tipo: regulamento
- Categoria: Serviços Públicos
- Número: REG-001/2024
- Relevância: 95.2%

Informações:
Este regulamento estabelece as normas para emissão de alvará de funcionamento para estabelecimentos comerciais, industriais e de prestação de serviços no município. Para obter o alvará, o interessado deve apresentar: 1) Requerimento padronizado; 2) Documentação pessoal (RG, CPF); 3) Comprovante de endereço; 4) Planta baixa do estabelecimento; 5) Projeto de instalações elétricas e hidráulicas; 6) Certificado de aprovação do Corpo de Bombeiros; 7) Licença ambiental quando aplicável. O prazo para análise é de 30 dias úteis.

### Recomendações
Para obter informações mais detalhadas ou esclarecimentos, recomendo:
1. Entrar em contato com a prefeitura através dos canais oficiais
2. Consultar o site oficial da prefeitura
3. Visitar o atendimento presencial se necessário
```

### Exemplo 2: Taxa de Coleta de Lixo
**Pergunta**: "Qual o valor da taxa de coleta de lixo?"

**Resposta Esperada**:
```
## Resposta para: Qual o valor da taxa de coleta de lixo?

### Resumo
Com base na legislação e regulamentos municipais, encontrei as seguintes informações relevantes:

📄 Decreto Municipal nº 456/2024 - Taxa de Coleta de Lixo
- Tipo: regulamento
- Categoria: Taxas e Impostos
- Número: 456/2024
- Relevância: 98.7%

Informações:
Este decreto estabelece a taxa de coleta de lixo domiciliar no município. Art. 1º - Fica instituída a Taxa de Coleta de Lixo Domiciliar (TCLD) no valor de R$ 25,00 mensais por unidade habitacional. Art. 2º - A TCLD será cobrada mensalmente junto com o IPTU. Art. 3º - Estão isentos da TCLD: I - Imóveis com renda familiar inferior a 3 salários mínimos; II - Imóveis rurais; III - Imóveis públicos destinados a serviços essenciais.

### Recomendações
Para obter informações mais detalhadas ou esclarecimentos, recomendo:
1. Entrar em contato com a prefeitura através dos canais oficiais
2. Consultar o site oficial da prefeitura
3. Visitar o atendimento presencial se necessário
```

## 🎯 Cenários de Uso Real

### Cenário 1: Cidadão Abrindo um Comércio
1. **Pergunta**: "Quero abrir uma loja, o que preciso?"
2. **Sistema responde** com informações sobre alvará, documentos necessários e prazos
3. **Cidadão** segue as orientações e prepara a documentação
4. **Resultado**: Processo mais ágil e transparente

### Cenário 2: Funcionário Público Atendendo
1. **Cidadão pergunta**: "Como funciona a taxa de coleta?"
2. **Funcionário** consulta o sistema para dar resposta precisa
3. **Sistema** fornece base legal e informações atualizadas
4. **Resultado**: Atendimento mais eficiente e preciso

### Cenário 3: Advogado Consultando Legislação
1. **Advogado pergunta**: "Quais são as normas de posturas municipais?"
2. **Sistema** busca em todas as leis e regulamentos relevantes
3. **Resposta** inclui referências específicas e contexto legal
4. **Resultado**: Consulta jurídica mais rápida e abrangente

## 🔧 Testes de Funcionalidade

### Teste de Busca Semântica
```bash
# Teste com perguntas similares
curl -X POST http://localhost:8080 \
  -d "question=Como tirar alvará?" \
  -d "question=Quais documentos para abrir comércio?" \
  -d "question=O que preciso para alvará de funcionamento?"

# Verificar se as respostas são consistentes
```

### Teste de Performance
```bash
# Medir tempo de resposta
time curl -X POST http://localhost:8080 \
  -d "question=Como funciona a taxa de coleta de lixo?"
```

### Teste de Relevância
```bash
# Perguntas que devem retornar resultados similares
- "Qual o valor da taxa de lixo?"
- "Quanto custa a coleta de lixo?"
- "Taxa de coleta de lixo domiciliar"
```

## 📈 Métricas de Qualidade

### Taxa de Acerto Esperada
- **Perguntas diretas**: > 90%
- **Perguntas por sinônimos**: > 85%
- **Perguntas por contexto**: > 80%

### Tempo de Resposta
- **Resposta simples**: < 2 segundos
- **Resposta complexa**: < 5 segundos
- **Reindexação completa**: < 10 minutos

### Cobertura de Documentos
- **Leis municipais**: 100%
- **Regulamentos**: 100%
- **Serviços públicos**: 100%
- **Informações gerais**: > 90%

## 🚀 Melhorias Futuras

### Funcionalidades Planejadas
1. **Chat em tempo real** com histórico de conversas
2. **Notificações push** para atualizações de documentos
3. **API REST** para integração com outros sistemas
4. **Relatórios analíticos** de uso e performance
5. **Integração com WhatsApp** para atendimento via mensagem
6. **Sistema de feedback** para melhorar respostas
7. **Cache inteligente** para perguntas frequentes
8. **Backup automático** de embeddings e documentos

### Otimizações Técnicas
1. **Redis** para cache de embeddings
2. **Elasticsearch** para busca avançada
3. **Queue system** para processamento assíncrono
4. **CDN** para assets estáticos
5. **Load balancer** para alta disponibilidade
6. **Monitoramento** com Prometheus e Grafana
7. **Logs estruturados** com ELK Stack
8. **Testes automatizados** com PHPUnit

---

**Use estes exemplos para testar e validar o funcionamento do sistema!** 