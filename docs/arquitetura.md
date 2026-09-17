# Arquitetura & decisões

## Visão geral

```mermaid
flowchart LR
    U[Utilizador / Aluno] -->|HTTPS| D[autonomiadigitalcv.com]
    D --> EC2
    subgraph EC2[AWS EC2 · Ubuntu Linux]
        NG[Servidor web] --> WP[WordPress + tema Astra]
        WP --> DB[(MySQL)]
        WP --> UP[Uploads: vídeos, e-books, imagens]
    end
    LE[Let's Encrypt] -.renovação automática.-> EC2
    DB -.export CSV.-> PBI[Power BI · dashboard publicado]
```

## Decisões e porquê

| Camada | Escolha | Porquê |
|---|---|---|
| Alojamento | AWS EC2 (Ubuntu, instância pequena) | controlo total do servidor e cloud a baixo custo (~5 USD/mês) |
| Web / CMS | WordPress + tema Astra | publicar conteúdo depressa; extensível com CSS e *mu-plugins* próprios |
| Segurança | HTTPS com Let's Encrypt, renovação automática | tráfego cifrado, sem custo adicional |
| Privacidade | *mu-plugin* `noindex` nas páginas com palavra-passe | as áreas de turma ficam fora do Google |
| BI | Power BI, dados via **CSV exportado** (`wp-cli`) | demonstra criar e publicar relatórios **sem expor a base de dados** |

## Segurança

- A base de dados MySQL **não** está aberta à internet.
- Se um dia o Power BI ligar diretamente ao MySQL, será **apenas por túnel SSH**.
- Chaves, palavras-passe, certificados e dados de alunos **nunca** entram no repositório
  (ver `.gitignore`); vivem num gestor de senhas, fora do controlo de versões.

## Custo

Operação a **~5 USD/mês** (IP fixo + disco), contra **~R$199–299/mês** de uma plataforma
SaaS equivalente — a diferença entre depender de terceiros e ter autonomia.
