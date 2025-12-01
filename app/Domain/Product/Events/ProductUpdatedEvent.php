<?php

declare(strict_types=1);

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\Product;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Product Updated Event
 *
 * Dispatched when a product is updated.
 */
final readonly class ProductUpdatedEvent
{
    public string $eventId;
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public Product $product,
        public array $changedAttributes = []
    ) {
        $this->eventId = (string) Str::uuid();
        $this->occurredAt = new DateTimeImmutable();
    }

    /**
     * Get the event type identifier.
     */
    public function getEventType(): string
    {
        return 'product.updated';
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
                'product_id' => $this->product->id,
                'uuid' => $this->product->uuid,
                'changed_attributes' => $this->changedAttributes,
            ],
        ];
    }
}
