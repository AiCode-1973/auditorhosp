# 📊 Documentação - Cálculos de Percentuais do Relatório Mensal

## 📋 Visão Geral

Este documento explica como são realizados os cálculos automáticos dos campos percentuais no módulo **Relatório Mensal Consolidado** do sistema AuditorHosp.

Os percentuais são calculados automaticamente pelo sistema sempre que um registro é inserido, atualizado ou importado, **não sendo necessário informá-los manualmente**.

---

## 🔢 Fórmulas de Cálculo

### 1. **% Retirado**

**Fórmula:**
```
% Retirado = (Valor Retirado ÷ Valor Inicial) × 100
```

**Descrição:**  
Indica qual percentual do valor inicial foi retirado durante a auditoria.

**Base de Cálculo:** Valor Inicial

**Exemplo:**
- Valor Inicial: R$ 100.000,00
- Valor Retirado: R$ 5.000,00
- **% Retirado = (5.000 ÷ 100.000) × 100 = 5,00%**

---

### 2. **% Acrescentado**

**Fórmula:**
```
% Acrescentado = (Valor Acrescentado ÷ Valor Inicial) × 100
```

**Descrição:**  
Indica qual percentual foi acrescentado ao valor inicial durante a auditoria.

**Base de Cálculo:** Valor Inicial

**Exemplo:**
- Valor Inicial: R$ 100.000,00
- Valor Acrescentado: R$ 3.000,00
- **% Acrescentado = (3.000 ÷ 100.000) × 100 = 3,00%**

---

### 3. **% Glosado**

**Fórmula:**
```
% Glosado = (Valor Glosado ÷ Valor Final) × 100
```

**Descrição:**  
Indica qual percentual do valor final foi glosado (não aceito) pelo convênio.

**Base de Cálculo:** Valor Final

**Exemplo:**
- Valor Final: R$ 98.000,00
- Valor Glosado: R$ 10.000,00
- **% Glosado = (10.000 ÷ 98.000) × 100 = 10,20%**

---

### 4. **% Aceito**

**Fórmula:**
```
% Aceito = (Valor Aceito ÷ Valor Glosado) × 100
```

**Descrição:**  
Indica qual percentual do valor glosado foi aceito após recurso.

**Base de Cálculo:** Valor Glosado

**Exemplo:**
- Valor Glosado: R$ 10.000,00
- Valor Aceito: R$ 6.000,00
- **% Aceito = (6.000 ÷ 10.000) × 100 = 60,00%**

---

## 💻 Implementação no Código

### 📍 Local 1: Importação de Dados

**Arquivo:** `importar_relatorio_mensal.php`  
**Linhas:** 108-111

```php
$perc_retirado = $valor_inicial > 0 
    ? round(($valor_retirado / $valor_inicial) * 100, 2) 
    : 0;

$perc_acrescentado = $valor_inicial > 0 
    ? round(($valor_acrescentado / $valor_inicial) * 100, 2) 
    : 0;

$perc_glosado = $valor_final > 0 
    ? round(($valor_glosado / $valor_final) * 100, 2) 
    : 0;

$perc_aceito = $valor_glosado > 0 
    ? round(($valor_aceito / $valor_glosado) * 100, 2) 
    : 0;
```

**Quando é executado:**  
Ao importar dados pela primeira vez através do script de importação.

---

### 📍 Local 2: Formulário de Edição/Inserção

**Arquivo:** `relatorio_mensal_form.php`  
**Linhas:** 72-75

```php
$perc_retirado = $valor_inicial_db > 0 
    ? round(($valor_retirado_db / $valor_inicial_db) * 100, 2) 
    : 0;

$perc_acrescentado = $valor_inicial_db > 0 
    ? round(($valor_acrescentado_db / $valor_inicial_db) * 100, 2) 
    : 0;

$perc_glosado = $valor_final_db > 0 
    ? round(($valor_glosado_db / $valor_final_db) * 100, 2) 
    : 0;

$perc_aceito = $valor_glosado_db > 0 
    ? round(($valor_aceito_db / $valor_glosado_db) * 100, 2) 
    : 0;
```

**Quando é executado:**  
Ao inserir um novo registro ou editar um registro existente através do formulário web.

---

### 📍 Local 3: Dashboard (Totais Gerais)

**Arquivo:** `dashboard_relatorio_mensal.php`  
**Linhas:** 32-43

```php
$perc_retirado_geral = ($totais['total_inicial'] > 0) 
    ? ($totais['total_retirado'] / $totais['total_inicial']) * 100 
    : 0;

$perc_acrescentado_geral = ($totais['total_inicial'] > 0) 
    ? ($totais['total_acrescentado'] / $totais['total_inicial']) * 100 
    : 0;

$perc_glosado_geral = ($totais['total_final'] > 0) 
    ? ($totais['total_glosado'] / $totais['total_final']) * 100 
    : 0;

$perc_aceito_geral = ($totais['total_glosado'] > 0) 
    ? ($totais['total_aceito'] / $totais['total_glosado']) * 100 
    : 0;
```

**Quando é executado:**  
Ao visualizar o dashboard analítico, calculando percentuais sobre os valores totais consolidados.

---

## 🛡️ Proteções e Validações

### 1. **Proteção contra Divisão por Zero**

Todos os cálculos verificam se o divisor é maior que zero antes de executar a operação:

```php
$valor_inicial > 0 ? (calculo) : 0
```

**Comportamento:**
- Se o divisor for **0**, o resultado será **0%**
- Evita erros de execução e valores inconsistentes

### 2. **Arredondamento**

Todos os percentuais são arredondados para **2 casas decimais**:

```php
round($resultado, 2)
```

**Exemplo:**
- Cálculo bruto: 10.204081632653
- Valor armazenado: **10.20**

