---
name: project-conventions
description: "Apply ChronoCert conventions when creating, reviewing, or refactoring Laravel backend code, database changes, Blade views, frontend components, and project integrations."
---

# Convenções do ChronoCert

Use esta skill para manter decisões de arquitetura, bibliotecas e interface consistentes no ChronoCert. Antes de criar algo novo, procure uma implementação existente no projeto e confirme as versões instaladas das bibliotecas envolvidas.

## Princípios gerais

- Prefira as soluções já adotadas no projeto e as APIs compatíveis com as versões instaladas.
- Não adicione dependências, abstrações ou camadas novas sem necessidade clara e aprovação quando a mudança for relevante.
- Reutilize componentes, traits, helpers, actions, requests e serviços existentes antes de duplicar lógica.
- Preserve a estrutura atual do Laravel; não crie pastas-raiz novas sem uma razão concreta.
- Mantenha nomes de código em inglês, mas escreva textos exibidos ao usuário sempre em português.
- Não introduza SoftDeletes neste momento.
- Não implemente verificação de e-mail ou outros fluxos de autenticação que não tenham sido solicitados explicitamente.
- Não crie uma abstração de moeda/currency até que o domínio realmente exija isso.
- Não imponha regras específicas de mobile ainda. Quando a responsividade for necessária, siga o contexto da tela e a decisão explícita da tarefa.
- Use ícones como componentes Blade pelo Blade UI Kit, com Heroicons como conjunto padrão, por exemplo `<x-heroicon-o-plus />`.
- Não use SVGs inline repetidos nem instale outra biblioteca de ícones sem uma decisão explícita. Os pacotes de ícones devem ser adicionados separadamente quando forem instalados no projeto.

## Laravel e backend

- Use as convenções nativas do Laravel e confirme a versão instalada antes de usar APIs de framework ou pacotes.
- Use comandos `php artisan make:*` para gerar arquivos Laravel quando houver um gerador apropriado.
- Mantenha controllers focados na orquestração HTTP. Extraia serviços, actions ou outras classes quando a lógica ficar complexa ou for reutilizada.
- Use Form Requests quando a validação tiver tamanho, regras condicionais ou reutilização suficientes para merecer uma classe própria.
- Prefira rotas nomeadas e `route()` para gerar URLs internas.
- Use tipos de retorno, tipos de parâmetros e propriedades tipadas em código PHP novo.
- Use chaves com chaves em estruturas de controle, mesmo para uma única instrução.
- Prefira PHPDoc quando uma explicação ou tipo composto realmente ajudar a manutenção.

## Dados, busca e datas

- Use PostgreSQL como banco principal e preserve as extensões de busca já adotadas (`unaccent` e `pg_trgm`).
- Quando a busca textual exigir normalização ou similaridade, verifique primeiro o trait `App\\Traits\\Searchable` e as extensões existentes antes de implementar uma consulta paralela.
- Use `App\\Helpers\\DateHelper` para as regras compartilhadas de formatação e apresentação de datas.
- A timezone da aplicação vem de `APP_TIMEZONE`, com fallback para `UTC`. Não fixe uma timezone diferente diretamente em código de domínio.
- Evite duplicar formatações de data, consultas de busca ou normalizações que já estejam centralizadas nesses recursos.

## Activity Log

- O projeto utiliza `spatie/laravel-activitylog` para registrar alterações relevantes de negócio.
- Modelos de negócio que precisem de histórico devem usar a integração do Activity Log conforme o padrão já existente, registrando mudanças úteis e evitando ruído técnico.
- Prefira registrar apenas alterações relevantes, alterações efetivas e atributos necessários para auditoria. Não registre segredos, tokens ou dados sensíveis sem uma decisão específica.
- Para customizações, confirme a API da versão instalada do pacote e siga a configuração existente antes de criar um mecanismo próprio.

## Frontend e Blade

- Use Blade e Blade Components para a interface server-rendered.
- Use Tailwind CSS v4 com a configuração CSS-first existente em `resources/css/app.css`; não crie `tailwind.config.js` para resolver uma necessidade pontual.
- Use o `resources/views/partials/head.blade.php` e os entrypoints Vite existentes. Não crie um segundo caminho para carregar CSS ou JavaScript.
- Procure primeiro uma solução equivalente no Preline. Quando existir, use sua estrutura, classes, atributos `data-hs-*` e API JavaScript documentados.
- Encapsule padrões repetidos do Preline em componentes Blade próprios, expondo props e slots adequados ao projeto. Preline não substitui os componentes Blade.
- Use Alpine ou JavaScript próprio somente quando o Preline não cobrir o comportamento ou quando a interação for específica do domínio.
- Componentes copiados de outros projetos, como o James, servem como referência visual e estrutural. Adapte-os às convenções do ChronoCert e não copie automaticamente suas interações Alpine.
- Reutilize um componente Blade existente antes de criar marcação duplicada. Extraia padrões visuais específicos do projeto quando houver repetição real.
- Para ícones em componentes e views, prefira os componentes do Blade UI Kit/Blade Heroicons em vez de copiar SVGs diretamente do James ou do Preline.
- Para detalhes dos componentes e plugins Preline, leia [references/preline.md](references/preline.md) quando a tarefa envolver interface ou comportamento interativo.

## Dependências e integrações

- Antes de instalar uma biblioteca, verifique se o projeto já possui uma solução adequada.
- Confirme versões PHP em `composer show --direct` e versões JavaScript em `package.json` antes de depender de uma API.
- Não substitua uma biblioteca instalada por uma implementação própria sem comparar o comportamento, a manutenção e o impacto no bundle.
- Prefira imports específicos quando uma integração pesada tornar o bundle desnecessariamente grande, mas preserve a simplicidade enquanto o bundle atual atender ao projeto.

## Verificação e manutenção

- O projeto não exige uma suíte de testes por padrão neste momento. Não crie testes automaticamente; adicione-os somente quando forem solicitados ou quando a tarefa depender explicitamente deles.
- Depois de alterar PHP, execute `vendor/bin/pint --dirty --format agent`.
- Depois de alterar Blade, CSS, JavaScript ou configuração de frontend, execute `npm run build` quando a ferramenta estiver disponível.
- Depois de alterações de banco ou configuração, faça as verificações proporcionais ao risco usando os comandos Artisan existentes.
- Se uma decisão nova se repetir ou resolver uma ambiguidade do projeto, proponha atualizar esta skill ou uma referência específica em vez de espalhar a regra em vários arquivos.
