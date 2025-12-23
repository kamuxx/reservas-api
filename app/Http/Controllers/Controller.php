<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

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
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success(int $statusCode, string $message, ?array $data = null, ?array $headers = null, ?array $cookies = null): JsonResponse{
        $output = [];
        $output["status"] = "success";
        $output["message"] = $message;
        if(is_array($data) && !empty($data))
            $output["data"] = $data;

        $response = response()->json($output,$statusCode);
        if(is_array($headers) && !empty($headers))
            $response->headers->add($headers);
        if(is_array($cookies) && !empty($cookies))
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
    protected function error(int $statusCode, string $message, ?array $errors):JsonResponse{
        $output = [];
        $output["message"] = $message;
        if(is_array($errors) && !empty($errors))
            $output["errors"] = $errors;
        $response =  response()->json($output,$statusCode);
        return $response;
    }
}
