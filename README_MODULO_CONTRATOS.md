# 📄 Módulo de Contratos - AuditorHosp

## 📋 Visão Geral

Módulo completo para gestão de contratos com convênios, permitindo cadastro, upload de arquivos, visualização e controle de vigência.

---

## 🎯 Funcionalidades

### ✅ Cadastro de Contratos
- Vincular contratos aos convênios cadastrados
- Múltiplos contratos por convênio
- Informações: número do contrato, datas de vigência, valor
- Upload de arquivo do contrato (PDF, DOC, DOCX, JPG, PNG - até 30MB)
- Campo de observações
- Controle de status (ativo/inativo)

### 📊 Listagem e Filtros
- Visualização em tabela com todos os contratos
- Filtros por convênio e status
- Cards com estatísticas:
  - Total de contratos
  - Contratos ativos
  - Contratos vencendo (30 dias)
  - Contratos vencidos
- Indicadores visuais de status

### 📁 Gestão de Arquivos
- Upload de contratos (máximo 30MB)
- Formatos aceitos: PDF, DOC, DOCX, JPG, PNG
- Visualização direta no navegador
- Remoção de arquivos
- Armazenamento seguro em `uploads/contratos/`

### 🔔 Alertas de Vencimento
- **Verde**: Contrato ativo e vigente
- **Amarelo**: Contrato a vencer em 30 dias
- **Vermelho**: Contrato vencido
- **Cinza**: Contrato inativo

---

## 🗂️ Estrutura do Banco de Dados

### Tabela: `contratos`

```sql
CREATE TABLE contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    convenio_id INT NOT NULL,
    numero_contrato VARCHAR(100) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    valor_contrato DECIMAL(15,2) NULL,
    arquivo_contrato VARCHAR(255) NULL,
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT NULL,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_criacao INT NULL,
    FOREIGN KEY (convenio_id) REFERENCES convenios(id)
);
```

### Relacionamentos
- **convenios** (1:N): Um convênio pode ter vários contratos

---

## 📂 Arquivos do Módulo

### Scripts PHP
- **`contratos.php`**: Listagem principal com filtros e estatísticas
- **`contratos_form.php`**: Formulário de cadastro/edição
- **`visualizar_contrato.php`**: Visualização de arquivos
- **`criar_tabela_contratos.php`**: Script de instalação
- **`inserir_contratos_exemplo.php`**: Dados de exemplo

### Pasta de Uploads
- **`uploads/contratos/`**: Armazenamento de arquivos

---

## 🚀 Como Usar

### 1. Instalação
Execute o script de instalação (já executado):
```
http://localhost/auditorhosp/criar_tabela_contratos.php
```

### 2. Acessar o Módulo
Clique em **"Contratos"** no menu superior do sistema.

### 3. Cadastrar Novo Contrato
1. Clique em **"+ Novo Contrato"**
2. Selecione o convênio
3. Preencha: número do contrato, datas, valor (opcional)
4. Faça upload do arquivo do contrato (opcional)
5. Adicione observações se necessário
6. Marque como ativo/inativo
7. Clique em **"Cadastrar Contrato"**

### 4. Editar Contrato
1. Na listagem, clique em **"Editar"**
2. Modifique os campos desejados
3. Faça upload de novo arquivo ou remova o existente
4. Clique em **"Atualizar Contrato"**

### 5. Visualizar Arquivo
- Clique no ícone de visualização (👁️) na coluna "Arquivo"
- O arquivo será aberto em nova aba do navegador

### 6. Excluir Contrato
- Clique em **"Excluir"** e confirme
- O arquivo físico também será removido

---

## 🔍 Filtros Disponíveis

### Por Convênio
Filtra contratos de um convênio específico.

### Por Status
- **Todos**: Exibe todos os contratos
- **Ativos**: Apenas contratos marcados como ativos
- **Inativos**: Apenas contratos desativados

---

## 🎨 Interface

### Dashboard de Estatísticas
4 cards informativos mostrando:
- 📄 Total de contratos
- ✅ Contratos ativos
- ⏰ Contratos vencendo (30 dias)
- ⚠️ Contratos vencidos

### Tabela Principal
Colunas:
- Convênio
- Nº do Contrato
- Vigência (início → fim)
- Valor
- Status (badge colorido)
- Arquivo (ícone para visualização)
- Ações (editar/excluir)

---

## 🔐 Segurança

### Proteção de Acesso
- Requer autenticação (via `auth.php`)
- Arquivos armazenados fora do alcance direto

### Validações
- Tipos de arquivo permitidos: PDF, DOC, DOCX, JPG, PNG
- Tamanho máximo: 30MB
- Validação de campos obrigatórios
- Proteção contra SQL Injection (PDO prepared statements)

### Controle de Arquivos
- Nomes únicos gerados automaticamente
- Remoção segura de arquivos antigos ao substituir
- Verificação de existência antes de servir

---

## 📈 Recursos Avançados

### Cálculo Automático de Status
O sistema calcula automaticamente:
- Dias para vencimento
- Classificação de status (ativo, a vencer, vencido)
- Cores dos indicadores visuais

### Upload Inteligente
- Preserva arquivo existente se não houver novo upload
- Permite remover arquivo sem substituir
- Validação de tipo e tamanho

### Responsividade
- Interface adaptável para desktop, tablet e mobile
- Tabelas com scroll horizontal em telas pequenas

---

## 🛠️ Manutenção

### Backup de Arquivos
Recomenda-se backup regular da pasta:
```
uploads/contratos/
```

### Limpeza de Arquivos Órfãos
Se necessário, criar script para identificar arquivos sem registro no BD.

---

## 📝 Exemplos de Uso

### Cenário 1: Renovação de Contrato
1. Mantenha o contrato antigo marcado como inativo
2. Cadastre novo contrato com novas datas e valores
3. Faça upload do novo documento

### Cenário 2: Aditivo Contratual
1. Edite o contrato existente
2. Atualize o valor se houver reajuste
3. Adicione observações sobre o aditivo
4. Faça upload do documento do aditivo (sobrescreve ou mantém original)

### Cenário 3: Contratos Temporários
- Deixe o campo "Data de Fim" vazio para contratos sem prazo determinado

---

## ✅ Checklist de Funcionalidades

- [x] Cadastro de contratos
- [x] Edição de contratos
- [x] Exclusão de contratos
- [x] Upload de arquivos
- [x] Visualização de arquivos
- [x] Filtros por convênio e status
- [x] Estatísticas de contratos
- [x] Alertas de vencimento
- [x] Múltiplos contratos por convênio
- [x] Validação de formulários
- [x] Interface responsiva
- [x] Integração com sistema de autenticação

---

## 🎯 Melhorias Futuras (Sugestões)

- [ ] Notificações automáticas de vencimento
- [ ] Histórico de alterações
- [ ] Exportação de relatório de contratos
- [ ] Anexar múltiplos arquivos por contrato
- [ ] Dashboard específico de contratos
- [ ] Integração com calendário
- [ ] Assinatura digital de contratos
- [ ] Versionamento de contratos

---

**Módulo desenvolvido em:** 13/01/2026  
**Status:** ✅ Operacional
