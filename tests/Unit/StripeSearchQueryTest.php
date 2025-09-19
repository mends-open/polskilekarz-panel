<?php

declare(strict_types=1);

it('builds an equality clause through chaining', function () {
    $query = stripeSearchQuery()->status()->equals('succeeded');

    expect($query->toString())->toBe("status:'succeeded'");
});

it('formats timestamps when using comparison operators', function () {
    $date = new DateTimeImmutable('2024-01-02 03:04:05', new DateTimeZone('UTC'));

    $query = stripeSearchQuery()->created()->greaterThan($date);

    expect((string) $query)->toBe('created>1704164645');
});

it('combines clauses with AND and OR operators', function () {
    $query = stripeSearchQuery()
        ->currency()->equals('usd')
        ->andMetadata('order_id')->equals('42')
        ->orField('payment_intent')->equals('pi_123');

    expect((string) $query)->toBe("(currency:'usd' AND metadata['order_id']:'42') OR payment_intent:'pi_123'");
});

it('negates clauses with the minus operator', function () {
    $query = stripeSearchQuery()->status()->equals('failed')->not();

    expect($query->toString())->toBe("-status:'failed'");
});

it('builds existence clauses', function () {
    $query = stripeSearchQuery()->metadata('order_id')->exists();

    expect((string) $query)->toBe("-metadata['order_id']:null");
});

it('casts metadata comparisons to strings when necessary', function () {
    $query = stripeSearchQuery()->metadata('order_id')->equals(123);

    expect($query->toString())->toBe("metadata['order_id']:'123'");
});

it('supports numeric comparisons without quotes', function () {
    $query = stripeSearchQuery()->amount()->lessThanOrEquals(1000);

    expect($query->toString())->toBe('amount<=1000');
});

it('casts numeric strings to numbers for numeric fields', function () {
    $query = stripeSearchQuery()->total()->equals('42');

    expect((string) $query)->toBe('total:42');
});

it('builds documented nested field clauses', function () {
    $query = stripeSearchQuery()->billingDetailsAddressPostalCode()->equals('12345');

    expect((string) $query)->toBe("billing_details.address.postal_code:'12345'");
});

it('builds payment method detail clauses from helper methods', function () {
    $query = stripeSearchQuery()->paymentMethodDetailsLast4('card')->equals('1234');

    expect($query->toString())->toBe("payment_method_details.card.last4:'1234'");
});

it('rejects invalid payment method detail sources', function () {
    stripeSearchQuery()->paymentMethodDetailsLast4('card present');
})->throws(InvalidArgumentException::class);

it('supports helpers from other Stripe resources', function () {
    $query = stripeSearchQuery()
        ->active()->equals(true)
        ->and(stripeSearchQuery()->description()->equals('Test Product'))
        ->or(stripeSearchQuery()->canceledAt()->exists());

    expect($query->toString())->toBe("(active:true AND description:'Test Product') OR canceled_at:'*'");
});

it('casts boolean fields from string input', function () {
    $query = stripeSearchQuery()->refunded()->equals('1');

    expect((string) $query)->toBe('refunded:true');
});

it('rejects unsupported operators for string fields', function () {
    expect(fn () => stripeSearchQuery()->status()->greaterThan('succeeded'))
        ->toThrow(InvalidArgumentException::class);
});

it('requires pending fields to be resolved before combining', function () {
    $builder = stripeSearchQuery();

    expect(fn () => $builder->and(stripeSearchQuery()->canceledAt()))
        ->toThrow(BadMethodCallException::class);
});

it('requires pending fields to be resolved before grouping', function () {
    expect(fn () => stripeSearchQuery()->andGroup(fn ($query) => $query->canceledAt()))
        ->toThrow(BadMethodCallException::class);
});
