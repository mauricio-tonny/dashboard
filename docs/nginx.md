# NGINX

## Dominio

O sistema sera publicado em:

```text
dashboard.oficinadodev.com.br
```

## Caminho Sugerido

```text
/var/www/dashboard.oficinadodev.com.br/html
```

A raiz publica do NGINX deve apontar para:

```text
/var/www/dashboard.oficinadodev.com.br/html/public
```

Isso e importante porque arquivos como `.env`, `src`, `views`, `storage`, `database` e `vendor` nao devem ficar acessiveis pela web.

## Arquivo de Configuracao

O modelo principal esta em:

```text
deploy/nginx/dashboard.oficinadodev.com.br.conf
```

No servidor, o fluxo sugerido e:

```bash
sudo cp deploy/nginx/dashboard.oficinadodev.com.br.conf /etc/nginx/sites-available/dashboard.oficinadodev.com.br
sudo ln -s /etc/nginx/sites-available/dashboard.oficinadodev.com.br /etc/nginx/sites-enabled/dashboard.oficinadodev.com.br
sudo nginx -t
sudo systemctl reload nginx
```

## Certificado SSL

Antes do certificado existir, talvez seja necessario subir uma versao temporaria somente com `listen 80`.

Depois, gere o certificado:

```bash
sudo certbot --nginx -d dashboard.oficinadodev.com.br
```

O arquivo em `deploy/nginx` ja esta preparado no padrao final com SSL.

## Permissoes do Projeto

O PHP-FPM roda como `www-data`, entao os diretorios do projeto precisam permitir leitura e travessia para esse usuario/grupo.

No servidor de desenvolvimento, depois de enviar arquivos por `scp`, normalize com:

```bash
cd /var/www/dashboard.oficinadodev.com.br/html
find . -type d -exec chmod 775 {} \;
find . -type f -exec chmod 664 {} \;
chmod 775 storage storage/cache storage/sessions
```

Se `public/` ficar sem permissao de grupo, o NGINX/PHP-FPM pode retornar `File not found.` e registrar `Primary script unknown`.

## Diferencas em Relacao ao Magento 2

- Nao usamos `MAGE_ROOT`.
- Nao existem rotas `/setup`, `/update`, `/static`, `/media` ou `get.php`.
- Apenas `/index.php` deve executar PHP.
- O restante das rotas passa por `try_files` e cai no roteador da aplicacao.
- `robots.txt`, meta tag e `X-Robots-Tag` ajudam a evitar indexacao, mas a seguranca real segue sendo login e permissao.
