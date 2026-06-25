<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guest;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use App\Services\SoapLoggingService;
use App\Services\RabbitMqPublisherService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuestController extends Controller
{
    protected SoapLoggingService $soapLoggingService;
    protected RabbitMqPublisherService $rabbitMqPublisherService;

    public function __construct(SoapLoggingService $soapLoggingService, RabbitMqPublisherService $rabbitMqPublisherService)
    {
        $this->soapLoggingService = $soapLoggingService;
        $this->rabbitMqPublisherService = $rabbitMqPublisherService;
    }

    /**
     * Collection: GET /api/v1/guests
     * Mengambil daftar semua guest.
     */
    #[OA\Get(
        path: "/guests",
        summary: "Ambil daftar semua guest",
        security: [["ApiKeyAuth" => []]],
        tags: ["Guest Service (Data Diri Tamu)"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent()
            )
        ]
    )]
    public function index()
    {
        $guests = Guest::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $guests,
            'meta' => [
                'service_name' => env('SERVICE_NAME', 'Guest-Service'),
                'api_version' => env('API_VERSION', 'v1')
            ]
        ], 200);
    }

    /**
     * Resource: GET /api/v1/guests/{id}
     * Mengambil data guest spesifik berdasarkan ID.
     */
    #[OA\Get(
        path: "/guests/{id}",
        summary: "Ambil profil guest berdasarkan ID",
        security: [["ApiKeyAuth" => []]],
        tags: ["Guest Service (Data Diri Tamu)"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent()
            ),
            new OA\Response(
                response: 404,
                description: "Not Found",
                content: new OA\JsonContent()
            )
        ]
    )]
    public function show($id)
    {
        $guest = Guest::find($id);
        if (!$guest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Guest not found',
                'errors' => null
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $guest,
            'meta' => [
                'service_name' => env('SERVICE_NAME', 'Guest-Service'),
                'api_version' => env('API_VERSION', 'v1')
            ]
        ], 200);
    }

    /**
     * Action: POST /api/v1/guests
     * Menambah data guest baru.
     */
    #[OA\Post(
        path: "/guests",
        summary: "Tambah data guest baru",
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
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent()
            ),
            new OA\Response(
                response: 422,
                description: "Validation Error",
                content: new OA\JsonContent()
            )
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'ktp_number' => 'required|string|max:20',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Save / Update local guest profile
            $guest = Guest::updateOrCreate(
                ['ktp_number' => $request->ktp_number],
                $request->only(['name', 'email', 'phone_number'])
            );

            // 2. Perform legacy SOAP Audit log (Orchestration step 1)
            try {
                $receiptNumber = $this->soapLoggingService->sendSoapAudit('StoreGuest', [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'email' => $guest->email,
                    'ktp_number' => $guest->ktp_number,
                    'phone_number' => $guest->phone_number,
                    'timestamp' => now()->toIso8601String(),
                ]);

                // Save Receipt Number to local DB
                $guest->receipt_number = $receiptNumber;
                $guest->save();
            } catch (\Exception $e) {
                Log::warning("SOAP Audit failed (non-critical): " . $e->getMessage());
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create guest: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create guest: ' . $e->getMessage(),
                'errors' => null
            ], 500);
        }

        // 3. Broadcast Event to RabbitMQ (non-critical)
        try {
            $this->rabbitMqPublisherService->publishRabbitMessage('guest.created', [
                'event' => 'guest.created',
                'timestamp' => now()->toIso8601String(),
                'team_id' => env('CENTRAL_TEAM_ID', 'TEAM-11'),
                'data' => [
                    'guest_id' => $guest->id,
                    'name' => $guest->name,
                    'email' => $guest->email,
                    'ktp_number' => $guest->ktp_number,
                    'phone_number' => $guest->phone_number,
                    'receipt_number' => $guest->receipt_number,
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to broadcast message to RabbitMQ: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Guest created successfully',
            'data' => $guest,
            'meta' => [
                'service_name' => env('SERVICE_NAME', 'Guest-Service'),
                'api_version' => env('API_VERSION', 'v1'),
            ]
        ], 201);
    }
}