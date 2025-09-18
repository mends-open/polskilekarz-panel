<?php

declare(strict_types=1);

it('builds an equality clause through chaining', function () {
    $query = stripeSearchQuery()->field('status')->equals('succeeded');

    expect($query->toString())->toBe("status:'succeeded'");
});

it('formats timestamps when using comparison operators', function () {
    $date = new DateTimeImmutable('2024-01-02 03:04:05', new DateTimeZone('UTC'));

    $query = stripeSearchQuery()->field('created')->greaterThan($date);

    expect((string) $query)->toBe('created>1704164645');
});

it('combines clauses with AND and OR operators', function () {
    $query = stripeSearchQuery()
        ->field('status')->equals('succeeded')
        ->andMetadata('order_id')->equals('42')
        ->orField('payment_intent')->equals('pi_123');

    expect((string) $query)->toBe("(status:'succeeded' AND metadata['order_id']:'42') OR payment_intent:'pi_123'");
});

it('negates clauses with the minus operator', function () {
    $query = stripeSearchQuery()->field('status')->equals('failed')->not();

    expect($query->toString())->toBe("-status:'failed'");
});

it('builds existence clauses', function () {
    $query = stripeSearchQuery()->metadata('order_id')->exists();

    expect((string) $query)->toBe("metadata['order_id']:'*'");
});

it('supports numeric comparisons without quotes', function () {
    $query = stripeSearchQuery()->field('amount')->lessThanOrEquals(1000);

    expect($query->toString())->toBe('amount<=1000');
});
