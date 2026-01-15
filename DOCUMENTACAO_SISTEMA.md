# 📊 AuditorHosp - Sistema de Auditoria Hospitalar

**Versão:** 1.0  
**Data:** 15 de Janeiro de 2026  
**Desenvolvido para:** Gestão completa de auditoria e faturamento hospitalar

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Objetivo do Sistema](#objetivo-do-sistema)
3. [Arquitetura Técnica](#arquitetura-técnica)
4. [Módulos do Sistema](#módulos-do-sistema)
5. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
6. [Fluxo de Trabalho](#fluxo-de-trabalho)
7. [Funcionalidades Detalhadas](#funcionalidades-detalhadas)
8. [Interface do Usuário](#interface-do-usuário)
9. [Segurança e Controles](#segurança-e-controles)
10. [Recursos Técnicos](#recursos-técnicos)
11. [Guia de Uso](#guia-de-uso)

---

## 🎯 Visão Geral

O **AuditorHosp** é uma plataforma web completa desenvolvida em PHP para gestão de auditoria e faturamento hospitalar. O sistema controla todo o ciclo financeiro desde o atendimento ao paciente até o recebimento dos valores, incluindo gestão de glosas, recursos, contratos e relatórios consolidados.

### Principais Características
- ✅ Gestão completa de atendimentos (Internação e PA/Ambulatório)
- ✅ Auditoria de valores com cálculos automáticos de percentuais
- ✅ Controle de glosas e recursos de glosa
- ✅ Gestão de contratos com convênios
- ✅ Sistema de documentação (upload de arquivos)
- ✅ Consolidação automática de relatórios mensais
- ✅ Dashboards com gráficos interativos
- ✅ Exportação para Excel
- ✅ Sistema de usuários com níveis de acesso
- ✅ Logs de auditoria

---

## 🎯 Objetivo do Sistema

Gerenciar todo o ciclo de **faturamento hospitalar com convênios médicos**, incluindo:

1. **Registro de Atendimentos** - Internações e Pronto Atendimento
2. **Auditoria de Valores** - Análise e ajustes nos valores
3. **Gestão de Glosas** - Valores não aceitos pelos convênios
4. **Recursos de Glosa** - Contestação e recuperação de valores glosados
5. **Controle de Contratos** - Documentos e vigência
6. **Relatórios Consolidados** - Visão mensal por convênio
7. **Análise de Indicadores** - Dashboards e gráficos

---

## 🏗️ Arquitetura Técnica

### Tecnologias Utilizadas

| Componente | Tecnologia |
|------------|------------|
| **Backend** | PHP 7.4+ |
| **Banco de Dados** | MySQL 5.7+ |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Framework CSS** | Tailwind CSS (CDN) |
| **Gráficos** | Chart.js |
| **Conexão BD** | PDO (PHP Data Objects) |
| **Servidor Web** | Apache (XAMPP) |

### Configuração do Banco de Dados

**Arquivo:** `db_config.php`

```php
$host = '186.209.113.107';
$user = 'dema5738_auditorhosp';
$pass = 'Dema@1973';
$dbname = 'dema5738_auditorhosp';
```

- **Tipo:** MySQL Remoto
- **Conexão:** PDO com modo de erro exception
- **Charset:** UTF-8 (utf8mb4)
- **Prepared Statements:** Habilitado para segurança

### Sistema de Autenticação

**Arquivos:** `login.php`, `auth.php`, `logout.php`

- **Método:** Sessão PHP
- **Senha:** Hash com `password_hash()` e `password_verify()`
- **Níveis de Acesso:**
  - `admin` - Acesso total (incluindo gestão de usuários)
  - `usuario` - Acesso operacional
- **Controle:** Todas as páginas verificam autenticação via `auth.php`

---

## 📦 Módulos do Sistema

### 1️⃣ Gestão de Convênios

**Arquivos:** `convenios.php`, `convenios_form.php`

**Funcionalidades:**
- Cadastro de convênios médicos (planos de saúde)
- Listagem com busca
- Edição e exclusão (com validação de vínculos)
- Base fundamental para todo o faturamento

**Campos:**
- ID
- Nome do Convênio

---

### 2️⃣ Contratos com Convênios

**Arquivos:** `contratos.php`, `contratos_form.php`, `visualizar_contrato.php`

**Funcionalidades:**
- Cadastro de contratos vinculados aos convênios
- Upload de arquivos (PDF, DOC, DOCX, JPG, PNG - máx 30MB)
- Controle de vigência (data início/fim)
- Alertas de vencimento
- Status ativo/inativo
- Visualização de documentos no navegador

**Campos:**
- Convênio vinculado
- Número do contrato
- Data de início
- Data de fim
- Valor do contrato (opcional)
- Arquivo do contrato
- Observações
- Status (ativo/inativo)

**Indicadores Visuais:**
- 🟢 **Verde** - Contrato vigente
- 🟡 **Amarelo** - Vence em 30 dias
- 🔴 **Vermelho** - Vencido
- ⚫ **Cinza** - Inativo

**Cards Estatísticos:**
- Total de contratos
- Contratos ativos
- Contratos vencendo (30 dias)
- Contratos vencidos

**Pasta de Upload:** `uploads/contratos/`

---

### 3️⃣ Internações (Atendimentos Hospitalares)

**Arquivos:** `internacoes.php`, `registrar_internacao.php`, `excluir_internacao.php`

**Funcionalidades:**
- Registro de atendimentos de internação
- Auditoria de valores com cálculos automáticos
- Filtros por paciente, guia, status, competência
- Consolidação para relatório mensal
- Exportação Excel
- Sistema de status

**Campos de Identificação:**
- Guia do Paciente
- Nome do Paciente
- Convênio
- Data de Recebimento
- Competência (mês/ano)
- Observações

**Campos de Auditoria (Valores):**
- **Valor Inicial** - Valor original da conta
- **Valor Retirado** - Itens/procedimentos retirados
- **Valor Acrescentado** - Itens/procedimentos adicionados
- **Valor Final** - Calculado: Inicial - Retirado + Acrescentado
- **Valor Glosado** - Valores não aceitos pelo convênio
- **Valor Aceito** - Glosas revertidas após recurso
- **Valor Faturado** - Valor efetivamente faturado

**Percentuais (Calculados Automaticamente):**
- **% Retirado** = (Valor Retirado / Valor Inicial) × 100
- **% Acrescentado** = (Valor Acrescentado / Valor Inicial) × 100
- **% Glosado** = (Valor Glosado / Valor Final) × 100
- **% Aceito** = (Valor Aceito / Valor Glosado) × 100

**Status Possíveis:**
- Em Análise
- Auditado
- Faturado
- Recursado
- Recebido

**Funcionalidade de Consolidação:**
- Botão "Consolidar para Relatório Mensal"
- Agrupa atendimentos auditados por competência/convênio
- Gera registros na tabela `relatorio_mensal_consolidado`

---

### 4️⃣ PA/Ambulatório (Pronto Atendimento)

**Arquivos:** `pa_ambulatorio.php`, `pa_ambulatorio_form.php`, `excluir_pa_ambulatorio.php`

**Funcionalidades:**
- Registro de atendimentos ambulatoriais
- Separação por setores
- Mesma estrutura de auditoria das internações
- Sistema de paginação (5, 10, 20, 30, 50 registros)
- Filtros avançados
- Consolidação separada

**Campos Adicionais:**
- **Setor** - Pronto Socorro, Ambulatório, Urgência, etc.

**Todos os demais campos e cálculos:** Idênticos ao módulo de Internações

**Consolidação:**
- Arquivo: `consolidar_pa_ambulatorio.php`
- Gera: `relatorio_mensal_pa_consolidado`
- Agrupamento adicional por setor

---

### 5️⃣ Gestão de Recurso de Glosa (Sistema Principal)

**Arquivos:** `faturas.php`, `registrar_auditoria.php`, `index.php`, `dashboard_v2.php`

Este é o **módulo principal** do sistema, baseado em 3 tabelas relacionadas:

#### a) FATURAS (Faturamento)
Valores totais faturados por competência e convênio.

**Campos:**
- ID
- Convênio
- Data de Competência
- Valor Total

#### b) GLOSAS (Valores Não Aceitos)
Valores glosados (não pagos) pelos convênios.

**Campos:**
- ID
- Fatura ID (FK)
- Valor da Glosa

#### c) RECURSOS (Contestações)
Recursos de glosa e valores recuperados.

**Campos:**
- ID
- Fatura ID (FK)
- Valor Recursado (valor contestado)
- Valor Aceito (glosa revertida)
- Valor Recebido (efetivamente pago)

#### Formulário de Registro (`registrar_auditoria.php`)
**Interface simplificada para cadastro:**
- Convênio
- Mês de Competência
- Valor de Faturamento
- Valor de Glosa
- Valor Recursado
- Valor Aceito
- Valor Recebido

**Sistema automaticamente:**
- Insere na tabela `faturas`
- Insere na tabela `glosas` (se houver)
- Insere na tabela `recursos` (se houver)

#### Dashboard Principal (`index.php`)
**Relatório consolidado com:**
- Agrupamento por Mês ou Convênio
- Filtros: Mês, Convênio
- Colunas:
  - Competência
  - Convênio
  - Faturamento
  - Glosado
  - Recursado
  - Aceito
  - Recebido
  - % Glosado (do Faturamento)
  - % Recursado (do Glosado)
  - % Aceito (do Recursado)
  - % Recebido (do Aceito)
- Totalizadores
- Exportação Excel
- Botão "Novo Registro"

---

### 6️⃣ Relatórios e Dashboards

#### Dashboard Principal (`index.php`)
- Tabela agrupada por mês ou convênio
- Filtros dinâmicos
- Totais consolidados
- Percentuais calculados
- Exportação Excel

#### Dashboard V2 - Gráficos (`dashboard_v2.php`)
**Cards Estatísticos:**
- 💰 Total Faturado
- ❌ Total Glosado
- 📊 % Glosa Geral
- 🔄 Total Recursado
- ✅ Total Aceito

**Gráficos Interativos (Chart.js):**
1. **Evolução Mensal** (Linha/Barra)
   - Faturamento vs Glosas por mês
   - Últimos 12 meses

2. **Faturamento por Convênio** (Rosca/Pizza)
   - Distribuição percentual
   - Top convênios

3. **Análise de Glosas**
   - Taxa de glosa por convênio
   - Comparativo

4. **Taxa de Recuperação**
   - Percentual aceito por convênio

**Filtros:**
- Filtro por mês
- Atualização dinâmica dos gráficos

#### Relatório Mensal Consolidado (`relatorio_mensal.php`)
**Origem:** Consolidação de Internações

**Visualização:**
- Agrupado por competência e convênio
- Todos os valores de auditoria
- Percentuais automáticos
- Totalizadores
- Exportação Excel

**Filtros:**
- Mês
- Convênio

#### Relatório PA/Ambulatório (`relatorio_mensal_pa_ambulatorio.php`)
**Origem:** Consolidação de PA/Ambulatório

**Diferencial:**
- Separado por setor
- Quantidade de atendimentos
- Mesma estrutura de valores

---

### 7️⃣ Sistema de Documentos

O sistema possui **3 módulos separados** de documentação:

#### a) Documentos de Glosa Gerais
**Arquivos:** `documentos.php`, `documentos_form.php`, `excluir_documento.php`

**Funcionalidades:**
- Upload de documentos relacionados a glosas
- Múltiplos anexos por documento
- Organização por competência e convênio
- Filtros e busca
- Contador de anexos

**Campos:**
- Convênio
- Competência
- Título/Descrição
- Anexos (múltiplos arquivos)

**Tabelas BD:**
- `documentos_glosa` - Documento principal
- `documentos_glosa_anexos` - Arquivos anexados

#### b) Documentos de Internação
**Arquivos:** `documentos_internacao.php`, `documentos_internacao_form.php`

**Mesma estrutura** dos documentos gerais, mas específicos para internações.

**Tabelas BD:**
- `documentos_internacao`
- `documentos_internacao_anexos`

#### c) Documentos PA/Ambulatório
**Arquivos:** `documentos_pa_ambulatorio.php`, `documentos_pa_ambulatorio_form.php`

**Mesma estrutura**, específico para PA/Ambulatório.

**Tabelas BD:**
- `documentos_pa_ambulatorio`
- `documentos_pa_ambulatorio_anexos`

**Características Comuns:**
- Upload seguro com validação
- Armazenamento em subpastas organizadas
- Visualização e download
- Exclusão de anexos individuais
- Vínculo com convênio e competência

---

### 8️⃣ Gestão de Usuários

**Arquivos:** `usuarios.php`

**Restrição:** Apenas usuários **admin** têm acesso

**Funcionalidades:**
- CRUD completo de usuários
- Criação de novos usuários
- Edição de dados (nome, email, nível)
- Alteração de senha (opcional)
- Ativar/Desativar usuários
- Exclusão (exceto própria conta)
- Visualização de último acesso

**Campos:**
- Nome
- E-mail (único)
- Senha (hash bcrypt)
- Nível (admin/usuario)
- Status (ativo/inativo)
- Data de Cadastro
- Último Acesso

**Segurança:**
- Não é possível excluir a própria conta
- Senhas sempre em hash
- Validação de e-mail único

---

### 9️⃣ Logs de Auditoria

**Arquivos:** `logs_atendimento.php`, `criar_tabela_logs.php`

**Funcionalidade:**
- Registro de todas as operações importantes
- Rastreabilidade completa
- Histórico de alterações

**Informações Registradas:**
- Usuário que realizou a ação
- Data e hora
- Tipo de operação
- Detalhes da alteração

---

### 🔟 Exportação de Dados

**Arquivos de Exportação:**
- `exportar_excel.php` - Dashboard principal
- `exportar_internacoes_excel.php` - Internações
- `exportar_relatorio_mensal_excel.php` - Relatório mensal

**Formato:** Excel (.xls)

**Características:**
- Mantém filtros aplicados
- Inclui totalizadores
- Formatação preservada
- Cabeçalhos descritivos

---

## 🗄️ Estrutura do Banco de Dados

### Tabelas Principais

```
├── usuarios
│   ├── id (PK)
│   ├── nome
│   ├── email (UNIQUE)
│   ├── senha (HASH)
│   ├── nivel (admin/usuario)
│   ├── ativo (1/0)
│   ├── data_cadastro
│   └── ultimo_acesso
│
├── convenios
│   ├── id (PK)
│   └── nome_convenio
│
├── contratos
│   ├── id (PK)
│   ├── convenio_id (FK → convenios)
│   ├── numero_contrato
│   ├── data_inicio
│   ├── data_fim
│   ├── valor_contrato
│   ├── arquivo_contrato
│   ├── data_upload
│   ├── observacoes
│   ├── ativo
│   ├── data_criacao
│   └── usuario_criacao
│
├── internacoes
│   ├── id (PK)
│   ├── convenio_id (FK → convenios)
│   ├── guia_paciente
│   ├── paciente
│   ├── data_recebimento
│   ├── competencia
│   ├── valor_inicial
│   ├── valor_retirado
│   ├── valor_acrescentado
│   ├── valor_total (CALCULADO)
│   ├── valor_glosado
│   ├── valor_aceito
│   ├── valor_faturado
│   ├── status
│   ├── observacoes
│   └── data_cadastro
│
├── pa_ambulatorio
│   ├── id (PK)
│   ├── convenio_id (FK → convenios)
│   ├── setor
│   ├── guia_paciente
│   ├── data_recebimento
│   ├── competencia
│   ├── valor_inicial
│   ├── valor_retirado
│   ├── valor_acrescentado
│   ├── valor_total (CALCULADO)
│   ├── valor_glosado
│   ├── valor_aceito
│   ├── valor_faturado
│   ├── status
│   ├── observacoes
│   └── data_cadastro
│
├── relatorio_mensal_consolidado
│   ├── id (PK)
│   ├── convenio_id (FK → convenios)
│   ├── competencia
│   ├── valor_inicial (SUM)
│   ├── valor_retirado (SUM)
│   ├── valor_acrescentado (SUM)
│   ├── valor_final (SUM)
│   ├── valor_glosado (SUM)
│   ├── valor_aceito (SUM)
│   ├── perc_retirado (CALC)
│   ├── perc_acrescentado (CALC)
│   ├── perc_glosado (CALC)
│   └── perc_aceito (CALC)
│
├── relatorio_mensal_pa_consolidado
│   ├── id (PK)
│   ├── convenio_id (FK → convenios)
│   ├── setor
│   ├── competencia
│   ├── valor_inicial (SUM)
│   ├── valor_retirado (SUM)
│   ├── valor_acrescentado (SUM)
│   ├── valor_final (SUM)
│   ├── valor_glosado (SUM)
│   ├── valor_aceito (SUM)
│   ├── valor_faturado (SUM)
│   └── qtd_atendimentos (COUNT)
│
├── faturas
│   ├── id (PK)
│   ├── convenio_id (FK → convenios)
│   ├── data_competencia
│   └── valor_total
│
├── glosas
│   ├── id (PK)
│   ├── fatura_id (FK → faturas)
│   └── valor_glosa
│
├── recursos
│   ├── id (PK)
│   ├── fatura_id (FK → faturas)
│   ├── valor_recursado
│   ├── valor_aceito
│   └── valor_recebido
│
├── documentos_glosa
│   ├── id (PK)
│   ├── convenio_id (FK → convenios)
│   ├── competencia
│   ├── titulo
│   ├── descricao
│   └── data_cadastro
│
├── documentos_glosa_anexos
│   ├── id (PK)
│   ├── documento_id (FK → documentos_glosa)
│   ├── nome_arquivo
│   ├── caminho_arquivo
│   └── data_upload
│
├── documentos_internacao
│   └── (mesma estrutura de documentos_glosa)
│
├── documentos_internacao_anexos
│   └── (mesma estrutura de documentos_glosa_anexos)
│
├── documentos_pa_ambulatorio
│   └── (mesma estrutura de documentos_glosa)
│
├── documentos_pa_ambulatorio_anexos
│   └── (mesma estrutura de documentos_glosa_anexos)
│
└── logs_atendimento
    ├── id (PK)
    ├── usuario_id (FK → usuarios)
    ├── tipo_operacao
    ├── descricao
    ├── dados_alteracao
    └── data_hora
```

### Relacionamentos

```
convenios (1) ─────── (N) contratos
convenios (1) ─────── (N) internacoes
convenios (1) ─────── (N) pa_ambulatorio
convenios (1) ─────── (N) faturas
convenios (1) ─────── (N) relatorio_mensal_consolidado
convenios (1) ─────── (N) relatorio_mensal_pa_consolidado
convenios (1) ─────── (N) documentos_glosa
convenios (1) ─────── (N) documentos_internacao
convenios (1) ─────── (N) documentos_pa_ambulatorio

faturas (1) ─────── (N) glosas
faturas (1) ─────── (N) recursos

documentos_glosa (1) ─────── (N) documentos_glosa_anexos
documentos_internacao (1) ─────── (N) documentos_internacao_anexos
documentos_pa_ambulatorio (1) ─────── (N) documentos_pa_ambulatorio_anexos

usuarios (1) ─────── (N) logs_atendimento
```

---

## 🔄 Fluxo de Trabalho Típico

### Cenário 1: Gestão de Internações

```
1. Cadastrar Convênios
   ↓
2. Registrar Contratos (upload de documentos)
   ↓
3. Registrar Atendimento de Internação
   - Identificação do paciente
   - Dados da guia
   - Valores iniciais
   ↓
4. Realizar Auditoria
   - Preencher valores de auditoria
   - Sistema calcula percentuais automaticamente
   - Mudar status para "Auditado"
   ↓
5. Upload de Documentos (se necessário)
   - Documentos de Internação
   - Anexar arquivos relevantes
   ↓
6. Consolidar para Relatório Mensal
   - Botão "Consolidar"
   - Sistema agrupa por competência/convênio
   - Gera registros consolidados
   ↓
7. Visualizar Relatórios
   - Relatório Mensal Consolidado
   - Dashboards com gráficos
   ↓
8. Exportar para Excel
   - Gerar relatórios para análise externa
```

### Cenário 2: Gestão de Glosas e Recursos

```
1. Cadastrar Faturamento
   - registrar_auditoria.php
   - Informar valor faturado
   ↓
2. Registrar Glosas
   - Informar valores glosados pelo convênio
   ↓
3. Contestar Glosas (Recurso)
   - Informar valores recursados
   ↓
4. Registrar Valores Aceitos
   - Glosas revertidas após análise
   ↓
5. Registrar Valores Recebidos
   - Valores efetivamente pagos
   ↓
6. Analisar Indicadores
   - Dashboard Principal (index.php)
   - Dashboard V2 com gráficos
   - Percentuais automáticos:
     * % Glosado do Faturamento
     * % Recursado do Glosado
     * % Aceito do Recursado
     * % Recebido do Aceito
   ↓
7. Exportar Relatórios
   - Excel para análise gerencial
```

---

## 🎨 Interface do Usuário

### Layout Geral

**Framework:** Tailwind CSS (via CDN)

**Componentes:**
- Header fixo com menu dropdown
- Navegação organizada por módulos
- Conteúdo principal responsivo
- Footer (minimal)

### Menu de Navegação

**Estrutura:**
```
AuditorHosp (Logo)
├── Gestão Recurso de Glosa
│   ├── Relatórios
│   ├── Lançamentos
│   ├── Dashboard V2
│   └── Documentos
│
├── Gestão Mensal
│   ├── Internações
│   ├── PA/Ambulatório
│   ├── Relatório Mensal (Internações)
│   ├── Relatório PA/Ambulatório
│   ├── Consolidar Internações
│   ├── Consolidar PA/Ambulatório
│   ├── Documentos Internação
│   └── Documentos PA/Ambulatório
│
├── Cadastros
│   ├── Convênios
│   ├── Contratos
│   └── Usuários (apenas admin)
│
└── [Nome do Usuário]
    ├── Perfil
    └── Sair
```

### Paleta de Cores

```css
- Primary: #2563eb (azul)
- Success: #10b981 (verde)
- Warning: #f59e0b (amarelo)
- Danger: #ef4444 (vermelho)
- Background: #f9fafb (cinza claro)
- Text: #1f2937 (cinza escuro)
```

### Componentes Padrão

#### Cards
- Fundo branco
- Sombra suave
- Bordas arredondadas
- Padding adequado

#### Tabelas
- Cabeçalho com fundo cinza
- Linhas alternadas com hover
- Ações alinhadas à direita
- Totalizadores em negrito

#### Formulários
- Labels descritivos
- Inputs com borda
- Validação visual
- Botões com cores semânticas

#### Botões
- **Primário** (Azul): Ações principais
- **Sucesso** (Verde): Criar/Adicionar
- **Perigo** (Vermelho): Excluir
- **Roxo**: Consolidar
- **Cinza**: Secundário/Cancelar

#### Ícones
- SVG inline
- Heroicons (estilo)
- Tamanho padrão: 5x5

---

## 🔒 Segurança e Controles

### Autenticação

**Método:** Sessão PHP com validação em todas as páginas

**Arquivo de Proteção:** `auth.php` (incluído em `header.php`)

**Verificações:**
```php
// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}
```

### Senhas

**Armazenamento:** Hash bcrypt

**Criação:**
```php
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
```

**Verificação:**
```php
password_verify($senha, $usuario['senha'])
```

### SQL Injection

**Prevenção:** Prepared Statements (PDO)

**Exemplo:**
```php
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
```

**Nunca usar:**
```php
// ERRADO - Vulnerável a SQL Injection
$sql = "SELECT * FROM usuarios WHERE email = '$email'";
```

### XSS (Cross-Site Scripting)

**Prevenção:** `htmlspecialchars()` em todas as saídas

**Exemplo:**
```php
<?php echo htmlspecialchars($convenio['nome_convenio']); ?>
```

### CSRF (Cross-Site Request Forgery)

**Confirmação de Exclusão:**
```javascript
onsubmit="return confirm('Tem certeza que deseja excluir?')"
```

### Upload de Arquivos

**Validações:**
1. **Tipo de Arquivo** - Extensões permitidas
2. **Tamanho** - Limite de 30MB
3. **Destino Seguro** - Pasta específica fora do webroot quando possível
4. **Nome Único** - Evita sobrescrita

**Exemplo (Contratos):**
```php
$extensoes_permitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
$tamanho_maximo = 30 * 1024 * 1024; // 30MB
```

### Controle de Acesso

**Verificação de Nível:**
```php
// Apenas admin
if ($_SESSION['usuario_nivel'] != 'admin') {
    header('Location: index.php');
    exit();
}
```

### Logs de Auditoria

**Registro de Operações Sensíveis:**
- Criação de registros
- Edição de valores
- Exclusão de dados
- Login/Logout

**Informações Armazenadas:**
- Quem fez
- O que fez
- Quando fez
- Dados alterados

---

## 💻 Recursos Técnicos

### Cálculos Automáticos

**Percentuais calculados pelo sistema:**

```php
// % Retirado
$perc_retirado = $valor_inicial > 0 
    ? round(($valor_retirado / $valor_inicial) * 100, 2) 
    : 0;

// % Acrescentado
$perc_acrescentado = $valor_inicial > 0 
    ? round(($valor_acrescentado / $valor_inicial) * 100, 2) 
    : 0;

// % Glosado
$perc_glosado = $valor_final > 0 
    ? round(($valor_glosado / $valor_final) * 100, 2) 
    : 0;

// % Aceito
$perc_aceito = $valor_glosado > 0 
    ? round(($valor_aceito / $valor_glosado) * 100, 2) 
    : 0;
```

**Vantagem:** Usuário não precisa calcular manualmente, evita erros.

### Consolidação Automática

**Processo:**
1. Seleciona todos os atendimentos com status "Auditado"
2. Agrupa por competência (mês/ano) e convênio
3. Soma todos os valores
4. Calcula percentuais médios
5. Insere na tabela consolidada

**SQL Exemplo:**
```sql
SELECT 
    DATE_FORMAT(i.competencia, '%Y-%m-01') as competencia_consolidada,
    i.convenio_id,
    SUM(i.valor_inicial) as valor_inicial,
    SUM(i.valor_retirado) as valor_retirado,
    -- ... demais campos
FROM internacoes i
WHERE i.status = 'Auditado'
GROUP BY DATE_FORMAT(i.competencia, '%Y-%m'), i.convenio_id
```

### Paginação

**Implementação (PA/Ambulatório):**
- Seletor de registros por página (5, 10, 20, 30, 50)
- Navegação entre páginas
- Contadores (mostrando X de Y)
- Totais considerando todos os registros (não só página atual)

### Filtros Dinâmicos

**Características:**
- Múltiplos filtros simultâneos
- Persistência via GET parameters
- Atualização instantânea
- Botão "Limpar Filtros"

**Exemplo:**
```
?filtro_convenio=5&filtro_mes=2026-01&agrupar_por=convenio
```

### Exportação Excel

**Biblioteca:** Nativa do PHP (headers + HTML table)

**Processo:**
```php
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="relatorio.xls"');
```

**Vantagem:** Não requer biblioteca externa.

### Gráficos Interativos

**Biblioteca:** Chart.js (via CDN)

**Tipos Utilizados:**
- Line Chart (Evolução mensal)
- Bar Chart (Comparativos)
- Doughnut Chart (Distribuição por convênio)
- Mixed Charts (Faturamento vs Glosas)

**Configuração:**
```javascript
new Chart(ctx, {
    type: 'line',
    data: {
        labels: meses,
        datasets: [{
            label: 'Faturamento',
            data: valores
        }]
    },
    options: {
        responsive: true,
        // ...
    }
});
```

---

## 📖 Guia de Uso

### Primeiro Acesso

1. **Acessar:** `http://seuservidor/auditorhosp/login.php`
2. **Credenciais:** Usuário inicial criado via `criar_usuario_inicial.php`
3. **Alterar Senha:** Recomendado após primeiro login

### Configuração Inicial

#### 1. Cadastrar Convênios
- Menu: Cadastros → Convênios
- Botão: "+ Novo Convênio"
- Informar: Nome do convênio
- Salvar

#### 2. Cadastrar Contratos (Opcional)
- Menu: Cadastros → Contratos
- Botão: "+ Novo Contrato"
- Selecionar convênio
- Preencher dados do contrato
- Fazer upload do arquivo
- Salvar

#### 3. Criar Usuários (Admin)
- Menu: Cadastros → Usuários
- Botão: "+ Novo Usuário"
- Preencher dados
- Definir nível (admin/usuario)
- Salvar

### Operação Diária

#### Registrar Atendimentos de Internação

1. Menu: Gestão Mensal → Internações
2. Botão: "+ Novo Atendimento"
3. Preencher:
   - Guia do paciente
   - Nome do paciente
   - Convênio
   - Data de recebimento
   - Competência (mês/ano)
   - Valor inicial
4. Salvar com status "Em Análise"

#### Realizar Auditoria

1. Localizar o atendimento na lista
2. Clicar em "Editar"
3. Preencher valores de auditoria:
   - Valor retirado
   - Valor acrescentado
   - Valor glosado
   - Valor aceito
   - Valor faturado
4. Sistema calcula percentuais automaticamente
5. Alterar status para "Auditado"
6. Salvar

#### Consolidar Relatórios Mensais

1. Menu: Gestão Mensal → Internações
2. Botão: "⚡ Consolidar para Relatório Mensal"
3. Sistema processa automaticamente
4. Mensagem de confirmação
5. Visualizar em: Gestão Mensal → Relatório Mensal

#### Registrar Glosas e Recursos

**Método 1 - Via Gestão de Recurso de Glosa:**
1. Menu: Gestão Recurso de Glosa → Lançamentos
2. Botão: "+ Novo Registro"
3. Preencher:
   - Convênio
   - Mês de competência
   - Valor de faturamento
   - Valor de glosa
   - Valor recursado
   - Valor aceito
   - Valor recebido
4. Salvar (sistema distribui nas tabelas corretas)

**Método 2 - Direto nas tabelas (avançado):**
- Inserir manualmente em faturas, glosas e recursos

#### Visualizar Dashboards

**Dashboard Principal:**
1. Menu: Gestão Recurso de Glosa → Relatórios
2. Aplicar filtros (mês, convênio)
3. Alternar agrupamento (mês/convênio)
4. Analisar percentuais
5. Exportar Excel se necessário

**Dashboard V2 (Gráficos):**
1. Menu: Gestão Recurso de Glosa → Dashboard V2
2. Filtrar por mês (opcional)
3. Analisar:
   - Cards com totais
   - Evolução mensal
   - Distribuição por convênio
   - Taxas de glosa e recuperação

#### Upload de Documentos

1. Selecionar tipo de documento:
   - Documentos Gerais
   - Documentos Internação
   - Documentos PA/Ambulatório
2. Botão: "+ Novo Documento"
3. Preencher:
   - Convênio
   - Competência
   - Título/Descrição
4. Upload de arquivos (múltiplos)
5. Salvar

### Exportação de Relatórios

1. Aplicar filtros desejados
2. Botão: "Exportar Excel"
3. Arquivo será baixado automaticamente
4. Abrir no Excel/LibreOffice

### Gestão de Usuários (Admin)

**Criar Usuário:**
1. Menu: Cadastros → Usuários
2. Botão: "+ Novo Usuário"
3. Preencher dados
4. Definir nível
5. Salvar

**Editar Usuário:**
1. Localizar usuário
2. Clicar em "Editar"
3. Alterar dados
4. Senha opcional (deixar em branco para manter)
5. Ativar/Desativar
6. Salvar

**Excluir Usuário:**
1. Localizar usuário
2. Clicar em "Excluir"
3. Confirmar
4. Obs: Não pode excluir própria conta

---

## 🔧 Manutenção e Administração

### Backup do Banco de Dados

**Recomendação:** Backup diário automatizado

**Via phpMyAdmin:**
1. Acessar phpMyAdmin
2. Selecionar banco: dema5738_auditorhosp
3. Exportar → SQL
4. Download do arquivo .sql

**Via linha de comando:**
```bash
mysqldump -h 186.209.113.107 -u dema5738_auditorhosp -p dema5738_auditorhosp > backup_$(date +%Y%m%d).sql
```

### Backup de Arquivos

**Pastas importantes:**
- `uploads/contratos/`
- `uploads/documentos/`
- `uploads/documentos_internacao/`
- `uploads/documentos_pa_ambulatorio/`

**Recomendação:** Backup semanal completo

### Limpeza de Dados

**Logs antigos:**
```sql
DELETE FROM logs_atendimento WHERE data_hora < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

**Arquivos não vinculados:**
- Verificar manualmente arquivos órfãos nas pastas de upload
- Comparar com registros no banco de dados

### Otimização de Tabelas

```sql
OPTIMIZE TABLE faturas;
OPTIMIZE TABLE glosas;
OPTIMIZE TABLE recursos;
OPTIMIZE TABLE internacoes;
OPTIMIZE TABLE pa_ambulatorio;
```

### Monitoramento

**Verificar:**
- Espaço em disco
- Tamanho do banco de dados
- Logs de erro do PHP
- Performance de queries lentas

---

## 📊 Indicadores e KPIs

### Indicadores Principais

**1. Taxa de Glosa**
```
% Glosa = (Valor Glosado / Valor Faturado) × 100
```
**Ideal:** Menor que 5%

**2. Taxa de Recurso**
```
% Recurso = (Valor Recursado / Valor Glosado) × 100
```
**Ideal:** Maior que 80%

**3. Taxa de Aceitação**
```
% Aceito = (Valor Aceito / Valor Recursado) × 100
```
**Ideal:** Maior que 70%

**4. Taxa de Recebimento**
```
% Recebido = (Valor Recebido / Valor Aceito) × 100
```
**Ideal:** 100%

**5. Perda Líquida**
```
Perda = Valor Glosado - Valor Aceito
% Perda = (Perda / Valor Faturado) × 100
```
**Ideal:** Menor que 2%

### Análises Recomendadas

**Por Convênio:**
- Qual convênio tem maior taxa de glosa?
- Qual convênio aceita mais recursos?
- Qual é o mais rentável?

**Por Competência:**
- Evolução da taxa de glosa ao longo do tempo
- Sazonalidade no faturamento
- Tendências de melhoria/piora

**Por Setor (PA/Ambulatório):**
- Qual setor tem mais glosa?
- Eficiência operacional por setor

---

## 🚀 Possíveis Melhorias Futuras

### Funcionalidades

- [ ] Notificações por email (alertas de vencimento de contratos)
- [ ] Relatórios em PDF com gráficos
- [ ] Dashboard executivo (resumo gerencial)
- [ ] Calendário de vencimentos
- [ ] Histórico de alterações (auditoria detalhada)
- [ ] Importação em lote (Excel/CSV)
- [ ] API REST para integração com outros sistemas
- [ ] App mobile para consultas
- [ ] Sistema de aprovação de lançamentos (workflow)
- [ ] Análise preditiva de glosas

### Técnicas

- [ ] Migrar para framework (Laravel/Symfony)
- [ ] Implementar frontend moderno (Vue.js/React)
- [ ] Adicionar testes automatizados
- [ ] Cache de queries (Redis)
- [ ] Otimização de consultas complexas
- [ ] Implementar Docker para deploy
- [ ] Ambiente de staging
- [ ] CI/CD automatizado

### Interface

- [ ] Dark mode
- [ ] Personalização de dashboard
- [ ] Widgets arrastar e soltar
- [ ] Tema customizável
- [ ] Acessibilidade (WCAG)
- [ ] Internacionalização (i18n)

---

## 📞 Suporte e Contatos

### Documentação Adicional

**Arquivos README no projeto:**
- `README_CALCULOS_PERCENTUAIS.md` - Detalhes sobre cálculos
- `README_MODULO_CONTRATOS.md` - Documentação de contratos

### Instalação de Módulos

**Scripts de instalação disponíveis:**
- `setup_db.php` - Instalação inicial do banco
- `criar_tabela_contratos.php` - Módulo de contratos
- `criar_tabela_documentos.php` - Documentos gerais
- `criar_tabela_documentos_internacao.php` - Docs internação
- `criar_tabela_documentos_pa_ambulatorio.php` - Docs PA
- `criar_tabela_logs.php` - Sistema de logs
- `criar_tabela_pa_ambulatorio.php` - Tabela PA
- `criar_tabela_relatorio_pa.php` - Relatório PA consolidado
- `criar_usuario_inicial.php` - Primeiro usuário admin

### Ferramentas de Diagnóstico

- `test_conexao.php` - Testar conexão com banco
- `verificar_contratos.php` - Verificar módulo de contratos
- `check_structure.php` - Verificar estrutura do banco
- `check_tables.php` - Listar tabelas
- `check_convenios_columns.php` - Verificar tabela convênios

---

## 📝 Notas de Versão

### Versão 1.0 (Atual)

**Data:** Janeiro 2026

**Características:**
- Sistema completo de auditoria hospitalar
- Gestão de internações e PA/Ambulatório
- Sistema de glosas e recursos
- Contratos com convênios
- Documentação com upload
- Relatórios consolidados
- Dashboards com gráficos
- Exportação Excel
- Gestão de usuários
- Logs de auditoria

**Tecnologias:**
- PHP 7.4+
- MySQL 5.7+
- Tailwind CSS
- Chart.js
- PDO

**Status:** Produção ✅

---

## 📄 Licença e Direitos

**Sistema:** AuditorHosp  
**Uso:** Interno - Gestão Hospitalar  
**Desenvolvido:** Customizado para auditoria hospitalar  

---

## 🎓 Conclusão

O **AuditorHosp** é um sistema robusto e completo para gestão de auditoria hospitalar, oferecendo:

✅ **Controle Total** - Do atendimento ao recebimento  
✅ **Automação** - Cálculos e consolidações automáticas  
✅ **Rastreabilidade** - Logs e histórico completo  
✅ **Análise** - Dashboards e relatórios detalhados  
✅ **Segurança** - Autenticação e validações robustas  
✅ **Organização** - Documentos e contratos centralizados  
✅ **Flexibilidade** - Filtros e exportações diversas  

O sistema foi desenvolvido considerando as melhores práticas de desenvolvimento web, segurança e experiência do usuário, resultando em uma ferramenta poderosa para gestão hospitalar.

---

**Documento gerado em:** 15 de Janeiro de 2026  
**Versão do Documento:** 1.0  
**Última atualização:** 15/01/2026
