# GarageON UX Patterns

Padroes obrigatorios de experiencia para agentes repetirem automaticamente ao criar ou alterar interfaces do GarageON. Use este arquivo junto com `.ai/design_system.md` e `.ai/frontend_rules.md`.

Este guia existe para manter consistencia por muitos anos, reduzir decisoes subjetivas, acelerar implementacao e proteger a qualidade do produto diante de crescimento, investidores, novos times e novas funcionalidades.

## Principios De Uso

- Resultado antes de operacao: comece mostrando impacto, contexto e proximo passo.
- Uma tela, uma prioridade: toda interface deve deixar clara a acao principal.
- Densidade controlada: sistemas operacionais podem ser ricos sem parecerem planilhas.
- Progresso visivel: sempre mostre estado, feedback, carregamento, erro e conclusao.
- Escaneabilidade primeiro: titulos, KPIs, status e acoes devem ser entendidos em segundos.
- Acao recuperavel: evite becos sem saida; todo estado deve oferecer caminho seguinte.
- IA como vantagem operacional: a interface deve parecer inteligente, nao decorada com chatbot.
- Consistencia acima de novidade: crie padrao novo apenas quando o existente nao resolver.

## Logos E Marca

Assets oficiais do GarageON:

- Logo horizontal: `resources/img/logo-horizontal.png`.
- Logo vertical: `resources/img/logo-vertical.png`.
- Icone: `resources/img/icon.png`.

Regras:

- Use `logo-horizontal.png` em headers largos, landing pages, barras superiores e contextos com boa largura.
- Use `logo-vertical.png` em telas de login, onboarding, apresentacoes institucionais e composicoes com foco de marca.
- Use `icon.png` em favicons, estados compactos, sidebar recolhida, cards pequenos e pontos onde a marca precisa aparecer sem ocupar espaco.
- Nao recrie o logo com texto, icones genericos ou tipografia manual quando um asset oficial puder ser usado.
- Preserve proporcao, respiro visual e contraste. Nunca distorca, comprima, rotacione ou aplique efeitos que prejudiquem legibilidade.

## Anatomia Padrao De Pagina

Use esta ordem como base para telas internas:

1. Header com contexto humano, status da area e acao principal.
2. Sintese executiva com KPIs, alertas ou insights.
3. Conteudo operacional principal: tabela, lista, calendario, funil, grafico ou formulario.
4. Acoes secundarias, filtros, configuracoes e historico.
5. Estados de suporte: empty, loading, erro, ajuda contextual e notificacoes.

Toda pagina deve responder rapidamente:

- O que esta acontecendo agora?
- O que mudou desde o periodo anterior?
- Onde exige atencao?
- Qual acao devo tomar primeiro?

## Dashboards

Dashboard e centro de comando, nao mural de graficos.

Estrutura recomendada:

1. Header com saudacao/contexto, periodo ativo e CTA principal.
2. Faixa de resumo: 3 a 5 KPIs realmente decisivos.
3. Bloco de inteligencia: oportunidades, riscos, gargalos e recomendacoes.
4. Visualizacoes principais: graficos ou listas que expliquem causa e tendencia.
5. Operacao do dia: proximos agendamentos, leads quentes, atrasos, follow-ups.

Regras:

- Nunca comece dashboard com tabela bruta.
- Evite mais de 6 cards acima da dobra.
- Cada KPI precisa de numero, label, periodo e contexto de comparacao.
- Graficos devem responder uma pergunta especifica, nao preencher espaco.
- Destaque anomalias: queda, pico, oportunidade, gargalo, atraso ou risco.
- Mostre recomendacao acionavel quando possivel: "Recuperar 8 clientes inativos".

Checklist:

- Existe uma leitura executiva em menos de 10 segundos?
- O periodo analisado esta claro?
- Os dados ajudam a decidir, nao apenas observar?
- A acao principal esta visivel sem rolagem em desktop?

## CRUDs

CRUD deve ser fluxo operacional, nao apenas quatro telas de banco de dados.

Lista/index:

