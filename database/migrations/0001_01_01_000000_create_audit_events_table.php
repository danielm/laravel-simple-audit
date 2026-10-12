<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('simple-audit.table', 'audit_events');
        $connection = config('simple-audit.connection');

        Schema::connection($connection)->create($table, function (Blueprint $t) {
            // ULIDs are time-sortable and avoid a hot auto-increment index
            // under high write volume.
            $t->ulid('id')->primary();

            // Free-form string, not an enum column: the event "types" are
            // defined by the consuming app (see examples/), this package
            // doesn't know about them.
            $t->string('type', 64);

            // who did it
            $t->string('actor_type')->nullable();
            $t->string('actor_id')->nullable();
            $t->index(['actor_type', 'actor_id']);

            // what it happened to
            $t->string('subject_type')->nullable();
            $t->string('subject_id')->nullable();
            $t->index(['subject_type', 'subject_id']);

            // related entity
            $t->string('reference_type')->nullable();
            $t->string('reference_id')->nullable();
            $t->index(['reference_type', 'reference_id']);

            $t->json('metadata')->nullable();  // NEVER store secrets/tokens here
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->string('request_id', 64)->nullable();

            $t->timestamp('occurred_at', 6)->index();

            $t->index(['type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        $connection = config('simple-audit.connection');
        $table = config('simple-audit.table', 'audit_events');

        Schema::connection($connection)->dropIfExists($table);
    }
};
