<?php

declare(strict_types=1);

use App\Domain\Orders\Enums\OrderStatus;

it('follows the approved order lifecycle', function (OrderStatus $from, OrderStatus $to): void {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    [OrderStatus::Pending, OrderStatus::Confirmed],
    [OrderStatus::Confirmed, OrderStatus::Processing],
    [OrderStatus::Processing, OrderStatus::Shipped],
    [OrderStatus::Shipped, OrderStatus::Delivered],
    [OrderStatus::Delivered, OrderStatus::Refunded],
    [OrderStatus::Pending, OrderStatus::Cancelled],
    [OrderStatus::Confirmed, OrderStatus::Cancelled],
    [OrderStatus::Processing, OrderStatus::Cancelled],
]);

it('rejects transitions outside the lifecycle', function (OrderStatus $from, OrderStatus $to): void {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'skip confirmation' => [OrderStatus::Pending, OrderStatus::Shipped],
    'go backwards' => [OrderStatus::Shipped, OrderStatus::Processing],
    'cancel after shipping' => [OrderStatus::Shipped, OrderStatus::Cancelled],
    'reopen cancelled' => [OrderStatus::Cancelled, OrderStatus::Pending],
    'refund before delivery' => [OrderStatus::Processing, OrderStatus::Refunded],
    'same status' => [OrderStatus::Pending, OrderStatus::Pending],
]);

it('treats cancelled and refunded as final', function (): void {
    expect(OrderStatus::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatus::Refunded->isFinal())->toBeTrue()
        ->and(OrderStatus::Delivered->isFinal())->toBeFalse();
});
