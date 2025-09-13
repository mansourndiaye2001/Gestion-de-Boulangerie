<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        // Ici, on retourne true car on permet à tout le monde de soumettre cette demande
        return true;
    }

    /**
     * Règles de validation pour la requête.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255', 
            'prenom' => 'required|string|max:255', 
            'email' => 'required|email|unique:users,email',  
            'password' => 'required|string|min:6',  
            'role' => 'required|in:admin,employe,client',  
            'telephone' => 'nullable|string|max:15', 
            
        ];
    }
}
