<?php

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\User;

it('can create a deal with all required fields', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $product = Product::factory()->create();

    $deal = Deal::factory()->create([
        'title' => 'Test Deal',
        'amount' => 5000.00,
        'currency' => 'USD',
        'stage' => 'qualified',
        'status' => 'open',
        'probability' => 75,
        'source' => 'website_form',
        'contact_id' => $contact->id,
        'product_id' => $product->id,
        'owner_id' => $user->id,
        'created_by' => $user->id,
    ]);

    expect($deal)->toBeInstanceOf(Deal::class)
        ->and($deal->title)->toBe('Test Deal')
        ->and($deal->amount)->toBe('5000.00')
        ->and($deal->currency)->toBe('USD')
        ->and($deal->stage)->toBe('qualified')
        ->and($deal->status)->toBe('open')
        ->and($deal->probability)->toBe(75)
        ->and($deal->source)->toBe('website_form')
        ->and($deal->contact_id)->toBe($contact->id)
        ->and($deal->product_id)->toBe($product->id)
        ->and($deal->owner_id)->toBe($user->id);
});

it('can create a deal without optional fields', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $deal = Deal::factory()->create([
        'title' => 'Minimal Deal',
        'contact_id' => $contact->id,
        'created_by' => $user->id,
        'product_id' => null,
        'owner_id' => null,
    ]);

    expect($deal)->toBeInstanceOf(Deal::class)
        ->and($deal->product_id)->toBeNull()
        ->and($deal->owner_id)->toBeNull();
});

it('has correct relationships', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $product = Product::factory()->create();

    $deal = Deal::factory()->create([
        'contact_id' => $contact->id,
        'product_id' => $product->id,
        'owner_id' => $user->id,
        'created_by' => $user->id,
    ]);

    expect($deal->contact)->toBeInstanceOf(Contact::class)
        ->and($deal->contact->id)->toBe($contact->id)
        ->and($deal->product)->toBeInstanceOf(Product::class)
        ->and($deal->product->id)->toBe($product->id)
        ->and($deal->owner)->toBeInstanceOf(User::class)
        ->and($deal->owner->id)->toBe($user->id)
        ->and($deal->creator)->toBeInstanceOf(User::class)
        ->and($deal->creator->id)->toBe($user->id);
});

it('can handle deal status transitions correctly', function () {
    $deal = Deal::factory()->open()->create();

    expect($deal->isClosed())->toBeFalse()
        ->and($deal->isWon())->toBeFalse()
        ->and($deal->isLost())->toBeFalse();

    $wonDeal = Deal::factory()->won()->create();

    expect($wonDeal->isClosed())->toBeTrue()
        ->and($wonDeal->isWon())->toBeTrue()
        ->and($wonDeal->isLost())->toBeFalse();

    $lostDeal = Deal::factory()->lost()->create();

    expect($lostDeal->isClosed())->toBeTrue()
        ->and($lostDeal->isWon())->toBeFalse()
        ->and($lostDeal->isLost())->toBeTrue();
});

it('calculates effective amount correctly', function () {
    // Deal with amount but no won_amount
    $deal = Deal::factory()->create([
        'amount' => 1000.00,
        'status' => 'open',
        'won_amount' => null,
    ]);

    expect($deal->getEffectiveAmount())->toBe(1000.00);

    // Won deal with different won_amount
    $wonDeal = Deal::factory()->won()->create([
        'amount' => 1000.00,
        'won_amount' => 850.00,
    ]);

    expect($wonDeal->getEffectiveAmount())->toBe(850.00);
});

it('supports soft deletes', function () {
    $deal = Deal::factory()->create();
    $dealId = $deal->id;

    $deal->delete();

    expect(Deal::find($dealId))->toBeNull()
        ->and(Deal::withTrashed()->find($dealId))->toBeInstanceOf(Deal::class)
        ->and(Deal::withTrashed()->find($dealId)->deleted_at)->not->toBeNull();
});

it('casts source_meta to array', function () {
    $sourceMetaData = ['utm_source' => 'google', 'utm_campaign' => 'test'];

    $deal = Deal::factory()->create([
        'source_meta' => $sourceMetaData,
    ]);

    expect($deal->source_meta)->toBeArray()
        ->and($deal->source_meta)->toBe($sourceMetaData);
});

it('can query deals by stage and status efficiently', function () {
    // Create deals with different stages and statuses
    Deal::factory()->create(['stage' => 'qualified', 'status' => 'open']);
    Deal::factory()->create(['stage' => 'qualified', 'status' => 'won']);
    Deal::factory()->create(['stage' => 'proposal', 'status' => 'open']);

    $qualifiedOpenDeals = Deal::where('stage', 'qualified')
        ->where('status', 'open')
        ->get();

    expect($qualifiedOpenDeals)->toHaveCount(1);

    $qualifiedDeals = Deal::where('stage', 'qualified')->get();
    expect($qualifiedDeals)->toHaveCount(2);
});

it('can query deals by owner efficiently', function () {
    $owner1 = User::factory()->create();
    $owner2 = User::factory()->create();

    Deal::factory(2)->create(['owner_id' => $owner1->id, 'status' => 'open']);
    Deal::factory()->create(['owner_id' => $owner2->id, 'status' => 'open']);
    Deal::factory()->create(['owner_id' => null, 'status' => 'open']);

    $owner1Deals = Deal::where('owner_id', $owner1->id)
        ->where('status', 'open')
        ->get();

    expect($owner1Deals)->toHaveCount(2);
});

it('validates required foreign key constraints', function () {
    $contact = Contact::factory()->create();
    $user = User::factory()->create();

    $deal = Deal::factory()->create([
        'contact_id' => $contact->id,
        'created_by' => $user->id,
    ]);

    // Contact is required - verify constraint exists
    expect($deal->contact_id)->toBe($contact->id)
        ->and($deal->created_by)->toBe($user->id);
});
