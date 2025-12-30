<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use \Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthenticatedException;


abstract class Controller
{

    /**
     * Devuelve una respuesta JSON estandarizada para operaciones exitosas.
     *
     * @param int $statusCode Código de estado HTTP (ej. 200, 201).
     * @param string $message Mensaje descriptivo del éxito.
     * @param array|null $data Datos adicionales que se enviarán en la respuesta.
     * @param array|null $headers Cabeceras HTTP adicionales.
     * @param array|null $cookies Array de objetos de cookie o parámetros de cookie.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    protected function success(int $statusCode, string $message = "", ?array $data = null, ?array $headers = null, ?array $cookies = null): JsonResponse|Response
    {
        $output = [];
        $output["status"] = "success";
        $output["message"] = $message;
        if (is_array($data))
            $output["data"] = $data;

        if ($statusCode == 204) {
            return response()->noContent();
        }

        $response = response()->json($output, $statusCode);
        if (is_array($headers) && !empty($headers))
            $response->headers->add($headers);
        if (is_array($cookies) && !empty($cookies))
            $response->withCookies($cookies);

        return $response;
    }

    /**
     * Devuelve una respuesta JSON estandarizada para errores o fallos.
     *
     * @param int $statusCode Código de estado HTTP de error (ej. 400, 401, 404, 422).
     * @param string $message Mensaje descriptivo del error.
     * @param array|null $errors Detalle de los errores (ej. errores de validación).
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(int $statusCode, string $message, ?array $errors): JsonResponse
    {
        $output = [];
        $output["status"] = "error";
        $output["message"] = $message;
        if (is_array($errors) && !empty($errors))
            $output["errors"] = $errors;
        $response =  response()->json($output, $statusCode);
        return $response;
    }

    /**
     * Maneja excepciones de servidor de forma centralizada.
     *
     * @param \Throwable $th La excepción capturada.
     * @param string $message Mensaje base para el usuario.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function serverError(\Throwable $th, string $message = "Error creating user"): JsonResponse
    {

        $this->writeLogError($th, $message);

        $error = ["message" => $th->getMessage()];
        if (config('app.debug') && env('APP_ENV') === 'development') {
            $error["line"] = $th->getLine();
            $error["file"] = $th->getFile();
        }

        // Determinar prefijo
        $prefix = "GE"; // General Exception por defecto
        if ($th instanceof \PDOException || $th instanceof QueryException) {
            $prefix = "DE"; // Database Exception
        }

        $code = $prefix . date('YmdHis');

        $userMessage = $message . ", please notify the administrator the following error: " . $code;

        return $this->error(500, $userMessage, $error);
    }

    protected function clientError(\Throwable $e, string $message = "Error creating user"): JsonResponse
    {
        $this->writeLogError($e, $message);

        $error = ["message" => $e->getMessage()];
        if (config('app.debug') && env('APP_ENV') === 'development') {
            $error["line"] = $e->getLine();
            $error["file"] = $e->getFile();
        }

        // Determinar prefijo
        $prefix = "CL"; // Client Exception por defecto
        if ($e instanceof \PDOException || $e instanceof QueryException) {
            $prefix = "DE"; // Database Exception
        }

        $code = $prefix . date('YmdHis');
        $statusCode = $e instanceof NotFoundHttpException ? 404 : 422;
        if ($e instanceof UnauthenticatedException)
            $statusCode = 401;

        $userMessage = $message . ", please notify the administrator the following error: " . $code;

        return $this->error($statusCode, $userMessage, $error);
    }

    protected function writeLogError(\Throwable $th, string $message = "Error creating user")
    {
        // Filtrar el stack trace para mostrar solo archivos de la aplicación (excluyendo vendor)
        $appTrace = collect($th->getTrace())
            ->filter(function ($frame) {
                return isset($frame['file']) && !str_contains($frame['file'], 'vendor');
            })
            ->map(function ($frame) {
                return [
                    'file' => $frame['file'],
                    'line' => $frame['line'] ?? '?',
                    'class' => $frame['class'] ?? '?',
                    'function' => $frame['function'] ?? '?',
                ];
            })
            ->values();

        // Loguear el error con detalles estructurados
        Log::error($message, [
            'message' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'app_trace' => $appTrace
        ]);
    }
}
