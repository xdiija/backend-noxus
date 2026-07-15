<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditLog\IndexAuditLogsRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function index(IndexAuditLogsRequest $request)
    {
        $response = AuditLogResource::collection(
            $this->auditLogService->list(
                $request->input('per_page'),
                $request->input('filter'),
                $request->input('entity'),
                $request->input('entity_id') !== null ? (int) $request->input('entity_id') : null,
                $request->input('action'),
            )
        )->response();

        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response;
    }
}