- Header com nome da entidade, contagem, status e CTA principal.
- KPIs ou resumo antes da tabela quando a entidade tiver impacto operacional.
- Busca e filtros antes da listagem, mas sem ocupar a tela inteira.
- Tabela/lista com status, informacao-chave e acoes frequentes.
- Empty state com CTA para criar primeiro registro ou importar dados.

Criar:

- Formulario orientado a tarefa, com campos agrupados por decisao.
- Campos obrigatorios devem ser previsiveis e bem marcados.
- CTA principal com verbo de negocio: "Cadastrar cliente", "Salvar servico".
- Evite pedir dados que podem ser completados depois sem bloquear valor.

Editar:

- Mostre identidade do registro no topo: nome, status, ultima atualizacao.
- Preserve contexto de origem quando possivel: voltar para lista, cliente ou agenda.
- Indique campos criticos e efeitos colaterais da alteracao.

Detalhe/show:

- Comece com resumo, status e acoes relevantes.
- Mostre historico, eventos e relacoes importantes abaixo.
- Use cards para informacoes de alto valor e tabelas apenas para historico/listas.

Excluir:

- Use confirmacao clara para acoes destrutivas.
- Informe consequencia real: dados vinculados, historico, agenda, financeiro.
- Prefira arquivar/inativar quando o historico operacional precisar ser preservado.

## Telas De Cadastro

Cadastro deve ser rapido, confiavel e sem atrito desnecessario.

Estrutura:

1. Titulo orientado a objetivo.
2. Descricao curta explicando valor do cadastro.
3. Blocos de campos por intencao: identificacao, contato, operacao, preferencias.
4. Ajuda contextual apenas onde reduz erro.
5. CTA principal fixo ou facil de encontrar em formularios longos.

Regras:

- Labels sempre visiveis; placeholder nunca substitui label.
- Agrupe campos relacionados visualmente.
- Valide cedo quando possivel, sem interromper digitacao.
- Mensagens de erro devem explicar correcao: "Informe um telefone com DDD".
- Mascara deve ajudar entrada, nao esconder erro.
- Campo opcional deve ser realmente opcional.
- Em mobile, use uma coluna e botoes com area de toque confortavel.

Padrao de copy:

- Bom: "Dados do cliente", "Como vamos falar com ele?", "Preferencias de atendimento".
- Evite: "Informacoes gerais", "Dados complementares", "Formulario de cadastro".

## Wizards

Wizard e indicado quando o fluxo exige decisoes sequenciais ou muitos campos com dependencia.

Use wizard para:

- Onboarding de loja.
- Criacao de orcamento complexo.
- Configuracao inicial de agenda, servicos ou automacoes.
- Processos com revisao antes de concluir.

Nao use wizard para:

- Formularios curtos.
- Edicao simples.
- Fluxos onde o usuario precisa comparar tudo ao mesmo tempo.

Estrutura:

1. Indicador de progresso com etapas nomeadas.
2. Uma decisao principal por etapa.
3. Resumo lateral ou final quando houver impacto acumulado.
4. Navegacao clara: voltar, continuar, salvar rascunho quando necessario.
5. Revisao final antes de concluir se houver custo, disparo, agenda ou dados sensiveis.

Regras:

- Permita voltar sem perder dados.
- Nao esconda erros apenas no final.
- Mostre o que sera desbloqueado ao concluir.
- Evite etapas artificiais criadas apenas por layout.

## KPIs

KPI e decisao condensada, nao numero decorativo.

Anatomia obrigatoria:

- Label claro: "Faturamento previsto".
- Numero principal com destaque.
- Periodo ou recorte: "Hoje", "Este mes", "Ultimos 30 dias".
- Comparacao ou contexto: "+12% vs. mes anterior".
- Direcao visual sem depender apenas de cor.

Regras:

- Use `font-orbitron` para numeros de alto impacto quando combinar com a tela.
- Maximo recomendado: 4 KPIs principais por linha em desktop.
- Evite KPI sem acao ou interpretacao.
- Diferencie metrica de vaidade de metrica operacional.
- Quando o numero for zero, explique se e bom, ruim ou esperado.

Exemplos fortes:

