<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'date', 'project_type', 'file_type', 'deadline', 'status', 'init_folder_path', 'user_id'];

    // Relation : Une commande appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation : Une commande peut avoir plusieurs retours
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }
}
