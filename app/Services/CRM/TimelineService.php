<?php

namespace App\Services\CRM;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Followup;
use App\Models\Message;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Task;
use Illuminate\Support\Collection;

class TimelineService
{
    /**
     * Obter timeline unificada e cronológica de eventos de uma empresa ou contato.
     *
     * @return Collection<int, array>
     */
    public function getCustomerTimeline(?Company $company = null, ?Contact $contact = null, int $limit = 50): Collection
    {
        $events = collect();

        $companyId = $company?->id ?? $contact?->company_id;
        $contactId = $contact?->id;

        // 1. Mensagens de WhatsApp
        $conversationIds = Conversation::query()
            ->when($contactId, fn($q) => $q->where('contact_id', $contactId))
            ->when(!$contactId && $companyId, function ($q) use ($companyId) {
                $contactIds = Contact::where('company_id', $companyId)->pluck('id');
                $q->whereIn('contact_id', $contactIds);
            })
            ->pluck('id');

        if ($conversationIds->isNotEmpty()) {
            $messages = Message::whereIn('conversation_id', $conversationIds)
                ->latest()
                ->take($limit)
                ->get();

            foreach ($messages as $msg) {
                $isOutbound = $msg->direction === 'outbound';
                $sender = match ($msg->sender_type) {
                    'ai' => 'Assistente IA',
                    'representative' => 'Representante',
                    'customer' => 'Cliente',
                    default => 'Sistema',
                };

                $events->push([
                    'type' => 'message',
                    'title' => ($isOutbound ? 'Mensagem enviada' : 'Mensagem recebida') . " ({$sender})",
                    'description' => $msg->content,
                    'timestamp' => $msg->created_at,
                    'badge' => $isOutbound ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                    'icon' => 'message-square',
                    'metadata' => [
                        'sender_type' => $msg->sender_type,
                        'status' => $msg->status,
                    ],
                ]);
            }
        }

        // 2. Cotações
        $quotes = Quote::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($contactId, fn($q) => $q->where('contact_id', $contactId))
            ->latest()
            ->take($limit)
            ->get();

        foreach ($quotes as $quote) {
            $events->push([
                'type' => 'quote',
                'title' => "Cotação {$quote->quote_number} (" . strtoupper($quote->status) . ")",
                'description' => "Valor total: R$ " . number_format($quote->total_amount, 2, ',', '.') . " • " . ($quote->items()->count()) . " itens",
                'timestamp' => $quote->created_at,
                'badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                'icon' => 'file-text',
                'metadata' => [
                    'quote_id' => $quote->id,
                    'status' => $quote->status,
                ],
            ]);
        }

        // 3. Pedidos
        $orders = Order::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($contactId, fn($q) => $q->where('contact_id', $contactId))
            ->latest()
            ->take($limit)
            ->get();

        foreach ($orders as $order) {
            $events->push([
                'type' => 'order',
                'title' => "Pedido {$order->order_number} (" . strtoupper($order->status) . ")",
                'description' => "Valor total: R$ " . number_format($order->total_amount, 2, ',', '.') . " • Pagamento: {$order->payment_terms}",
                'timestamp' => $order->created_at,
                'badge' => 'bg-green-500/10 text-green-400 border-green-500/20',
                'icon' => 'shopping-cart',
                'metadata' => [
                    'order_id' => $order->id,
                    'status' => $order->status,
                ],
            ]);
        }

        // 4. Tarefas e Follow-ups
        $tasks = Task::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($contactId, fn($q) => $q->where('contact_id', $contactId))
            ->latest()
            ->take($limit)
            ->get();

        foreach ($tasks as $task) {
            $events->push([
                'type' => 'task',
                'title' => "Tarefa: {$task->title} (" . strtoupper($task->status) . ")",
                'description' => $task->description ?? 'Sem descrição adicional.',
                'timestamp' => $task->created_at,
                'badge' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                'icon' => 'check-square',
                'metadata' => [
                    'due_date' => $task->due_date?->format('d/m/Y H:i'),
                    'priority' => $task->priority,
                ],
            ]);
        }

        // Ordenar tudo cronologicamente decrescente (mais recente primeiro)
        return $events->sortByDesc('timestamp')->values()->take($limit);
    }
}
