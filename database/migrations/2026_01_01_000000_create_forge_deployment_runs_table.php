<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forge_deployment_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('actor_email')->nullable();
            $table->string('source', 32)->default('dashboard');
            $table->unsignedBigInteger('forge_deployment_id')->nullable();
            $table->unsignedBigInteger('forge_server_id');
            $table->unsignedBigInteger('forge_site_id');
            $table->string('status', 32);
            $table->string('deployment_type', 64)->nullable();
            $table->string('commit_hash', 128)->nullable();
            $table->string('commit_author')->nullable();
            $table->text('commit_message')->nullable();
            $table->string('branch')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->timestamps();

            $table->unique(
                ['forge_server_id', 'forge_site_id', 'forge_deployment_id'],
                'forge_deployment_runs_remote_unique',
            );
            $table->index(['forge_site_id', 'requested_at']);
            $table->index(['forge_site_id', 'status']);
            $table->index(['actor_type', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forge_deployment_runs');
    }
};
