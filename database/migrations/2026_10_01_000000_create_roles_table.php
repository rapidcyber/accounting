<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds roles and a role_user pivot so users can be given roles.
 *
 * Only users with the built-in "admin" role can manage users and roles.
 * Until now that was hard-coded to user #1, so the admin role is given to
 * user #1 here (or the first user, if #1 no longer exists) to keep the
 * same person in charge after deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });

        $now = now();

        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'description' => 'Can manage users and roles',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $firstAdminId = DB::table('users')->where('id', 1)->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($firstAdminId) {
            DB::table('role_user')->insert([
                'role_id' => $adminRoleId,
                'user_id' => $firstAdminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
