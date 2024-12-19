<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_at',
    ];

    /**
     * Define the relationship with the User model.
     * An order belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
