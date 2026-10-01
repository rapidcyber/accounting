<?php

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RoleResource\Pages\ManageRoles;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\Role;
use App\Models\User;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // The migration creates the admin role.
    $this->adminRole = Role::admin();
    $this->admin = User::factory()->create();
    $this->admin->roles()->attach($this->adminRole);
    $this->member = User::factory()->create();
});

test('migration creates the admin role', function () {
    expect($this->adminRole)->not->toBeNull()
        ->and($this->admin->fresh()->isAdmin())->toBeTrue()
        ->and($this->member->isAdmin())->toBeFalse();
});

test('only admins can open user and role management', function () {
    $this->actingAs($this->member);
    expect(UserResource::canAccess())->toBeFalse()
        ->and(RoleResource::canAccess())->toBeFalse();
    Livewire::test(ManageUsers::class)->assertForbidden();
    Livewire::test(ManageRoles::class)->assertForbidden();

    $this->actingAs($this->admin);
    expect(UserResource::canAccess())->toBeTrue()
        ->and(RoleResource::canAccess())->toBeTrue();
    Livewire::test(ManageUsers::class)->assertOk()->assertCanSeeTableRecords([$this->admin, $this->member]);
    Livewire::test(ManageRoles::class)->assertOk()->assertCanSeeTableRecords([$this->adminRole]);
});

test('admin can add a role', function () {
    $this->actingAs($this->admin);

    Livewire::test(ManageRoles::class)
        ->callAction('create', ['name' => 'accountant', 'description' => 'Handles vouchers'])
        ->assertHasNoActionErrors();

    expect(Role::where('name', 'accountant')->exists())->toBeTrue();
});

test('admin can add a user with roles', function () {
    $accountant = Role::create(['name' => 'accountant']);
    $this->actingAs($this->admin);

    Livewire::test(ManageUsers::class)
        ->callAction('create', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'roles' => [$accountant->id],
        ])
        ->assertHasNoActionErrors();

    $jane = User::where('email', 'jane@example.com')->first();
    expect($jane)->not->toBeNull()
        ->and($jane->hasRole('accountant'))->toBeTrue()
        ->and(Hash::check('secret123', $jane->password))->toBeTrue();
});

test('editing a user without a password keeps the old password', function () {
    $this->actingAs($this->admin);
    $old = $this->member->password;

    Livewire::test(ManageUsers::class)
        ->callTableAction(EditAction::class, $this->member, ['name' => 'Renamed', 'password' => ''])
        ->assertHasNoTableActionErrors();

    expect($this->member->fresh()->name)->toBe('Renamed')
        ->and($this->member->fresh()->password)->toBe($old);
});

test('admin cannot remove their own admin role or delete themselves', function () {
    $this->actingAs($this->admin);

    Livewire::test(ManageUsers::class)
        ->callTableAction(EditAction::class, $this->admin, ['roles' => []])
        ->assertHasTableActionErrors(['roles']);

    Livewire::test(ManageUsers::class)
        ->assertTableActionHidden(DeleteAction::class, $this->admin)
        ->assertTableActionVisible(DeleteAction::class, $this->member);

    expect($this->admin->fresh()->isAdmin())->toBeTrue();
});

test('the admin role cannot be deleted or renamed', function () {
    $this->actingAs($this->admin);

    Livewire::test(ManageRoles::class)
        ->assertTableActionHidden(DeleteAction::class, $this->adminRole);

    expect($this->adminRole->delete())->toBeFalse();
    expect($this->adminRole->update(['name' => 'boss']))->toBeFalse();
    expect(Role::admin())->not->toBeNull();
});
