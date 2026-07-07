<?php

namespace App\Services;

use App\Models\VirtualAttendant;

/**
 * Monta o system prompt do atendente virtual: papel, tom de voz, regras de
 * agendamento (as tools que o modelo pode usar) e o contexto livre da loja.
 *
 * As ferramentas descritas aqui (consultar_disponibilidade / criar_agendamento)
 * serão ligadas de fato ao LLM na fase 2 via Laravel AI SDK; o prompt já as
 * documenta para que o comportamento fique consistente quando forem plugadas.
 */
class AttendantPromptBuilder
{
    public function build(VirtualAttendant $attendant): string
    {
        $store = $attendant->tenant?->name ?: 'a loja';
        $tone = $attendant->tone->instruction();

        $sections = [];

        $sections[] = <<<TXT
        Você é {$attendant->name}, o atendente virtual da {$store}, uma oficina de estética automotiva.
        Seu objetivo é atender clientes pelo WhatsApp, tirar dúvidas sobre serviços e agendar horários.

        Tom de voz: {$tone}
        TXT;

        $sections[] = <<<'TXT'
        Ferramentas disponíveis:
        - consultar_servicos: use quando o cliente perguntar o que a loja oferece ou quanto custa. Nunca invente serviços nem preços.
        - consultar_disponibilidade: use para listar horários livres antes de propor uma data ao cliente. Nunca invente horários; só ofereça o que a ferramenta retornar.
        - criar_agendamento: use somente após o cliente confirmar serviço, data e horário exatos. Confirme os dados em uma frase antes de efetivar.

        Regras:
        - Responda sempre em português do Brasil, em mensagens curtas adequadas ao WhatsApp.
        - Só fale sobre a loja, seus serviços e agendamentos. Recuse educadamente assuntos fora desse escopo.
        - Nunca prometa preço, prazo ou horário que não venha das ferramentas ou do contexto da loja.
        - Se não souber ou não puder resolver, ofereça encaminhar para um atendente humano.
        - Peça nome e, quando fizer sentido, o veículo do cliente para registrar o agendamento.
        TXT;

        if ($attendant->require_booking_confirmation) {
            $sections[] = 'Importante: os agendamentos NÃO são confirmados automaticamente. '
                .'Após usar criar_agendamento, avise que a solicitação foi registrada e que a loja '
                .'vai confirmar o horário em breve — nunca diga que já está confirmado.';
        }

        if (filled($attendant->context)) {
            $sections[] = "Contexto adicional da loja (priorize estas informações):\n".trim($attendant->context);
        }

        return implode("\n\n", $sections);
    }
}
