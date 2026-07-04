# GarageON Frontend Rules

Regras obrigatórias para implementar frontend no GarageON. Use junto com `.ai/design_system.md`.

## Stack e Escopo

- Frontend principal: Laravel Blade + Tailwind CSS 4.
- Evite adicionar framework JS pesado para interações simples.
- JavaScript deve ser progressivo, pontual e fácil de remover/manter.
- Priorize HTML semântico, Blade components/partials e classes Tailwind consistentes.
- Antes de criar padrão novo, procure padrão semelhante no projeto.

## Estrutura de Telas

Toda tela deve ser composta em camadas claras:

1. Layout/base.
2. Header/contexto da página.
3. Seções.
4. Cards/painéis.
5. Componentes reutilizáveis.
6. Elementos pequenos de UI.

Toda página do painel autenticado deve incluir o header padrão do cockpit no topo. Não crie telas internas sem esse header; quando necessário, extraia/reutilize um partial para manter navegação, contexto e ações consistentes.

Não concentre toda lógica, marcação e estado em um único Blade gigante quando a tela crescer. Extraia partials/components para blocos reutilizáveis como cards, botões, empty states, headers, tabelas e formulários.

## Princípios de Código

- Código deve ser simples, legível e preparado para crescer.
- Cada componente/partial resolve um problema claro.
- Prefira composição a componentes enormes.
- Use nomes explícitos para variáveis, props e partials.
- Evite duplicação de blocos visuais.
- Comentários só para regra de negócio ou decisão visual não óbvia.
- Não misture regra de negócio complexa dentro da view; prepare dados no controller, model, view model ou helper apropriado.

## Tailwind e Tokens

- Use a paleta e direção visual de `.ai/design_system.md`.
- Evite cores aleatórias ou estética fora da marca.
- Preferir classes Tailwind consistentes (`bg-neutral-*`, `text-*`, `border-white/10`, etc.) e valores já usados no projeto.
- Valores arbitrários (`[#FFC400]`, `p-[27px]`) são aceitáveis apenas quando representarem token/decisão de marca ainda não mapeada.
- Não espalhe o mesmo valor arbitrário em vários lugares; se repetir, crie padrão reutilizável.
- Use escala consistente de espaçamento: `1`, `2`, `3`, `4`, `6`, `8`, `10`, `12`, `16`.
- Grid para composição em blocos; flex para alinhamento interno.

## Componentização Blade

Extraia componentes/partials quando:

- o bloco aparece em mais de uma tela;
- o HTML começa a prejudicar leitura da página;
- há variações previsíveis do mesmo padrão;
- existe estado comum: loading, empty, erro, ativo/inativo.

Evite extrair quando:

- o bloco é usado uma única vez e está simples;
- a abstração exigiria muitas props confusas;
- a tela ainda está em descoberta visual.

Componentes comuns devem padronizar:

- botões e links de ação;
- cards e painéis;
- badges/status;
- inputs e mensagens de erro;
- empty states;
- tabelas/listas;
- headers de página;
- modais/drawers, se existirem no projeto.

## Botões e Ações

- Cada tela deve ter uma ação principal evidente.
- Botão primário usa amarelo GarageON e texto escuro.
- Botões secundários usam fundo escuro/transparente, borda sutil e texto claro.
- Ações destrutivas usam vermelho apenas quando houver risco real.
- Nunca estilize botões de forma diferente sem motivo de produto.
- Ações destrutivas devem pedir confirmação.

## Formulários

- Use labels visíveis, mensagens de erro claras e áreas clicáveis confortáveis.
- Placeholders ajudam, mas não substituem labels.
- Agrupe campos por intenção.
- Formulários longos devem ser divididos em etapas ou seções.
- Mostre progresso quando houver fluxo de múltiplos passos.
- Não comece uma tela por formulário quando houver resultado, contexto ou recomendação mais importante.

## Cards, KPIs e Dashboards

