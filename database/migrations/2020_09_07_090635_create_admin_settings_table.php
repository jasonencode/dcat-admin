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
        Schema::create($this->config('database.settings_table') ?: 'admin_settings', function (Blueprint $table) {
            $table->string('slug', 100)->primary();
            $table->longText('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->config('database.settings_table') ?: 'admin_settings');
    }
};
