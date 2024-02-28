<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = ['date', 'client_message', 'seller_message', 'status', 'folder_path', 'order_id', 'feedback_id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
