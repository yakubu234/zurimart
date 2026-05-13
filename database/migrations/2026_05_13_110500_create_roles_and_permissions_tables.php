<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role_code')->nullable()->after('role');
            $table->foreignId('role_id')->nullable()->after('role_code')->constrained('roles')->nullOnDelete();
        });

        $now = now();

        $permissions = collect(config('access.permissions', []))
            ->map(function (array $permission) use ($now) {
                return [
                    'slug' => $permission['slug'],
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'description' => $permission['description'] ?? null,
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        DB::table('permissions')->insert($permissions);

        $permissionIds = DB::table('permissions')->pluck('id', 'slug');

        foreach (config('access.roles', []) as $role) {
            $roleId = DB::table('roles')->insertGetId([
                'slug' => $role['slug'],
                'name' => $role['name'],
                'description' => $role['description'] ?? null,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $pivotRows = collect($role['permissions'] ?? [])
                ->map(fn (string $slug) => $permissionIds[$slug] ?? null)
                ->filter()
                ->map(fn ($permissionId) => [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all();

            if (! empty($pivotRows)) {
                DB::table('permission_role')->insert($pivotRows);
            }
        }

        $roleIds = DB::table('roles')->pluck('id', 'slug');

        DB::table('users')
            ->whereNotNull('role')
            ->orderBy('id')
            ->get(['id', 'role'])
            ->each(function (object $user) use ($roleIds) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'role_code' => $user->role,
                        'role_id' => $roleIds[$user->role] ?? null,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('role_code');
        });

        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
