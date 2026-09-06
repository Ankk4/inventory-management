<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_id')->nullable()->after('id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_id')->nullable()->after('user_id');
        });

        $this->backfillInventories();

        // Ensure no nulls remain before adding the FK (empty tables are fine).
        if (DB::table('items')->whereNull('inventory_id')->exists()
            || DB::table('receipts')->whereNull('inventory_id')->exists()) {
            throw new RuntimeException('Unable to backfill inventory_id for all items/receipts.');
        }

        Schema::table('items', function (Blueprint $table) {
            $table->foreign('inventory_id')->references('id')->on('inventories')->cascadeOnDelete();
            $table->unique(['inventory_id', 'normalized_name']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreign('inventory_id')->references('id')->on('inventories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['inventory_id', 'normalized_name']);
            $table->dropForeign(['inventory_id']);
            $table->dropColumn('inventory_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['inventory_id']);
            $table->dropColumn('inventory_id');
        });

        Schema::dropIfExists('inventories');
    }

    private function backfillInventories(): void
    {
        $users = DB::table('users')->orderBy('id')->get();

        if ($users->isEmpty()) {
            return;
        }

        $inventoryIdsByUser = [];

        foreach ($users as $user) {
            $inventoryId = DB::table('inventories')->insertGetId([
                'user_id' => $user->id,
                'name' => 'Pantry',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inventoryIdsByUser[$user->id] = $inventoryId;
        }

        $fallbackInventoryId = $inventoryIdsByUser[$users->first()->id];

        DB::table('items')
            ->whereNull('inventory_id')
            ->update(['inventory_id' => $fallbackInventoryId]);

        foreach ($inventoryIdsByUser as $userId => $inventoryId) {
            DB::table('receipts')
                ->where('user_id', $userId)
                ->whereNull('inventory_id')
                ->update(['inventory_id' => $inventoryId]);
        }

        DB::table('receipts')
            ->whereNull('inventory_id')
            ->update(['inventory_id' => $fallbackInventoryId]);
    }
};
