# Preline no ChronoCert

## O que o Preline fornece

O Preline tem duas partes que devem ser diferenciadas:

1. **Componentes visuais:** exemplos de HTML com classes Tailwind, estados e estrutura de markup.
2. **Plugins de comportamento:** JavaScript que inicializa e controla componentes interativos por meio de atributos `data-hs-*` ou da API JavaScript.

Preline não gera componentes Blade para o Laravel. A convenção do ChronoCert é usar a documentação como referência e encapsular os padrões repetidos em Blade Components próprios. O componente Blade deve controlar props, slots e nomes do domínio, enquanto a implementação interna segue a estrutura documentada pelo Preline.

O markup real deve ser conferido na documentação da versão instalada e adaptado ao componente Blade do projeto.

## Estado atual do projeto

- O pacote `preline` já está instalado em `package.json` na versão `4.2.0`.
- `resources/js/app.js` já importa `preline`, portanto o bundle atual inclui os plugins disponíveis no pacote.
- `resources/css/app.css` já importa `preline/variants.css` e faz scan dos arquivos JavaScript do Preline para que as classes necessárias sejam detectadas pelo Tailwind.
- Não é necessário instalar plugins do Preline separadamente enquanto o pacote completo `preline` continuar sendo usado. A instalação separada só deve ser considerada se o projeto decidir usar um plugin isolado.

## Como escolher a implementação

- Para modal, dropdown, tabs, accordion, overlay, tooltip, drawer, select e comportamentos semelhantes, procure primeiro o plugin Preline correspondente.
- Para layout comum de página, sidebar fixa ou navegação, use a estrutura visual apropriada do Preline.
- Use Alpine ou JavaScript próprio somente quando não houver plugin equivalente ou quando a regra for específica do negócio.
- Se o bundle completo ficar pesado no futuro, avalie imports específicos de plugins. Não faça essa otimização prematuramente.

## Fontes

- [Documentação do Preline](https://preline.co/docs/)
