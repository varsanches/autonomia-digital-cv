# Dashboard em Power BI — como montar (passo a passo)

Dados: **`dados_site.csv`** (nesta pasta) — publicações reais do site
(colunas: Titulo, Tipo, Data, Mes, Categoria).

## 1. Importar os dados
1. Abre o **Power BI Desktop**.
2. **Base → Obter dados → Texto/CSV** → escolhe `dados_site.csv` → **Carregar**.
3. Confirma os tipos: **Data** = Data; **Mes**, **Tipo**, **Categoria** = Texto.

## 2. Cabeçalho
- **Inserir → Caixa de texto**: escreve *"Autonomia Digital CV — Atividade do site"*.

## 3. Cartões (KPIs)
- **Cartão** → arrasta **Titulo** para o campo (fica "Contagem de Titulo") = total de publicações.
- Novo **Cartão**, arrasta **Titulo**, e no painel **Filtros deste visual** põe **Tipo = Artigo** → nº de artigos (8).
- Repete com **Tipo = Página** → nº de páginas (9).
- (Opcional) e-books: **Inserir → Caixa de texto** com *"3 e-books"* (não estão neste CSV).

## 4. Publicações por mês (gráfico de colunas)
- Visual **Gráfico de colunas empilhadas**.
- **Eixo X = Mes** · **Eixo Y = Contagem de Titulo**.

## 5. Artigos por categoria (gráfico de barras)
- Visual **Gráfico de barras**.
- **Eixo Y = Categoria** · **Eixo X = Contagem de Titulo**.
- Filtro do visual: **Tipo = Artigo** (as páginas não têm categoria).

## 6. Guardar e publicar
1. **Ficheiro → Guardar** como `dashboard.pbix` (podes guardar aqui na pasta `powerbi/`).
2. **Base → Publicar** → entra com a tua conta Microsoft/Power BI → **O Meu Espaço de Trabalho**.
3. Vai a **app.powerbi.com** → abre o relatório → **Ficheiro → Incorporar relatório → Publicar na Web (público)**.
4. Copia o **link** e cola no `README.md` (secção "IA/BI" — o link do dashboard).

⚠️ *"Publicar na Web"* torna o relatório **público** — por isso só métricas do site,
**nada de dados pessoais**. É perfeito para o júri abrir sem conta.

## 7. Screenshot
- Tira uma captura do dashboard e guarda em `docs/capturas/` (para os slides e o README).
