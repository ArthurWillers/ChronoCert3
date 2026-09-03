# ChronoCert

O ChronoCert é uma aplicação web para gestão institucional de Atividades Complementares (ACC), com envio privado de documentos, análise acadêmica e contabilização de horas.

O projeto está em reconstrução incremental. A base atual concentra autenticação, identidade, vínculos, seleção do contexto de acesso, configurações da conta e a interface visual. Os fluxos operacionais de ACC serão implementados conforme a documentação do vault.

## Fonte de verdade

As decisões de produto, domínio, dados, autorização e fluxos estão em [`docs/`](docs/). Em caso de divergência, a documentação do vault deve ser consultada antes do código.

## Estado atual

- Login e recuperação de senha são fornecidos pelo Laravel Fortify.
- O login usa `users.email`; o CPF é único em `users`.
- E-mail operacional e matrícula acadêmica pertencem ao vínculo (`affiliations`), não à pessoa.
- Os tipos de vínculo previstos são `student`, `coordinator` e `administrator`.
- Após o login, um único vínculo válido é selecionado automaticamente. Com mais de um vínculo, a pessoa escolhe o contexto em `/affiliations/select`; apenas o ID técnico do vínculo ativo é guardado na sessão.
- O dashboard é separado por tipo de vínculo e está preparado para receber os fluxos específicos de cada contexto.
- Nome e CPF aparecem nas configurações como dados somente leitura. O e-mail de login e a senha podem ser atualizados pelos fluxos do Fortify.
- O cadastro inicial do administrador é feito pelo comando interativo `chronocert:create-administrator`.
- Arquivos são armazenados em disco privado. A configuração atual aceita PDF e imagens JPG, JPEG, PNG e WEBP; os downloads deverão continuar protegidos pelas Policies do domínio.
- A autorização do domínio seguirá Policies e Gates nativos do Laravel. O projeto não usa Spatie Permission.

Os módulos de categorias, cursos, submissões, análise, snapshots de aprovação, auditoria detalhada e expurgo de rejeições ainda fazem parte da implementação incremental prevista em [`docs/06 - Planejamento/ChronoCert - Checklist de implementação.md`](docs/06%20-%20Planejamento/ChronoCert%20-%20Checklist%20de%20implementação.md).

## Rotas da aplicação

As URLs próprias da aplicação são mantidas em inglês:

| Rota | Finalidade |
| --- | --- |
| `/dashboard` | Dashboard do vínculo ativo |
| `/affiliations/select` | Seleção ou troca do vínculo ativo |
| `/settings` | Configurações da conta |

As rotas de autenticação (login, logout, recuperação e redefinição de senha) são registradas pelo Fortify.

## Requisitos

- PHP 8.3 ou superior
- Composer
- Node.js e npm
- SQLite para desenvolvimento local ou outro banco suportado pelo Laravel

## Instalação local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Configure o banco e os demais serviços no `.env` antes de executar as migrations. O `.env.example` usa SQLite e os drivers locais do Laravel como ponto de partida.

## Primeiro administrador

Com o banco migrado, execute o prompt do Laravel para criar a primeira pessoa e o vínculo global de administrador:

```bash
php artisan chronocert:create-administrator
```

O comando solicita nome completo, CPF, e-mail de login, e-mail operacional do vínculo e senha com confirmação. Também é possível informar opções não interativas; evite passar a senha diretamente na linha de comando:

```bash
php artisan chronocert:create-administrator \
  --name="Nome do administrador" \
  --cpf="00000000000" \
  --email="admin@instituicao.edu.br" \
  --operational-email="admin@instituicao.edu.br"
```

O cadastro público não é o fluxo de entrada do sistema: novos usuários e vínculos devem ser criados pelos fluxos institucionais previstos no domínio.

## Desenvolvimento

Para iniciar o ambiente de desenvolvimento configurado pelo Laravel:

```bash
composer run dev
```

Quando necessário, os processos podem ser executados separadamente:

```bash
php artisan serve
npm run dev
```

## Tecnologias

### Aplicação

- Laravel Framework 13.26.1
- Laravel Fortify 1.38.0 — autenticação
- Spatie Laravel Activitylog 5.1.0 — registro de atividades
- Spatie Laravel Medialibrary 11.23.5 — gerenciamento de arquivos
- Blade Icons e Blade Heroicons — ícones nos componentes Blade
- Tailwind CSS 4 — estilização
- Alpine.js 3.16 — interações leves no frontend

### Desenvolvimento

- Laravel Pint 1.30 — formatação de código PHP
- Laravel Sail 1.67 — ambiente com Docker
- Laravel Boost 2.5 — suporte ao desenvolvimento com agentes de IA
- Pest 5.1 — infraestrutura de testes do projeto

## Licença

O ChronoCert é distribuído sob a [licença MIT](LICENSE).
