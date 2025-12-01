<?php

declare(strict_types=1);

namespace App\Domain\Product\Events;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Product Deleted Event
 *
 * Dispatched when a product is deleted.
 */
final readonly class ProductDeletedEvent
{
    public string $eventId;
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public int $productId,
        public string $productUuid,
        public string $productSku
    ) {
        $this->eventId = (string) Str::uuid();
        $this->occurredAt = new DateTimeImmutable();
    }

    /**
     * Get the event type identifier.
     */
    public function getEventType(): string
    {
        return 'product.deleted';
    }

    /**
     * Convert event to array for Kafka/Queue.
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => $this->getEventType(),
            'occurred_at' => $this->occurredAt->format(DateTimeInterface::ATOM),
            'payload' => [
                'product_id' => $this->productId,
                'uuid' => $this->productUuid,
                'sku' => $this->productSku,
            ],
        ];
    }
}