- "R$ 18.420 em orcamentos abertos" com contexto "42% vencem esta semana".
- "9 clientes para retorno" com CTA "Acionar campanha".
- "87% de ocupacao" com alerta "Sabado esta no limite".

## Analytics

Analytics deve explicar comportamento, tendencia e causa provavel.

Estrutura:

1. Pergunta de negocio no titulo: "De onde vieram os melhores clientes?".
2. Filtros de periodo, loja/canal/status quando aplicavel.
3. Grafico ou comparativo principal.
4. Insights interpretados em linguagem humana.
5. Caminho para acao: ajustar campanha, recuperar clientes, reorganizar agenda.

Regras:

- Sempre mostrar periodo analisado.
- Evite grafico sem legenda, eixo ou unidade.
- Prefira comparacoes claras a visualizacoes complexas.
- Mostre amostra pequena com cautela: "dados ainda insuficientes".
- Separe analise historica de previsao.
- Quando houver IA, deixe claro se e recomendacao, estimativa ou dado confirmado.

## Empty States

Estado vazio e oportunidade de orientar, nao ausencia de interface.

Anatomia:

- Titulo humano e especifico.
- Texto curto explicando por que esta vazio.
- Beneficio da proxima acao.
- CTA principal.
- Opcional: acao secundaria, exemplo ou link de ajuda.

Regras:

- Nunca usar apenas "Sem dados", "Nenhum registro" ou tela em branco.
- Diferencie primeiro uso, filtro sem resultado e erro de carregamento.
- Se filtros causaram vazio, ofereca limpar filtros.
- Se permissao causou vazio, explique acesso necessario sem expor dados.

Exemplos:

- Primeiro uso: "Nenhum cliente cadastrado ainda. Cadastre o primeiro cliente para acompanhar retornos e oportunidades.".
- Filtro vazio: "Nao encontrei clientes com esses filtros. Ajuste a busca ou limpe os filtros para ver toda a base.".
- Operacao do dia vazia: "Agenda livre hoje. Aproveite para reativar clientes inativos ou abrir horarios promocionais.".

## Skeletons

Skeleton deve preservar layout e reduzir ansiedade.

Use skeleton para:

- Cards de KPI.
- Tabelas/listas.
- Graficos.
- Perfil/detalhe com blocos previsiveis.

Regras:

- O skeleton deve ter tamanho parecido com o conteudo final.
- Use shimmer discreto, sem excesso de movimento.
- Nao misture muitos spinners na mesma tela.
- Se carregar em partes, mostre primeiro o bloco mais util.
- Preserve a posicao dos elementos para evitar salto visual.
- Em carregamentos longos, acrescente texto contextual: "Buscando oportunidades da semana...".

## Sidebars

Sidebar e navegação estrutural, nao deposito de links.

Estrutura:

- Marca e status da loja no topo.
- Navegacao principal agrupada por tarefa: Comando, Agenda, Clientes, Vendas, Marketing, Configuracoes.
- Item ativo evidente com borda/acento amarelo.
- Acoes globais ou status de plano em area secundaria.

Regras:

- Labels devem ser claros mesmo com icone.
- Evite mais de 7 itens principais visiveis sem agrupamento.
- Em mobile, transforme em drawer/menu acessivel.
- Mantenha ordem estavel; nao reorganize dinamicamente itens principais.
- Diferencie navegacao de acao. "Novo cliente" pode ser CTA, nao item comum.
- Indique badges apenas quando forem acionaveis ou relevantes.

## Drawers

Drawer e painel lateral para contexto sem tirar o usuario da tela.

Use drawer para:

- Filtros avancados.
- Detalhe rapido de cliente, veiculo, servico ou alerta.
- Edicao curta relacionada a uma lista.
- Configuracao contextual.

Nao use drawer para:

- Fluxos longos.
- Confirmacoes criticas.
- Conteudo que exige comparacao ampla.

Regras:

- Titulo e descricao devem explicar o escopo.
- Mantenha acao principal fixa no rodape quando o conteudo rolar.
- Fechar por `Esc`, botao visivel e clique externo quando seguro.
- Preserve estado da pagina por tras.
- Em mobile, drawer pode ocupar tela cheia com header claro.

