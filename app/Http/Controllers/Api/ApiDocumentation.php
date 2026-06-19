<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="E-PNMLS API Documentation",
 *     description="API pour le système de gestion des ressources humaines PNMLS",
 *     @OA\Contact(
 *         name="PNMLS Technical Team",
 *         url="https://e-pnmls.cd"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://e-pnmls.cd/license"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://e-pnmls.cd/api",
 *     description="Production Server"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     description="Laravel Sanctum token-based authentication",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints pour l'authentification"
 * )
 * 
 * @OA\Tag(
 *     name="Agents",
 *     description="Gestion des agents"
 * )
 * 
 * @OA\Tag(
 *     name="Holidays",
 *     description="Gestion des congés"
 * )
 * 
 * @OA\Tag(
 *     name="Documents",
 *     description="Gestion documentaire"
 * )
 * 
 * @OA\Tag(
 *     name="Tasks",
 *     description="Gestion des tâches"
 * )
 * 
 * @OA\Schema(
 *     schema="Agent",
 *     type="object",
 *     title="Agent",
 *     description="Agent model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="matricule", type="string", example="PNM-000001"),
 *     @OA\Property(property="nom", type="string", example="Kabamba"),
 *     @OA\Property(property="prenom", type="string", example="Jean"),
 *     @OA\Property(property="postnom", type="string", example="Pierre"),
 *     @OA\Property(property="sexe", type="string", enum={"M", "F"}, example="M"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.kabamba@pnmls.cd"),
 *     @OA\Property(property="telephone", type="string", example="+243815555555"),
 *     @OA\Property(property="statut", type="string", enum={"Actif", "Inactif", "Suspendu"}, example="Actif"),
 *     @OA\Property(property="department", ref="#/components/schemas/Department"),
 *     @OA\Property(property="grade", ref="#/components/schemas/Grade"),
 *     @OA\Property(property="fonction", ref="#/components/schemas/Fonction"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Holiday",
 *     type="object",
 *     title="Holiday",
 *     description="Holiday request model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="agent_id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"Congé annuel", "Congé maladie", "Congé maternité", "Permission exceptionnelle"}, example="Congé annuel"),
 *     @OA\Property(property="date_debut", type="string", format="date", example="2024-07-01"),
 *     @OA\Property(property="date_fin", type="string", format="date", example="2024-07-15"),
 *     @OA\Property(property="nombre_jours", type="integer", example=15),
 *     @OA\Property(property="motif", type="string", example="Congé annuel réglementaire"),
 *     @OA\Property(property="statut", type="string", enum={"En attente", "Approuvé", "Refusé", "Annulé"}, example="En attente"),
 *     @OA\Property(property="agent", ref="#/components/schemas/Agent"),
 *     @OA\Property(property="validated_by", type="integer", nullable=true),
 *     @OA\Property(property="validated_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Department",
 *     type="object",
 *     title="Department",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="RH"),
 *     @OA\Property(property="nom", type="string", example="Ressources Humaines"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="province_id", type="integer")
 * )
 * 
 * @OA\Schema(
 *     schema="Grade",
 *     type="object",
 *     title="Grade",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Directeur"),
 *     @OA\Property(property="niveau", type="integer", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="Fonction",
 *     type="object",
 *     title="Fonction",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Chef de Section"),
 *     @OA\Property(property="organe_id", type="integer")
 * )
 * 
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     required={"matricule", "password"},
 *     @OA\Property(property="matricule", type="string", example="PNM-000001"),
 *     @OA\Property(property="password", type="string", format="password", example="password")
 * )
 * 
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Connexion réussie"),
 *     @OA\Property(property="token", type="string", example="1|abcdef123456"),
 *     @OA\Property(property="user", ref="#/components/schemas/Agent")
 * )
 * 
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     @OA\Property(property="data", type="array", @OA\Items()),
 *     @OA\Property(property="links", type="object",
 *         @OA\Property(property="first", type="string"),
 *         @OA\Property(property="last", type="string"),
 *         @OA\Property(property="prev", type="string", nullable=true),
 *         @OA\Property(property="next", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="current_page", type="integer"),
 *         @OA\Property(property="from", type="integer"),
 *         @OA\Property(property="last_page", type="integer"),
 *         @OA\Property(property="path", type="string"),
 *         @OA\Property(property="per_page", type="integer"),
 *         @OA\Property(property="to", type="integer"),
 *         @OA\Property(property="total", type="integer")
 *     )
 * )
 */
class ApiDocumentation
{
    // This class is just for Swagger documentation
}