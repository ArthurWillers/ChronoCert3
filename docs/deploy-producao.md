# Deploy do ChronoCert em produção

Este guia descreve um servidor único Linux com Nginx, PHP-FPM, PostgreSQL e systemd. Os exemplos usam a organização de serviços do Debian/Ubuntu, a conta operacional `chronocert`, o usuário web `www-data`, o diretório `/var/www/chronocert` e o domínio fictício `acc.example.com`. Substitua esses valores pelos do servidor.

A referência de configuração web é a [documentação oficial de deployment do Laravel](https://laravel.com/framework/docs/deployment#nginx). O projeto usa Laravel 13; o ambiente de desenvolvimento usa PHP 8.5 e PostgreSQL 18. O `compose.yaml` com Sail, Vite e Mailpit é destinado ao desenvolvimento.

## 1. Requisitos

Prepare no servidor:

- Nginx e PHP 8.5 com PHP-FPM e CLI na mesma versão.
- Composer 2 e as extensões exigidas pelo `composer.lock`. Inclua `pdo_pgsql`, `pgsql`, `mbstring`, `xml`/DOM, `curl`, `fileinfo`, OpenSSL, tokenizer, session, ctype e OPcache. Confira também GD/EXIF/ZIP conforme os requisitos das bibliotecas instaladas.
- PostgreSQL 18 e ferramentas cliente, incluindo `pg_dump` e `pg_restore`.
- Node.js compatível com Vite 8: o pacote instalado declara `^20.19.0 || >=22.12.0`; use uma versão ainda suportada. Node/npm também podem ficar somente no ambiente que gera os assets.
- Git, cron e um servidor SMTP real.
- DNS do domínio apontado para o servidor e certificado TLS válido.

Instale os pacotes de fontes mantidas pela distribuição ou pelo provedor. Os nomes dos pacotes e o socket do FPM variam conforme o sistema. Confirme antes de usar os exemplos:

```bash
php -v
php -m
composer --version
node --version
npm --version
psql --version
sudo systemctl status php8.5-fpm nginx
```

Libere HTTP/HTTPS no firewall e restrinja SSH aos acessos administrativos. Em uma instalação local de PostgreSQL, mantenha a porta 5432 fora do acesso público.

## 2. Código, usuário e permissões

Crie a conta operacional `chronocert` caso ainda não exista. Dê a ela acesso de leitura ao repositório, preferencialmente com uma chave restrita. Execute clone, Composer, npm e Artisan nessa conta, sem usar root.

Preparação administrativa:

```bash
sudo adduser --disabled-password --gecos "" chronocert
sudo install -d -o chronocert -g www-data -m 2750 /var/www/chronocert
```

Como `chronocert`, faça o clone e selecione o commit ou tag previamente aprovado:

```bash
umask 0007
git clone https://github.com/ArthurWillers/ChronoCert3.git /var/www/chronocert
cd /var/www/chronocert
git checkout <COMMIT_OU_TAG_APROVADO>
cp .env.example .env
```

Depois de instalar as dependências e criar os diretórios de runtime, aplique estas permissões administrativas. O PHP-FPM precisa escrever em `storage` e `bootstrap/cache`, mas não no código:

```bash
sudo chown -R chronocert:www-data /var/www/chronocert
sudo find /var/www/chronocert -type d -exec chmod 2750 {} +
sudo find /var/www/chronocert -type f -exec chmod g+r,g-w,o-rwx {} +
sudo find /var/www/chronocert/storage /var/www/chronocert/bootstrap/cache -type d -exec chmod 2770 {} +
sudo find /var/www/chronocert/storage /var/www/chronocert/bootstrap/cache -type f -exec chmod 0660 {} +
sudo chmod 0640 /var/www/chronocert/.env
```

Preserve os bits de execução dos executáveis instalados pelo Composer/npm. Verifique também que `www-data` consegue atravessar `/var` e `/var/www`. Não use `chmod -R 777`.

## 3. PostgreSQL

Crie uma base e um usuário próprios. Em um servidor local, entre como administrador:

```bash
sudo -u postgres psql
```

No console do PostgreSQL:

```sql
CREATE ROLE chronocert LOGIN;
\password chronocert
CREATE DATABASE chronocert OWNER chronocert;
\quit
```

O comando `\password` solicita a senha sem incluí-la no histórico do shell. A migration do projeto cria as extensões PostgreSQL necessárias durante `php artisan migrate`; confirme que elas estão disponíveis no servidor ou serviço gerenciado. A conta da aplicação precisa poder executar as migrations na base, sem ser superusuária do cluster.

## 4. Variáveis de produção

Edite `/var/www/chronocert/.env` antes de criar os caches. Exemplo com banco local e SMTP com STARTTLS na porta 587:

```dotenv
APP_NAME=ChronoCert
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://acc.example.com
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=America/Sao_Paulo

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=chronocert
DB_USERNAME=chronocert
DB_PASSWORD="SUBSTITUA_POR_UMA_SENHA_FORTE"

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=null

CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=90
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME="SUBSTITUA"
MAIL_PASSWORD="SUBSTITUA"
MAIL_FROM_ADDRESS=chronocert@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Use os parâmetros de TLS indicados pelo provedor SMTP; por exemplo, TLS implícito normalmente usa `MAIL_SCHEME=smtps` e porta 465. `MAIL_MAILER=log` não entrega mensagens. Valide o domínio remetente no provedor de e-mail.

Para PostgreSQL remoto, configure rede privada e validação TLS conforme o provedor; `config/database.php` aceita `DB_SSLMODE`. Não coloque segredos no Git nem os imprima em logs de deploy.

## 5. Instalação inicial

Como `chronocert`, no diretório da aplicação:

```bash
umask 0007
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci --ignore-scripts
npm run build
php artisan key:generate --no-interaction
php artisan migrate --force --no-interaction
php artisan optimize --no-interaction
```

Gere `APP_KEY` somente na primeira instalação. Nas atualizações e restaurações, conserve a chave existente. Se o build acontecer em CI, publique `public/build` junto com o código e dispense Node no servidor. Verifique que não foi publicado um arquivo `public/hot` de desenvolvimento.

Não execute `composer run setup`, `migrate:fresh` ou `db:seed` em produção. O `DatabaseSeeder` atual chama um seeder com senha padrão de desenvolvimento. Para criar o primeiro administrador, use o comando próprio **interativo**, que solicita os dados e a senha:

```bash
php artisan chronocert:create-administrator
```

Esse comando é a exceção intencional ao uso de `--no-interaction`. Não passe senha ou CPF em argumentos do shell. Ele recusa a criação se já existir um vínculo de administrador.

### Comprovantes e persistência

Os comprovantes usam o disco `private`, configurado em `config/filesystems.php`, cuja raiz é `storage/app/private`. Alterar apenas `FILESYSTEM_DISK` não muda esse disco da coleção de comprovantes.

Preserve `storage` e `.env` em toda atualização. Os documentos devem ser entregues pelas rotas autorizadas da aplicação; não crie um alias Nginx ou um link público para `storage/app/private`. `storage:link` não é necessário para esses comprovantes.

## 6. Nginx, HTTPS e PHP-FPM

O exemplo abaixo adapta a [configuração Nginx oficial do Laravel](https://laravel.com/framework/docs/deployment#nginx) ao domínio, PHP e limites de upload do ChronoCert. A raiz web é exclusivamente `public`, com encaminhamento para o front controller. Nunca aponte para a raiz do repositório.

Primeiro obtenha o certificado pelo processo ACME usado no servidor. O bloco HTTPS pressupõe que os caminhos de certificado já existem. Se usar validação HTTP-01, provisione inicialmente um virtual host HTTP que sirva o diretório de challenge; depois habilite a configuração final.

Arquivo sugerido: `/etc/nginx/sites-available/chronocert`.

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name acc.example.com;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
        try_files $uri =404;
    }

    location / {
        return 301 https://acc.example.com$request_uri;
    }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name acc.example.com;
    root /var/www/chronocert/public;
    index index.php;
    charset utf-8;

    ssl_certificate /etc/letsencrypt/live/acc.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/acc.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    client_max_body_size 12m;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header X-Content-Type-Options nosniff always;

    access_log /var/log/nginx/chronocert-access.log;
    error_log /var/log/nginx/chronocert-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120s;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~ /\. {
        deny all;
    }
}
```

Confirme o socket em `/etc/php/8.5/fpm/pool.d/www.conf` e o usuário do pool (`www-data` neste guia). Mantenha as configurações TLS e a renovação automática de certificados do servidor atualizadas. O cabeçalho `SAMEORIGIN` permite a prévia de PDF dentro da própria aplicação.

No PHP-FPM, por exemplo em `/etc/php/8.5/fpm/conf.d/99-chronocert.ini`:

```ini
expose_php = Off
display_errors = Off
log_errors = On
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 120
memory_limit = 256M
opcache.enable = 1
```

O limite da aplicação é 10 MiB por comprovante (`config/acc.php`). Nginx e `post_max_size` têm margem para o formulário multipart. Ajuste memória e tempos com base no volume real, especialmente nas exportações ZIP.

Para que arquivos de runtime criados pelo FPM continuem graváveis pelo grupo compartilhado, configure um override de serviço em `/etc/systemd/system/php8.5-fpm.service.d/chronocert.conf` (crie o diretório se necessário):

```ini
[Service]
UMask=0007
```

Após criar esse override, execute `sudo systemctl daemon-reload` e `sudo systemctl restart php8.5-fpm`. Aplique também `umask 0007` nas sessões da conta `chronocert`; os diretórios com setgid preservam o grupo `www-data`.

Habilite o site após provisionar os certificados:

```bash
sudo ln -s /etc/nginx/sites-available/chronocert /etc/nginx/sites-enabled/chronocert
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl reload php8.5-fpm
```

Se houver proxy ou balanceador terminando HTTPS, configure os proxies confiáveis em `bootstrap/app.php` com os endereços reais dessa infraestrutura. Hoje não há configuração específica de `trustProxies` no projeto. Não confie indiscriminadamente em cabeçalhos encaminhados por clientes externos.

## 7. Fila de notificações com systemd

As notificações de início, aprovação e rejeição de análise implementam `ShouldQueue`. Com `QUEUE_CONNECTION=database`, configure um worker persistente; as tabelas necessárias vêm das migrations.

Crie `/etc/systemd/system/chronocert-queue.service`:

```ini
[Unit]
Description=Fila do ChronoCert
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=chronocert
Group=www-data
WorkingDirectory=/var/www/chronocert
ExecStart=/usr/bin/php8.5 artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=60 --max-time=3600
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=90
UMask=0007

[Install]
WantedBy=multi-user.target
```

O timeout de 60 segundos fica abaixo do `retry_after` de 90 segundos. Confirme o caminho do binário CLI. Se o banco for local, inicialize o PostgreSQL antes de habilitar o worker.

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now chronocert-queue
sudo systemctl status chronocert-queue
sudo journalctl -u chronocert-queue -n 50 --no-pager
```

Em caso de falha, consulte `php artisan queue:failed`. Investigue a causa antes de executar `queue:retry`, pois repetir um job pode repetir uma entrega de e-mail.

## 8. Scheduler e purga após 30 dias

Em `routes/console.php`, `acc:purge-rejected-submissions` está agendado diariamente às **02:00 na timezone da aplicação**, com `withoutOverlapping()`. A rejeição define `purge_at` para 30 dias depois da decisão. A execução seguinte remove os comprovantes e os registros de submissão/análise elegíveis, preservando o evento de auditoria. Portanto, a purga não ocorre necessariamente no minuto exato em que o prazo vence.

Adicione a entrada abaixo ao crontab da conta `chronocert` (`sudo crontab -u chronocert -e`):

```cron
* * * * * umask 0007; cd /var/www/chronocert && /usr/bin/php8.5 artisan schedule:run >> /var/www/chronocert/storage/logs/scheduler.log 2>&1
```

```bash
php artisan schedule:list
```

Confira também o serviço cron da distribuição e monitore o log. Configure rotação para `scheduler.log`; esse arquivo não é rotacionado pelo canal de log do Laravel. Não agende a purga diretamente em paralelo ao scheduler. A execução manual de `acc:purge-rejected-submissions` é destrutiva para todos os registros elegíveis, não um modo de simulação.

Se a aplicação ganhar múltiplos servidores, mantenha uma única instância do cron ou adapte o agendamento para execução única com cache compartilhado. Não presuma que `withoutOverlapping()` isoladamente define qual servidor executará a tarefa.

## 9. Atualizações do código

Este procedimento é um deploy com janela de manutenção, no mesmo diretório. Antes de começar, confirme que o commit foi validado em homologação e que há espaço para os backups e o build.

1. Como `chronocert`, execute `php artisan down --retry=60`.
2. Pare o worker com `sudo systemctl stop chronocert-queue` e aguarde tarefas/scheduler já em andamento terminarem.
3. Faça backup consistente da base, de `storage/app/private` e do `.env`/`APP_KEY`, em destino protegido fora da raiz web. Use `pg_dump -Fc` com credenciais em arquivo protegido ou gerenciador de segredos; não em argumentos do shell. Registre o commit atual com `git rev-parse HEAD`.
4. Atualize para a revisão aprovada. Para acompanhar `master`, sem alterações locais: `git fetch origin` e `git merge --ff-only origin/master`. Para uma revisão fixa, use `git checkout <COMMIT_OU_TAG_APROVADO>` após o fetch.
5. Como `chronocert`, no diretório da aplicação:

```bash
umask 0007
php artisan config:clear --no-interaction
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci --ignore-scripts
npm run build
php artisan migrate --force --no-interaction
php artisan optimize --no-interaction
php artisan schedule:interrupt --no-interaction
```

6. Confira permissões dos arquivos novos e reabra o serviço:

```bash
sudo systemctl reload php8.5-fpm
sudo systemctl start chronocert-queue
php artisan up --no-interaction
```

O worker foi parado antes da atualização e iniciado com o código novo. Não rode `composer update` ou `npm update` no servidor durante o deploy: use os lockfiles versionados. Não regenere a chave nem apague o armazenamento privado.

As migrations de preservação das horas reconhecidas fazem backfill do histórico. O tempo de execução depende do volume da base; planeje a janela e o backup. Não execute `migrate:rollback` automaticamente em caso de falha: voltar o código não implica que seja seguro voltar os dados. Se uma etapa falhar, mantenha a manutenção, investigue e escolha entre uma correção adiante ou a restauração coordenada de banco, arquivos e código.

## 10. Verificação operacional e recuperação

- Confira `/up`, login e carregamento de CSS/JS. `/up` verifica o boot; por padrão não garante conectividade com banco, SMTP ou existência dos documentos.
- Confirme login com os perfis previstos, isolamento entre cursos, envio/visualização privada de um comprovante e entrega de notificações em uma operação controlada.
- Consulte `php artisan migrate:status`, `php artisan schedule:list`, o worker e `queue:failed`.
- Monitore armazenamento, banco, logs da aplicação, Nginx, FPM e a última execução bem-sucedida da purga.
- Mantenha cópias protegidas fora do servidor e exercite restauração em ambiente isolado. Banco e arquivos precisam corresponder ao mesmo ponto de recuperação. A retenção dos backups também deve considerar os comprovantes já purgados.

Problemas frequentes:

| Sintoma | Conferência |
| --- | --- |
| 502 no Nginx | Serviço FPM, socket configurado e permissões de acesso ao socket. |
| Erro de escrita | Grupo e permissões em `storage` e `bootstrap/cache`; evite dar escrita no código ao usuário web. |
| `could not find driver` | `pdo_pgsql` instalado no CLI e no FPM da versão usada. |
| Upload 413 ou arquivo vazio | Limites de Nginx, `post_max_size` e `upload_max_filesize`. |
| CSS/JS ausentes | `public/build/manifest.json`, assets publicados e ausência de `public/hot`. |
| E-mails não chegam | SMTP/remetente, worker ativo, jobs falhos e logs do provedor. |
| Purga não executa | Cron ativo, timezone, acesso ao cache de locks e logs do scheduler. |
| URLs HTTP atrás de proxy HTTPS | `APP_URL`, proxies confiáveis e cabeçalhos encaminhados pelo balanceador. |

## Referências

- [Deployment do Laravel: servidor, Nginx, caches e serviços](https://laravel.com/framework/docs/deployment).
- Fontes específicas do projeto: `compose.yaml`, `config/acc.php`, `config/filesystems.php`, `config/queue.php`, `routes/console.php` e `app/Console/Commands/CreateInitialAdministrator.php`.
