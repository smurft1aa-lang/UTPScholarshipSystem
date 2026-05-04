<?php

namespace UTP;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "REST API for the UTP Scholarship & Course Eligibility System.",
    title: "UTP Scholarship System API",
    contact: new OA\Contact(name: "UTP Engineering", email: "engineering@utp.edu.my")
)]
#[OA\Server(url: "http://localhost:8080", description: "Local development")]
#[OA\Server(url: "https://scholarship.utp.edu.my", description: "Production")]
#[OA\SecurityScheme(
    securityScheme: "sessionAuth",
    type: "apiKey",
    name: "PHPSESSID",
    in: "cookie",
    description: "PHP session cookie"
)]
#[OA\Schema(
    schema: "ErrorResponse",
    type: "object",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "error", type: "string", example: "Please select a valid qualification type.")
    ]
)]
#[OA\Post(
    path: "/api/check-eligibility.php",
    summary: "Check programme eligibility",
    description: "Saves qualification and grades, runs the AI recommendation engine, creates an application record, and returns programme eligibility results.",
    tags: ["Eligibility"],
    security: [["sessionAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "application/x-www-form-urlencoded",
            schema: new OA\Schema(
                required: ["csrf_token", "qual_type", "subjects[]", "grades[]"],
                properties: [
                    new OA\Property(property: "csrf_token", description: "CSRF protection token", type: "string"),
                    new OA\Property(property: "qual_type", description: "Student's qualification type", type: "string", enum: ["SPM", "O-Level", "IGCSE"]),
                    new OA\Property(property: "subjects[]", description: "Array of subject names", type: "array", items: new OA\Items(type: "string")),
                    new OA\Property(property: "grades[]", description: "Array of letter grades", type: "array", items: new OA\Items(type: "string"))
                ]
            )
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Eligibility check completed",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean"),
                    new OA\Property(property: "redirect", type: "string", example: "/student/results.php")
                ]
            )
        ),
        new OA\Response(response: 400, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        new OA\Response(response: 403, description: "Not authenticated or email not verified"),
        new OA\Response(response: 500, description: "AI engine or database error")
    ]
)]
#[OA\Post(
    path: "/api/submit-application.php",
    summary: "Submit scholarship application",
    description: "Updates an existing application with 3 chosen programme preferences and an optional scholarship selection.",
    tags: ["Applications"],
    security: [["sessionAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "application/x-www-form-urlencoded",
            schema: new OA\Schema(
                required: ["csrf_token", "app_id", "programme_id_1", "programme_id_2", "programme_id_3"],
                properties: [
                    new OA\Property(property: "csrf_token", type: "string"),
                    new OA\Property(property: "app_id", type: "integer"),
                    new OA\Property(property: "programme_id_1", type: "integer"),
                    new OA\Property(property: "programme_id_2", type: "integer"),
                    new OA\Property(property: "programme_id_3", type: "integer"),
                    new OA\Property(property: "scholarship_id", type: "integer", nullable: true)
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Application submitted"),
        new OA\Response(response: 400, description: "Validation error"),
        new OA\Response(response: 403, description: "Not authenticated"),
        new OA\Response(response: 500, description: "Server error")
    ]
)]
#[OA\Post(
    path: "/api/logout.php",
    summary: "Logout and destroy session",
    tags: ["Authentication"],
    responses: [
        new OA\Response(response: 302, description: "Redirect to /auth/login.php")
    ]
)]
#[OA\Get(
    path: "/api/export-audit-csv.php",
    summary: "Export audit log as CSV",
    tags: ["Admin"],
    security: [["sessionAuth" => []]],
    parameters: [
        new OA\Parameter(name: "date_from", in: "query", schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "date_to", in: "query", schema: new OA\Schema(type: "string", format: "date"))
    ],
    responses: [
        new OA\Response(response: 200, description: "CSV file download"),
        new OA\Response(response: 400, description: "Invalid date format"),
        new OA\Response(response: 403, description: "Admin role required")
    ]
)]
class OpenApiSpec
{
}
