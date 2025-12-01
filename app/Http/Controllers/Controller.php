<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="E-Commerce API",
 *     description="E-Commerce Backend API Documentation - DDD Architecture with Laravel 12",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API V1 Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Health",
 *     description="Health check endpoints"
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;
    use ApiResponseTrait;
}
