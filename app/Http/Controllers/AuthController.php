<?php

// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Gère l'inscription des utilisateurs
    public function register(Request $request)
    {
        // Valider les données de la requête
        $validatedData = $request->validate([
            'artist_name' => 'required|string|max:255',
            'name' => 'string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:24',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        // Créer un nouvel utilisateur et hacher le mot de passe
        $user = User::create([
            'user_id' => bin2hex(random_bytes(20)),
            'artist_name' => $validatedData['artist_name'],
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // Générer un token pour l'utilisateur
        $token = $user->createToken('auth_token')->plainTextToken;

        // Renvoyer une réponse avec le token utilisateur
        return response()->json(['access_token' => $token, 'token_type' => 'Bearer', ]);
    }

    // Gère la connexion des utilisateurs
    public function login(Request $request)
    {
        // Valider les données de la requête
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        // Vérifier les informations d'identification
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Mot de passe ou identifiant incorrect.'], 401);
        }

        // Récupérer l'utilisateur
        $user = User::where('email', $request['email'])->firstOrFail();

        // Générer un token pour l'utilisateur
        $token = $user->createToken('auth_token')->plainTextToken;

        // Renvoyer une réponse avec le token utilisateur
        return response()->json(['access_token' => $token, 'token_type' => 'Bearer', ]);
    }

    // Gère la déconnexion des utilisateurs
    public function logout(Request $request)
    {
        // Révoquer tous les tokens de l'utilisateur
        $request->user()->tokens()->delete();

        // Renvoyer une réponse de déconnexion réussie
        return response()->json(['message' => 'Vous avez bien été déconnecté.']);
    }
}
