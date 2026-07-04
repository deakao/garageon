# GarageON Design System

Guia obrigatório para qualquer tela, componente ou ajuste visual do GarageON.

## Essência da Marca

GarageON é o centro de comando inteligente de uma oficina de estética automotiva. A interface deve parecer premium, rápida e orientada a resultado, nunca um ERP antigo, sistema governamental ou painel genérico.

O visual deve transmitir:

- tecnologia, inteligência e automação;
- confiança, crescimento e clareza operacional;
- estética automotiva premium: cockpit, performance, contraste forte, precisão.

Regra principal: mostre resultado antes de configuração. Toda tela deve responder em poucos segundos: "o que aconteceu?", "o que importa agora?" e "qual é o próximo passo?".

## Prioridades de UX

- Automação primeiro: não pedir ao usuário o que o sistema pode inferir.
- Resultado primeiro: KPIs, impacto e oportunidades antes de tabelas e formulários.
- Poucos cliques: ações importantes devem exigir no máximo 3 cliques.
- Foco único: cada seção/card responde uma pergunta clara.
- IA invisível: o usuário vê recomendações e resultados, não "chatbot" ou jargão técnico.
- Crescimento visível: todo número precisa de contexto, direção ou impacto.

## Direção Visual

Use sempre uma estética escura, premium e automotiva:

- Background base: `#0B0B0B`.
- Superfícies/cards: `#111111`, `#151515` ou `#1A1A1A`.
- Texto principal: `#FFFFFF`.
- Texto secundário: `#A3A3A3` ou `#808080`.
- Divisores/bordas: branco com baixa opacidade (`white/10`, `white/15`).
- Destaque GarageON: `#FFC400`.
- Erro/perigo: `#E53935`.
- Sucesso: usar com moderação, preferindo verde escuro/sutil ou o próprio amarelo quando indicar "ON".

O amarelo é acento de performance, não cor de preenchimento em massa. Use para CTA principal, estados ON, foco, métrica positiva e elementos que merecem atenção imediata.

## Tipografia

- Marca, títulos e números de alto impacto: preferir `font-orbitron`.
- Títulos: peso alto, boa respiração, hierarquia clara.
- Corpo: fonte legível do projeto, peso 400/500, line-height confortável.
- KPIs: números grandes, densos e fáceis de comparar.
- Evite textos longos dentro de cards; transforme em métrica, insight ou ação.

## Layout e Composição

- Comece páginas com contexto humano + síntese executiva.
- Depois mostre KPIs, alertas, oportunidades e ações recomendadas.
- Tabelas, filtros e configurações vêm depois do panorama.
- Toda página do painel autenticado deve exibir o header padrão do cockpit no topo, preservando navegação, contexto da loja e ações principais.
- Use grid responsivo com cards escuros, espaçamento generoso e densidade controlada.
- Evite layouts planos: use camadas sutis, gradientes escuros, brilho amarelo discreto, bordas finas e sensação de cockpit.
- Não crie muitas cores competindo. O sistema é preto, branco, cinza e amarelo.

Escala de espaçamento preferida: `4`, `8`, `12`, `16`, `24`, `32`, `40`, `48`, `64`.

Raios:

- Inputs: `12px`.
- Botões: `14px`.
- Cards: `16px`.
- Modais/painéis grandes: `20px`.

Sombras devem ser discretas. Prefira profundidade com contraste, borda translúcida e gradientes.

## Componentes

Botão primário:

- fundo `#FFC400`;
- texto preto;
- uso exclusivo para a ação principal da tela ou seção.

Botão secundário:

- fundo transparente ou escuro;
- borda `white/10` ou `white/15`;
- texto branco/cinza claro.

Botão de perigo:

- vermelho apenas para exclusão, cancelamento destrutivo ou risco real.

Cards:

- um card, uma pergunta;
- título curto, número/insight em destaque e contexto pequeno;
- nunca misturar assuntos no mesmo card.

Inputs:

- limpos, espaçosos, labels presentes e placeholder discreto;
- bordas leves, foco claro, sem aparência de formulário pesado.

Tabelas:

- minimalistas, poucas linhas visíveis, bom espaçamento entre colunas;
- destacar status e ações, não transformar a tela em planilha.

Ícones:

- usar um único estilo por tela, preferindo outline;
- Lucide ou Heroicons são boas opções quando já disponíveis;
- ícones ajudam escaneabilidade, mas não substituem rótulos claros.

## Copy e Mensagens

Escreva como um gerente comercial experiente, direto e humano. Nunca como sistema técnico.

Prefira:

- "Encontrei 12 oportunidades para hoje."
- "Seu cliente já está confirmado."
- "Está tudo pronto."
- "Identifiquei uma queda nas conversões."
- "Posso recuperar esses clientes?"

Evite:

- "Foram encontrados 12 registros."
- "Agendamento criado."
- "Processamento concluído."
- "Sou um chatbot."
- "Erro desconhecido."

Em erros, seja claro e recuperável: "Não consegui concluir essa ação. Vamos tentar novamente."

## Estados de Interface

Loading:

- nunca deixar a tela parada;
- usar skeletons, cards carregando ou indicadores animados;
- manter o layout estável enquanto os dados chegam.

Estado vazio:

- nunca mostrar apenas "Sem dados";
- explicar o estado, reforçar valor e oferecer a próxima ação.

Exemplo: "Você ainda não possui clientes cadastrados. Cadastre o primeiro cliente para começar a acompanhar retornos e oportunidades."

Alertas:

- devem ser acionáveis;
- indicar impacto, urgência e próximo passo;
- não competir com o CTA principal.

## Responsividade e Acessibilidade

- Desktop é prioridade, mas toda tela deve funcionar bem em notebook, tablet e mobile.
- Em mobile, priorize resumo, ação principal e navegação simples; detalhes podem ficar recolhidos.
- Contraste alto é obrigatório.
- Área clicável confortável.
- Labels sempre presentes.
- Navegação por teclado e foco visível.
- Não depender apenas de cor para explicar status.

## Performance Percebida

As telas devem parecer instantâneas:

- usar lazy loading quando fizer sentido;
- paginar listas grandes;
- evitar renderizar tabelas enormes;
- preservar layout durante carregamento;
- priorizar o primeiro bloco útil da página.

## Regras Para Implementar Frontend

- Reutilize componentes e padrões existentes antes de criar novos.
- Em Blade + Tailwind, mantenha classes consistentes com a paleta e evite valores aleatórios.
- Não adicione framework JS pesado para resolver interação simples.
- Evite estética genérica de SaaS: cards brancos, roxo degradê, sombras grandes, layouts sem personalidade.
- Se uma tela parecer formulário administrativo comum, redesenhe para evidenciar resultado e comando.
- Toda nova interface deve parecer parte do mesmo cockpit GarageON.

## Checklist Antes de Finalizar

- O resultado principal aparece em menos de 5 segundos?
- KPIs, impacto ou oportunidades aparecem antes de tabela/formulário?
- Existe apenas uma ação principal clara?
- A tela parece premium, automotiva e tecnológica?
- O amarelo foi usado como acento, sem exagero?
- Os textos parecem humanos e comerciais?
- A IA aparece como inteligência de bastidor, não como chatbot?
- Estados vazio, loading e erro foram tratados?
- Mobile mantém resumo e ação principal utilizáveis?
- O layout ajuda o cliente a manter a empresa sempre ON?

Se alguma resposta for "não", revise o design antes de finalizar.