### 3. **Tipos de Dados**

No banco de dados, os percentuais são armazenados como:
```sql
perc_retirado DECIMAL(5, 2)
```

**Significado:**
- **5 dígitos** no total
- **2 casas** decimais
- Valores possíveis: **-999.99** até **999.99**

---

## 📊 Exemplo Completo

### Cenário Real

**Dados de Entrada:**
| Campo | Valor |
|-------|-------|
| Competência | 07/2024 |
| Convênio | Prevent Senior |
| Valor Inicial | R$ 250.628,55 |
| Valor Retirado | R$ 17.168,18 |
| Valor Acrescentado | R$ 17.658,16 |
| Valor Final | R$ 265.055,33 |
| Valor Glosado | R$ 27,27 |
| Valor Aceito | R$ 27,27 |

### Cálculos Automáticos

**1. % Retirado:**
```
(17.168,18 ÷ 250.628,55) × 100 = 6,85%
```

**2. % Acrescentado:**
```
(17.658,16 ÷ 250.628,55) × 100 = 7,05%
```

**3. % Glosado:**
```
(27,27 ÷ 265.055,33) × 100 = 0,01%
```

**4. % Aceito:**
```
(27,27 ÷ 27,27) × 100 = 100,00%
```

### Resultado Final no Banco

```sql
INSERT INTO relatorio_mensal_consolidado VALUES (
    competencia = '2024-07-01',
    convenio_id = 1,
    valor_inicial = 250628.55,
    valor_retirado = 17168.18,
    valor_acrescentado = 17658.16,
    valor_final = 265055.33,
    valor_glosado = 27.27,
    valor_aceito = 27.27,
    perc_retirado = 6.85,
    perc_acrescentado = 7.05,
    perc_glosado = 0.01,
    perc_aceito = 100.00
);
```

---

## 🎯 Fluxo de Dados

```
┌─────────────────────────┐
│  Entrada de Dados       │
│  (Formulário/Importação)│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│  Conversão de Moeda     │
│  (R$ 1.000,00 → 1000.00)│
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│  Cálculo de Percentuais │
│  (Fórmulas aplicadas)   │
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│  Arredondamento         │
│  (2 casas decimais)     │
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│  Gravação no Banco      │
│  (MySQL)                │
└─────────────────────────┘
```

---

## ✅ Boas Práticas

### ✔️ **O que FAZER:**

1. **Informe apenas os valores monetários** no formulário
2. **Deixe o sistema calcular** os percentuais automaticamente
3. **Confie nos valores calculados** - as fórmulas são consistentes
4. **Use o botão "Ver em %"** no dashboard para alternar visualização

### ❌ **O que NÃO fazer:**

1. **Não tente calcular percentuais manualmente**
2. **Não edite diretamente os campos de percentual no banco**
3. **Não presuma que % Retirado e % Glosado usam a mesma base**
4. **Não ignore valores zerados** - eles são válidos e protegidos

---

## 🔍 Onde Visualizar

### 1. **Relatório Mensal (Tabular)**
- **Arquivo:** `relatorio_mensal.php`
- **Exibição:** Tabela com todos os valores e percentuais
- **Filtros:** Por mês e convênio

### 2. **Dashboard Mensal (Gráfico)**
- **Arquivo:** `dashboard_relatorio_mensal.php`
- **Exibição:** Cards, gráficos e análises visuais
- **Recursos:** Toggle R$ ↔ %, filtros dinâmicos

### 3. **Formulário de Edição**
- **Arquivo:** `relatorio_mensal_form.php`
- **Exibição:** Os percentuais são calculados ao salvar
- **Nota:** Não há campos de entrada para percentuais

---

## 🗄️ Estrutura do Banco de Dados

### Tabela: `relatorio_mensal_consolidado`

```sql
CREATE TABLE relatorio_mensal_consolidado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competencia DATE NOT NULL,
    convenio_id INT NOT NULL,
    
    -- Valores Monetários
    valor_inicial DECIMAL(15, 2) DEFAULT 0.00,
    valor_retirado DECIMAL(15, 2) DEFAULT 0.00,
    valor_acrescentado DECIMAL(15, 2) DEFAULT 0.00,
    valor_final DECIMAL(15, 2) DEFAULT 0.00,
    valor_glosado DECIMAL(15, 2) DEFAULT 0.00,
    valor_aceito DECIMAL(15, 2) DEFAULT 0.00,
    
    -- Percentuais (Calculados Automaticamente)
    perc_retirado DECIMAL(5, 2) DEFAULT 0.00,
    perc_acrescentado DECIMAL(5, 2) DEFAULT 0.00,
    perc_glosado DECIMAL(5, 2) DEFAULT 0.00,
    perc_aceito DECIMAL(5, 2) DEFAULT 0.00,
    
    FOREIGN KEY (convenio_id) REFERENCES convenios(id),
    UNIQUE KEY unique_competencia_convenio (competencia, convenio_id)
) ENGINE=InnoDB;
```

---

## 📞 Suporte

Para dúvidas sobre os cálculos ou comportamentos inesperados:

1. Verifique se os **valores base** estão corretos
2. Confirme que **não há divisão por zero** nos dados
3. Verifique o **log de importação** em caso de importação em lote
4. Consulte o código-fonte nos arquivos mencionados acima

---

## 📝 Changelog

| Data | Versão | Descrição |
|------|--------|-----------|
| 05/01/2026 | 1.0 | Documentação inicial dos cálculos de percentuais |

---

## 📄 Licença

Este documento é parte do sistema **AuditorHosp** e deve ser mantido junto ao código-fonte.

---

**Desenvolvido por:** AuditorHosp Team  
**Última atualização:** Janeiro de 2026
