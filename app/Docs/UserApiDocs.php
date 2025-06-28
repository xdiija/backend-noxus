<?php

namespace App\Docs;

/**
 * @OA\Get(
 *     path="/api/users",
 *     tags={"Users"},
 *     summary="Get a paginated list of users with optional filters",
 *     description="Retrieve a list of users with pagination and filtering options. Only accessible to authorized userssss.",
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of users per page (default: 10)",
 *         required=false,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Parameter(
 *         name="filter",
 *         in="query",
 *         description="Filter by user name",
 *         required=false,
 *         @OA\Schema(type="string", example="Djalma")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Djalma Leandro"),
 *                 @OA\Property(property="email", type="string", example="djalma@example.com"),
 *                 @OA\Property(property="status", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Ativo")
 *                 ),
 *                 @OA\Property(property="roles", type="array", @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Desenvolvimento")
 *                 )),
 *                 @OA\Property(property="last_login", type="string", format="date-time", example=null),
 *                 @OA\Property(property="created_at", type="string", format="date", example="14/04/2025"),
 *                 @OA\Property(property="updated_at", type="string", format="date", example="14/04/2025")
 *             )),
 *             @OA\Property(property="links", type="object",
 *                 @OA\Property(property="first", type="string", example="http://localhost:8989/api/users?page=1"),
 *                 @OA\Property(property="last", type="string", example="http://localhost:8989/api/users?page=1"),
 *                 @OA\Property(property="prev", type="string", example=null),
 *                 @OA\Property(property="next", type="string", example=null)
 *             ),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="from", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=1),
 *                 @OA\Property(property="path", type="string", example="http://localhost:8989/api/users"),
 *                 @OA\Property(property="per_page", type="integer", example=5),
 *                 @OA\Property(property="to", type="integer", example=3),
 *                 @OA\Property(property="total", type="integer", example=3)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     )
 * )
 */
class UserApiDocs {}
