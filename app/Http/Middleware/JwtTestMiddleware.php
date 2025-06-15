<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtTestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Verificar si estamos en entorno de prueba
            if (app()->environment('testing')) {
                // Si estamos en pruebas y el usuario ya está autenticado, permitir el acceso
                if ($request->user()) {
                    return $next($request);
                }
            }

            // Intentar autenticar con JWT
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalid'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token absent: ' . $e->getMessage()], 401);
        } catch (\Exception $e) {
            // Log detallado del error
            \Illuminate\Support\Facades\Log::error('JWT Middleware Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Error Stack Trace: ' . $e->getTraceAsString());
            
            return response()->json(['message' => 'Authentication error: ' . $e->getMessage()], 500);
        }

        return $next($request);
    }
}