## Modais

Modal interrompe fluxo; use com criterio.

Use modal para:

- Confirmacao destrutiva.
- Criacao curta.
- Aviso critico que exige decisao.
- Detalhe pontual com pouca informacao.

Nao use modal para:

- Cadastro longo.
- Dashboard dentro de dashboard.
- Fluxos multi-etapa complexos.
- Conteudo que o usuario precisa consultar enquanto preenche.

Anatomia:

- Titulo direto.
- Descricao com consequencia ou contexto.
- Conteudo enxuto.
- Acao principal.
- Cancelamento claro.

Regras:

- Foco deve entrar no modal e retornar ao gatilho ao fechar.
- `Esc` deve fechar quando nao houver risco de perda de dados.
- Acoes destrutivas devem usar texto especifico: "Excluir cliente", nao "Confirmar".
- Se houver dados nao salvos, confirme antes de descartar.

## Tabelas

Tabela e ferramenta de decisao operacional, nao exportacao visual do banco.

Estrutura:

- Toolbar com busca, filtros principais, ordenacao e acao principal quando fizer sentido.
- Cabecalho com colunas essenciais.
- Linhas com informacao primaria, status, contexto e acao frequente.
- Rodape com paginacao e resumo de resultados.

Regras:

- Primeira coluna deve identificar claramente o registro.
- Ultima coluna deve concentrar acoes.
- Status deve ter texto e sinal visual, nao apenas cor.
- Evite mais colunas do que cabem com leitura confortavel.
- Em mobile, converta para cards empilhados com os mesmos dados essenciais.
- Ordenacao deve ser visivel quando ativa.
- Acoes destrutivas nao devem ser o primeiro clique visivel.
- Datas devem ser humanas quando ajudarem: "Hoje, 14:30"; historico pode usar data completa.

## Filtros

Filtro deve reduzir ruido, nao criar formulario paralelo.

Regras:

- Exiba filtros primarios sempre visiveis: periodo, status, busca, responsavel/canal quando essencial.
- Coloque filtros avancados em drawer, popover ou area expansivel.
- Mostre filtros ativos como chips removiveis.
- Ofereca "Limpar filtros" quando houver qualquer filtro ativo.
- Preserve filtros ao paginar e ao voltar da tela de detalhe quando possivel.
- Use defaults inteligentes, geralmente periodo recente e status operacional.
- Nao aplique filtro que esconda dados sem deixar claro.

## Pesquisa

Pesquisa deve ser tolerante, rapida e previsivel.

Regras:

- Campo de busca com label ou placeholder claro: "Buscar por cliente, placa ou telefone".
- Debounce em busca dinamica.
- Permita Enter para pesquisar quando nao for dinamico.
- Busque por campos que o usuario espera, nao apenas nome exato.
- Destaque termo encontrado quando fizer sentido.
- Para nenhum resultado, ofereca limpar busca ou criar novo registro se apropriado.
- Evite busca global se o escopo for local; deixe claro onde esta pesquisando.

## Paginacao

Paginacao deve manter performance e orientacao.

Regras:

- Use paginacao em listas grandes por padrao.
- Mostre intervalo e total quando disponivel: "1-25 de 240 clientes".
- Mantenha filtros, ordenacao e busca ao mudar pagina.
- Botao anterior/proximo deve ter estado desabilitado claro.
- Em mobile, priorize anterior/proximo e resumo; numeros podem ser reduzidos.
- Use infinite scroll apenas para feeds exploratorios, nao para operacao que exige localizar, comparar ou auditar.
- Apos alterar filtro, volte para primeira pagina.

## IA Conversacional

IA no GarageON deve funcionar como copiloto operacional, nao personagem.

Principios:

- Fale como consultor comercial experiente.
- Seja especifico, acionavel e transparente sobre limite dos dados.
- Priorize recomendacoes embutidas na interface antes de abrir chat.
- Chat deve ser canal de comando e explicacao, nao substituto de telas mal desenhadas.

Padroes de uso:

- Sugestoes em cards: "Encontrei 12 clientes com chance de retorno esta semana".
- Acoes assistidas: "Criar campanha", "Gerar mensagem", "Priorizar agenda".
- Explicacao sob demanda: "Por que estou vendo isso?".
- Conversa contextual: IA sabe a tela, filtros e entidade aberta, sem pedir tudo de novo.

Regras:

- Nao diga "sou um chatbot".
- Diferencie fato, inferencia e sugestao.
- Nunca prometa envio, cobranca ou acao externa sem confirmacao clara.
- Mostre revisao antes de disparar mensagens para clientes.
- Ofereca desfazer/cancelar quando possivel.
- Registre acoes importantes feitas com apoio de IA.
- Evite respostas longas; prefira diagnostico, motivo e CTA.

Exemplo de resposta boa:

"Identifiquei 8 clientes que fizeram vitrificacao ha mais de 6 meses e nao retornaram. Posso montar uma campanha com mensagem personalizada para recuperar esses agendamentos."

## Notificacoes

Notificacao deve orientar prioridade e acao, nao apenas informar evento.

Tipos:

- Toast: feedback temporario de sucesso, erro ou aviso apos uma acao.
- Banner: informacao importante persistente dentro de uma tela.
- Alerta operacional: risco, oportunidade ou pendencia que exige acao.
- Badge: contador pequeno para chamar atencao em navegacao ou lista.
- Inbox/central: historico de alertas e eventos relevantes.

Regras:

- Toda notificacao precisa de severidade, contexto e proximo passo quando aplicavel.
- Toast nao deve carregar informacao critica que desaparece sem alternativa.
- Nao empilhe muitos toasts; agrupe eventos repetidos.
- Use som apenas se o produto exigir e com controle do usuario.
- Notificacoes criticas devem ser persistentes ate resolucao ou dispensa consciente.
- Evite alarmismo. Seja direto: impacto, urgencia e acao.

Copy recomendada:

- Sucesso: "Cliente cadastrado. Ja da para agendar o primeiro atendimento.".
- Aviso: "Esse horario esta quase cheio. Confira a capacidade antes de confirmar.".
- Erro: "Nao consegui salvar agora. Verifique os campos destacados e tente novamente.".
- Oportunidade: "5 clientes estao prontos para retorno esta semana.".

## Responsividade Padrao

- Desktop: mostrar sintese, operacao e acoes em paralelo quando fizer sentido.
- Notebook: reduzir colunas, manter CTA principal e KPIs visiveis.
- Tablet: priorizar uma ou duas colunas, drawers mais largos e tabelas simplificadas.
- Mobile: resumo primeiro, CTA principal, listas em cards, filtros recolhidos e navegacao simples.

Nunca permita que uma tabela, modal ou sidebar quebre a tela em mobile. Se a densidade ficar alta, transforme em lista, etapa, drawer ou pagina dedicada.

## Acessibilidade Padrao

- HTML semantico antes de `div` interativa.
- Labels visiveis em campos.
- Foco visivel em botoes, links, inputs, modais e drawers.
- Navegacao por teclado em menus, modais e acoes de tabela.
- Contraste alto em texto, bordas essenciais e estados.
- Status com texto, icone ou label alem da cor.
- Alvos clicaveis confortaveis, especialmente em mobile.
- Mensagens de erro associadas ao campo correspondente.

## Checklist Para Agentes

Antes de finalizar qualquer interface, confirme:

- A tela comeca pelo resultado mais importante?
- Existe uma acao principal clara?
- Os KPIs tem contexto, periodo e direcao?
- A tabela ou lista e escaneavel e paginada quando necessario?
- Busca, filtros e paginacao preservam contexto?
- Empty, loading, erro e sucesso foram tratados?
- Modal/drawer foi usado pelo motivo certo?
- Mobile nao quebra e mantem a tarefa principal possivel?
- Textos parecem humanos, comerciais e orientados a acao?
- A IA aparece como inteligencia operacional, nao como enfeite?
- O visual continua premium, escuro, automotivo e consistente com GarageON?

Regra final: se a interface nao ajuda uma oficina a decidir melhor, vender mais, atender melhor ou economizar tempo, ela ainda nao esta pronta.
