<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'date', 'project_type', 'file_type', 'support', 'deadline', 'status', 'user_id', 'order_id'];

    // Relation : Une commande appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation : Une commande peut avoir plusieurs retours
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'order_id', 'order_id');
    }
}
