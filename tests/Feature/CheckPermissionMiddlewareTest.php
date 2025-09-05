<?php

use App\Http\Middleware\CheckPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('middleware allows access when user has permission', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'test_permission']);
    $user->givePermissionTo($permission);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new CheckPermission;
    $response = $middleware->handle($request, fn () => new Response('OK'), 'test_permission');

    expect($response->getContent())->toBe('OK');
});

test('middleware denies access when user lacks permission', function () {
    $user = User::factory()->create();

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new CheckPermission;

    expect(fn () => $middleware->handle($request, fn () => new Response('OK'), 'test_permission'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'You do not have permission to access this resource.');
});

test('middleware denies access for unauthenticated user', function () {
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => null);

    $middleware = new CheckPermission;

    expect(fn () => $middleware->handle($request, fn () => new Response('OK'), 'test_permission'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Unauthenticated.');
});
