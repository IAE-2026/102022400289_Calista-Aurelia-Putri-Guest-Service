<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guest;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class GuestController extends Controller
{
    #[OA\Get(
        path: "/{guestId}",
        summary: "Ambil profil guest",
        security: [["ApiKeyAuth" => []]],
        tags: ["Guest Service (Data Diri Tamu)"],
        parameters: [
            new OA\Parameter(name: "guestId", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function show($guestId)
    {
        $guest = Guest::find($guestId);
        if (!$guest) {
            return response()->json(['status' => 'error', 'message' => 'Guest not found'], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => $guest,
            'meta' => ['service_name' => 'Guest-Service', 'api_version' => 'v1']
        ], 200);
    }

    #[OA\Post(
        path: "/profile",
        summary: "Simpan profile",
        security: [["ApiKeyAuth" => []]],
        tags: ["Guest Service (Data Diri Tamu)"],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Aurel"),
                    new OA\Property(property: "email", type: "string", example: "aurel@example.com"),
                    new OA\Property(property: "ktp_number", type: "string", example: "1234567890123456"),
                    new OA\Property(property: "phone_number", type: "string", example: "08123456789")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function storeProfile(Request $request)
    {
        $guest = Guest::updateOrCreate(
            ['ktp_number' => $request->ktp_number],
            $request->only(['name', 'email', 'phone_number'])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Profile saved successfully',
            'data' => $guest,
            'meta' => ['service_name' => 'Guest-Service', 'api_version' => 'v1']
        ], 200);
    }

    #[OA\Post(
        path: "/validate-ktp",
        summary: "Validasi KTP",
        security: [["ApiKeyAuth" => []]],
        tags: ["Guest Service (Data Diri Tamu)"],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "ktp_number", type: "string", example: "1234567890123456")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Valid")
        ]
    )]
    public function validateKtp(Request $request)
    {
        $guest = Guest::where('ktp_number', $request->ktp_number)->first();
        if (!$guest) {
            return response()->json(['status' => 'error', 'message' => 'KTP not found'], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => ['is_valid' => true, 'name' => $guest->name],
            'meta' => ['service_name' => 'Guest-Service', 'api_version' => 'v1']
        ], 200);
    }
}