# Assistente de Dúvidas (IA)

Assistente que responde a dúvidas dos alunos (TIC/Excel) no site, usando a **API do
Gemini**. Demonstra o módulo de **Engenharia de IA** e a **Segurança na Cloud**: a chave da
API vive **no servidor**, nunca no browser.

## Como funciona

```
Aluno (index.html) ──POST /assistente/api.php──▶ servidor (PHP) ──▶ API Gemini
                                                  (lê a chave)   ◀── resposta ──
```

- `index.html` — a página do assistente (interface de chat).
- `api.php` — o *endpoint* seguro: recebe a pergunta, lê a chave de `/var/www/gemini.key`,
  chama o Gemini e devolve a resposta. **A chave nunca sai do servidor.**

## Instalação (no servidor)

1. Copiar `api.php` e `index.html` para `…/wordpress/assistente/`.
2. Criar o ficheiro da chave **fora da raiz web** e dar-lhe permissões:
   ```bash
   sudo touch /var/www/gemini.key
   sudo chown www-data:www-data /var/www/gemini.key
   sudo chmod 640 /var/www/gemini.key
   ```
3. Criar uma **chave gratuita** em <https://aistudio.google.com/app/apikey> e colá-la no
   ficheiro (o conteúdo é só a chave, numa linha):
   ```bash
   sudo nano /var/www/gemini.key
   ```
4. Abrir `https://autonomiadigitalcv.com/assistente/` e testar.

## Segurança

- 🔐 A chave **não** está neste repositório (bloqueada pelo `.gitignore`) nem no browser.
- O `api.php` só aceita `POST`, limita o tamanho da pergunta e só responde ao próprio site.
- `/var/www/gemini.key` está **fora** da raiz web (`…/wordpress/`), por isso não é acessível
  pela internet.

## Modelo e custo

- Modelo por defeito: `gemini-2.0-flash` (rápido e barato). Muda-se no topo do `api.php`.
- A API do Gemini tem **plano gratuito** suficiente para uma turma.
  *(A subscrição Google One dá o Gemini na app, não a API — a chave da API é a do AI Studio.)*