- Um card deve responder uma pergunta.
- KPIs precisam de número forte, rótulo claro e contexto de impacto.
- Dashboard deve seguir: contexto, resumo, KPIs, insights, gráficos/listas.
- Tabelas e listagens vêm depois da síntese executiva.
- Evite excesso de cards competindo pela atenção.

## Tabelas e Listas

- Tabelas devem ser minimalistas e escaneáveis.
- Use paginação quando houver muitos registros.
- Forneça busca/filtro quando a lista crescer.
- Destaque status, próximos passos e ações.
- Em mobile, prefira cards/listas empilhadas quando tabela ficar ruim.
- Nunca transformar o painel em planilha densa.

## Estados de Interface

Loading:

- nunca deixar tela branca ou travada;
- usar skeleton, shimmer discreto, spinner contextual ou placeholder;
- preservar estrutura visual enquanto carrega.

Empty state:

- nunca mostrar apenas "Sem dados";
- explicar o estado, reforçar valor e oferecer CTA.

Erro:

- explicar o que aconteceu e como tentar resolver;
- evitar mensagem técnica;
- manter tom humano e recuperável.

Toast:

- usar para sucesso, erro ou aviso temporário;
- não usar para informação permanente ou decisão importante.

## Modais e Drawers

- Use modal para confirmação, criação curta ou detalhe pontual.
- Use drawer para filtros, configurações rápidas e formulários laterais.
- Todo modal/drawer precisa de título, descrição quando útil, ação principal e cancelamento claro.
- Deve fechar por `Esc` e ter foco acessível quando houver JS controlando interação.
- Não use modal para fluxos longos que merecem página própria.

## JavaScript

- Use JS apenas quando HTML/Blade/Tailwind não resolverem bem.
- Mantenha scripts pequenos e próximos do comportamento que controlam.
- Evite estado global desnecessário.
- Debounce em busca/filtros dinâmicos.
- Throttle em eventos frequentes como scroll/resize.
- Não chamar API diretamente de múltiplos pontos duplicados; centralize quando houver repetição.

## Acessibilidade

- HTML semântico primeiro.
- Labels sempre presentes em campos.
- Foco visível em elementos interativos.
- Navegação por teclado funcionando.
- Contraste alto.
- `aria-label` apenas quando o texto visível não for suficiente.
- Não depender apenas de cor para indicar status.
- Ícones não substituem texto em ações críticas.

## Responsividade

- Desktop é prioridade do produto, mas mobile não pode quebrar.
- Use breakpoints Tailwind (`sm`, `md`, `lg`, `xl`, `2xl`) de forma intencional.
- Evite larguras fixas que quebrem layout.
- Em telas pequenas, priorize resumo, ação principal e conteúdo essencial.
- Menus, tabelas e grids devem se adaptar sem perder clareza.

## Performance

- Renderize primeiro o bloco mais útil da página.
- Use paginação para listas grandes.
- Evite loops Blade pesados com relações não carregadas.
- Previna N+1 preparando dados no backend.
- Use lazy loading para imagens abaixo da dobra.
- Evite bibliotecas só para efeitos visuais pequenos.
- Mantenha Vite/Tailwind sem classes mortas ou padrões duplicados quando possível.

## Dependências

Antes de instalar biblioteca, confirme:

- resolve um problema real e recorrente;
- não há solução simples com Laravel, Blade, Tailwind ou JS nativo;
- combina com a arquitetura do projeto;
- não aumenta demais o bundle ou manutenção.

## Checklist Antes de Finalizar

- Seguiu `.ai/design_system.md`?
- Reutilizou padrão existente quando possível?
- A tela tem uma ação principal clara?
- Loading, empty state e erro foram tratados?
- Formulários possuem labels e mensagens úteis?
- Tabelas/listas têm paginação ou estratégia para crescer?
- Layout funciona em desktop e não quebra no mobile?
- Contraste, foco e teclado foram considerados?
- Não há duplicação visual fácil de extrair?
- O código está simples, sem framework ou dependência desnecessária?

Regra final: se a interface parecer apenas um sistema administrativo comum, revise até parecer um cockpit premium do GarageON.
