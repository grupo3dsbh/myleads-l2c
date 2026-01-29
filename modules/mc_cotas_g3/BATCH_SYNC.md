# SINCRONIZAÇÃO EM LOTES - MC Cotas G3

## 📊 Como Funciona a Sincronização

### **Sistema de Lotes (Batches)**

O módulo MC Cotas G3 usa um sistema inteligente de **lotes** para evitar travamentos e timeouts.

---

## 🔄 Processo Passo a Passo

### **Exemplo Prático:**

Imagine que você tem **5.000 membros** no Multiclubes e configurou **lote de 100**.

### **O que acontece:**

```
INÍCIO DA SINCRONIZAÇÃO
├─ Conta total: 5.000 membros
├─ Tamanho do lote: 100
└─ Total de lotes: 50

LOTE 1 (membros 1-100)
├─ Busca 100 membros do SQL Server
├─ Processa um por um:
│  ├─ Membro 1 → Verifica se existe → NÃO → CRIA lead #1001
│  ├─ Membro 2 → Verifica se existe → NÃO → CRIA lead #1002
│  ├─ Membro 3 → Verifica se existe → NÃO → CRIA lead #1003
│  └─ ... (até 100)
└─ Libera memória

LOTE 2 (membros 101-200)
├─ Busca 100 membros do SQL Server
├─ Processa um por um
└─ Libera memória

... (continua até lote 50)

RESULTADO FINAL:
├─ 5.000 membros processados
├─ 4.500 leads novos criados
├─ 500 leads atualizados
├─ 0 erros
└─ Tempo: ~120 segundos
```

---

## 💾 O Que É Salvo

### **Para cada membro sincronizado:**

#### **1. Tabela `tblleads`** (Lead criado/atualizado)
```sql
INSERT/UPDATE tblleads SET
  name = 'JEAN FELIPE BRAGA'
  email = 'jeanbraga035@gmail.com'
  phonenumber = '5531983401034'
  city = 'Abadia dos Dourados'
  state = 'MG'
  address = 'Pintor Renato lima, n° 25, Casa'
  status = 3           -- SEM ATENDIMENTO
  source = 8           -- CRM
  assigned = 0         -- não atribuído
  mc_member_id = 12296 -- ⭐ CAMPO ÚNICO
  mc_title_code = 'PLA-0036'
  mc_is_titular = 1
  description = '**IMPORTADO DO MULTICLUBES**...'
```

#### **2. Tabela `tblmc_cotas_g3_sync`** (Controle de sincronização)
```sql
INSERT tblmc_cotas_g3_sync SET
  member_id = 12296
  lead_id = 1001
  title_code = 'PLA-0036'
  title_type_name = 'Platinum 05 vagas'
  member_status = 'Ativo'
  is_titular = 1
  last_sync_date = '2026-01-29 16:30:00'
```

#### **3. Tabela `tblmc_cotas_g3_sync_log`** (Histórico)
```sql
INSERT tblmc_cotas_g3_sync_log SET
  sync_date = '2026-01-29 16:30:00'
  total_members = 5000
  new_leads = 4500
  updated_leads = 500
  errors = 0
  sync_by = 1          -- ID do admin
  is_cron = 0          -- sincronização manual
  execution_time = 120.45
```

---

## 🚫 Evitando Duplicatas

### **Sistema de Detecção:**

O campo `mc_member_id` é **UNIQUE** no banco de dados:

```php
// ANTES DE CRIAR:
SELECT * FROM tblleads WHERE mc_member_id = 12296

// RESULTADO:
┌─────────────────────────────┐
│ NÃO ENCONTRADO              │
│ → CRIA novo lead            │
└─────────────────────────────┘

// PRÓXIMA SINCRONIZAÇÃO:
SELECT * FROM tblleads WHERE mc_member_id = 12296

// RESULTADO:
┌─────────────────────────────┐
│ ENCONTRADO → Lead #1001     │
│ → ATUALIZA o lead existente │
└─────────────────────────────┘
```

### **Garantia:**
- ✅ **1 membro** do Multiclubes = **1 lead** no MyLeads
- ✅ **NUNCA duplica**
- ✅ Sempre atualiza dados mais recentes

---

## ⚙️ Configuração de Tamanho do Lote

### **Onde configurar:**
**MC Cotas G3 → Configurações → Tamanho do Lote (Batch)**

### **Valores recomendados:**

