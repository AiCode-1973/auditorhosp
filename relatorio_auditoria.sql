-- Exemplo de SQL para gerar o relatório da imagem
-- Este script assume que você tem tabelas para faturamento e glosas.
-- Ajuste os nomes das tabelas e colunas conforme o seu banco de dados.

SELECT 
    -- Agrupamento por Competência (Mês/Ano) e Convênio
    DATE_FORMAT(f.data_competencia, '%m/%Y') AS Competencia,
    c.nome_convenio AS Convenio,

    -- Métricas Absolutas
    SUM(f.valor_total) AS Faturamento,
    SUM(COALESCE(g.valor_glosa, 0)) AS Glosado,
    SUM(COALESCE(r.valor_recursado, 0)) AS Recursado,
    SUM(COALESCE(a.valor_aceito, 0)) AS Aceito,

    -- Cálculos de Porcentagem (com proteção contra divisão por zero)
    
    -- % Glosado: Quanto do faturamento foi glosado
    ROUND(
        (SUM(COALESCE(g.valor_glosa, 0)) / NULLIF(SUM(f.valor_total), 0)) * 100, 
    2) AS Perc_Glosado,

    -- % Recursado: Quanto do valor glosado foi recursado (ou sobre o faturamento, dependendo da regra de negócio)
    -- Aqui assumindo % sobre o valor Glosado para mostrar eficiência do recurso
    ROUND(
        (SUM(COALESCE(r.valor_recursado, 0)) / NULLIF(SUM(COALESCE(g.valor_glosa, 0)), 0)) * 100, 
    2) AS Perc_Recursado,

    -- % Aceito: Quanto do valor recursado foi aceito (Recuperação)
    ROUND(
        (SUM(COALESCE(a.valor_aceito, 0)) / NULLIF(SUM(COALESCE(r.valor_recursado, 0)), 0)) * 100, 
    2) AS Perc_Aceito,

    -- % Recebido: (Faturamento Líquido / Faturamento Bruto) ou similar
    -- Assumindo: (Faturamento - Glosa + Aceito) / Faturamento
    ROUND(
        ((SUM(f.valor_total) - SUM(COALESCE(g.valor_glosa, 0)) + SUM(COALESCE(a.valor_aceito, 0))) / NULLIF(SUM(f.valor_total), 0)) * 100, 
    2) AS Perc_Recebido

FROM 
    faturas f
    LEFT JOIN convenios c ON f.convenio_id = c.id
    -- Join com tabela de glosas (pode ser 1:N, aqui simplificado com agregação prévia ou assumindo 1:1 por item)
    LEFT JOIN (
        SELECT fatura_id, SUM(valor) as valor_glosa 
        FROM glosas 
        GROUP BY fatura_id
    ) g ON f.id = g.fatura_id
    -- Join com tabela de recursos
    LEFT JOIN (
        SELECT fatura_id, SUM(valor_recursado) as valor_recursado, SUM(valor_deferido) as valor_aceito
        FROM recursos 
        GROUP BY fatura_id
    ) r ON f.id = r.fatura_id -- ou join com a tabela de glosas se o recurso for ligado à glosa
    LEFT JOIN (
         -- Caso o aceite venha de uma tabela separada de pagamentos pós-recurso
        SELECT fatura_id, SUM(valor_pago) as valor_aceito
        FROM pagamentos_recurso
        GROUP BY fatura_id
    ) a ON f.id = a.fatura_id

GROUP BY 
    DATE_FORMAT(f.data_competencia, '%m/%Y'),
    c.nome_convenio
ORDER BY 
    f.data_competencia DESC, 
    c.nome_convenio;
