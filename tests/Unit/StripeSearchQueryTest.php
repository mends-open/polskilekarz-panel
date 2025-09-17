<?php

declare(strict_types=1);

use App\Services\StripeSearchQuery;

it('builds an equality clause', function () {
    expect(StripeSearchQuery::equals('status', 'succeeded'))
        ->toBe("status:'succeeded'");
});

it('formats timestamps when using comparison operators', function () {
    $date = new DateTimeImmutable('2024-01-02 03:04:05', new DateTimeZone('UTC'));

    expect(StripeSearchQuery::greaterThan('created', $date))
        ->toBe('created>1704164645');
});

it('combines clauses with AND and OR operators', function () {
    $clause = StripeSearchQuery::any(
        StripeSearchQuery::all(
            StripeSearchQuery::equals('status', 'succeeded'),
            StripeSearchQuery::metadataEquals('order_id', '42'),
        ),
        StripeSearchQuery::equals('payment_intent', 'pi_123'),
    );

    expect($clause)->toBe("(status:'succeeded' AND metadata['order_id']:'42') OR payment_intent:'pi_123'");
});

it('negates clauses with the minus operator', function () {
    expect(StripeSearchQuery::not(StripeSearchQuery::equals('status', 'failed')))
        ->toBe("-status:'failed'");
});

it('builds existence clauses', function () {
    expect(StripeSearchQuery::exists("metadata['order_id']"))
        ->toBe("metadata['order_id']:'*'");
});

it('supports numeric comparisons without quotes', function () {
    expect(StripeSearchQuery::lessThanOrEquals('amount', 1000))
        ->toBe('amount<=1000');
});
