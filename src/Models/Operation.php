<?php

namespace Dcat\Admin\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Operation extends Model
{
    use HasDateTimeFormatter;

    const UPDATED_AT = null;

    public static array $methods = [
        'POST', 'PUT', 'DELETE',
    ];

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        $this->init();
        parent::__construct($attributes);
    }

    protected function init(): void
    {
        $connection = config('admin.database.connection') ?: config('database.default');
        $this->setConnection($connection);
        $this->setTable(config('admin.database.operation_logs'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('admin.database.users_model'));
    }
}