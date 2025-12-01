<?php

declare(strict_types=1);

namespace App\Domain\Product\Events;

use App\Domain\Product\Models\Product;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Product Created Event
 *
 * Dispatched when a new product is created.
 */
final readonly class ProductCreatedEvent
{
    public string $eventId;
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public Product $product
    ) {
        $this->eventId = (string) Str::uuid();
        $this->occurredAt = new DateTimeImmutable();
    }

    /**
     * Get the event type identifier.
     */
    public function getEventType(): string
    {
        return 'product.created';
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
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => $this->product->price,
                'quantity' => $this->product->quantity,
            ],
        ];
    }
}
