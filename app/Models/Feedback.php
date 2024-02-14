<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = ['date', 'client_message', 'seller_message', 'status', 'folder_path', 'order_id'];

    // Relation : Un retour appartient à une commande
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