| Situação | Tamanho do Lote | Observações |
|----------|-----------------|-------------|
| **Poucos membros** (até 1.000) | 500 | Mais rápido |
| **Média quantidade** (1.000-10.000) | 100-200 | Balanceado ⭐ **RECOMENDADO** |
| **Muitos membros** (10.000+) | 50-100 | Mais seguro, usa menos memória |
| **Servidor fraco** | 25-50 | Evita timeout |
| **Servidor potente** | 500-1000 | Mais rápido |

### **Exemplo de cálculo:**

```
Total de membros: 10.000
Tamanho do lote: 100

Tempo por lote: ~2 segundos
Total de lotes: 100
Tempo total estimado: ~200 segundos (3 minutos)
```

---

## 🎯 Filtros Disponíveis

### **1. Sincronizar Apenas Titulares**
```sql
WHERE Titular = 'Titular'
```
- ✅ Sincroniza apenas titulares
- ❌ Ignora dependentes

### **2. Sincronizar Apenas Ativos**
```sql
WHERE MemberStatus = 'Ativo'
```
- ✅ Sincroniza apenas membros ativos
- ❌ Ignora inativos, cancelados, etc

### **Exemplo combinado:**
```
Filtros ativos:
☑ Apenas Titulares
☑ Apenas Ativos

Resultado:
Total no Multiclubes: 50.000 membros
Filtrados: 3.500 titulares ativos
Sincronizados: 3.500 leads
```

---

## ⏱️ Tempo de Execução

### **Fatores que influenciam:**

1. **Quantidade de membros**
2. **Tamanho do lote**
3. **Velocidade da conexão SQL Server**
4. **Poder de processamento do servidor**
5. **Leads novos vs atualizações** (criar é mais lento que atualizar)

### **Estimativa:**

```
Velocidade média: 50 membros/segundo

Exemplos:
├─ 100 membros → ~2 segundos
├─ 1.000 membros → ~20 segundos
├─ 5.000 membros → ~100 segundos (1min 40s)
├─ 10.000 membros → ~200 segundos (3min 20s)
└─ 50.000 membros → ~1000 segundos (16min 40s)
```

---

## 🔍 Monitoramento

### **Durante a sincronização:**

Se ativou **Log Detalhado**, verá no log de atividades:

```
[16:30:01] MC Cotas G3 - Processando lote 1/50 (100 membros)
[16:30:03] MC Cotas G3 - Processando lote 2/50 (100 membros)
[16:30:05] MC Cotas G3 - Processando lote 3/50 (100 membros)
...
```

### **Após conclusão:**

Na tela de **Sincronização**, verá:

```
✅ Sincronização concluída!

Total de membros: 5.000
Novos leads criados: 4.500
Leads atualizados: 500
Erros: 0
Tempo de execução: 120.45s
```

---

## ⚠️ Tratamento de Erros

### **Se der erro em um membro:**

```
LOTE 5 (membros 401-500)
├─ Membro 401 → ✅ Processado
├─ Membro 402 → ❌ ERRO: Email inválido
│  └─ Registra no log de erros
│  └─ CONTINUA processando
├─ Membro 403 → ✅ Processado
└─ ... (continua)

RESULTADO DO LOTE:
├─ 99 membros processados com sucesso
├─ 1 erro
└─ Continua para o próximo lote
```

### **No final:**
```
Erros: 1
Ver detalhes: [Link para página de erros]

Detalhes:
┌──────────────────────────────────────┐
│ Membro ID: 402                       │
│ Nome: JOSÉ DA SILVA                  │
│ Erro: Email inválido ''              │
└──────────────────────────────────────┘
```

---

## 💪 Benefícios do Sistema de Lotes

### **Antes (sem lotes):**
❌ Busca 50.000 membros de uma vez → 💥 **TIMEOUT!**
❌ Usa muita memória → 💥 **TRAVAMENTO!**
❌ Se der erro, perde tudo

### **Agora (com lotes):**
✅ Busca 100 por vez → ⚡ **RÁPIDO**
✅ Usa pouca memória → 🟢 **ESTÁVEL**
✅ Se der erro em um lote, os outros continuam
✅ Libera memória entre lotes
✅ Pode processar milhões de registros

---

## 📝 Resumo

1. **Sincronização em lotes** evita travamentos
2. **Tamanho configurável** (padrão: 100)
3. **Evita duplicatas** pelo campo `mc_member_id`
4. **Processa milhares** de membros sem problemas
5. **Registra tudo** em tabelas de controle e log
6. **Continua mesmo com erros** em membros individuais

**Recomendação:** Use lote de **100** para melhor desempenho! 🚀
