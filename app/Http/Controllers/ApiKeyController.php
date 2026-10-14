<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\DatabaseSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function index(): View
    {
        $apiKeys = ApiKey::with('databaseSource')
            ->withCount('auditLogs')
            ->latest()
            ->get();

        $databases = DatabaseSource::orderBy('name')->get();

        $mcpGatewaySseUrl = env('MCP_GATEWAY_URL', 'http://localhost:3000') . '/sse';

        return view('api-keys.index', compact('apiKeys', 'databases', 'mcpGatewaySseUrl'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'database_source_id' => 'required|exists:database_sources,id',
        ]);

        $generated = ApiKey::generate($validated['name'], $validated['database_source_id']);

        return redirect()->route('api-keys.index')
            ->with('new_api_key', [
                'name' => $generated['model']->name,
                'raw_token' => $generated['raw_token'],
                'masked_key' => $generated['model']->masked_key,
                'database' => $generated['model']->databaseSource->name,
            ])
            ->with('success', 'API Key successfully created! Please copy the secret key now as it will not be shown again.');
    }

    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        $name = $apiKey->name;
        $apiKey->delete();

        return redirect()->route('api-keys.index')
            ->with('success', "API Key '{$name}' has been revoked.");
    }
}
