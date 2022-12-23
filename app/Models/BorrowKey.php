<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowKey extends Model
{
    use HasFactory;

    protected $table = 'borrow_key';

    /**
     * @var <string>
     */
    protected $fillable = [
        'received',
        'borrow_id',
        'key_id',
    ];

    /**
     * @return BelongsTo
     */
    public function borrow(): BelongsTo
    {
        return $this->belongsTo(Borrow::class);
    }

    /**
     * @return BelongsTo
     */
    public function key(): BelongsTo
    {
        return $this->belongsTo(Key::class);
    }
}
