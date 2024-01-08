<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function getConnection()
    {
        return $this->config('database.connection') ?: config('database.default');
    }

    public function config($key)
    {
        return config('admin.'.$key);
    }

    public function up(): void
    {
        Schema::create($this->config('database.operation_logs') ?: 'admin_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')
                ->index();
            $table->string('path');
            $table->string('method', 16)
                ->index();
            $table->string('ip', 16);
            $table->text('input')
                ->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
