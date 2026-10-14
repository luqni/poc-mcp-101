<?php

namespace App\Http\Controllers;

use App\Models\DatabaseSource;
use App\Models\SecurityPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatabaseSourceController extends Controller
{
    public function index(): View
    {
        $databases = DatabaseSource::with(['securityPolicy'])
            ->withCount(['apiKeys', 'auditLogs'])
            ->latest()
            ->get();

        return view('databases.index', compact('databases'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'ssl' => 'nullable|boolean',
        ]);

        $validated['ssl'] = $request->boolean('ssl');
        $validated['db_type'] = 'pgsql';
        $validated['status'] = 'unknown';

        $dbSource = DatabaseSource::create($validated);

        // Run initial connection test
        $testResult = $dbSource->testConnection();

        // Create default policy for this database source
        $fetchedTables = $dbSource->fetchTables();
        SecurityPolicy::create([
            'database_source_id' => $dbSource->id,
            'whitelist_tables' => !empty($fetchedTables) ? array_values(array_slice($fetchedTables, 0, 5)) : [],
            'masked_columns' => [
                ['column' => 'password', 'mask_type' => 'full_redact'],
                ['column' => 'email', 'mask_type' => 'partial_mask'],
            ],
            'max_query_limit' => 50,
            'is_active' => true,
        ]);

        if ($testResult['success']) {
            return redirect()->route('databases.index')
                ->with('success', "Database source '{$dbSource->name}' added and connected successfully!");
        } else {
            return redirect()->route('databases.index')
                ->with('error', "Database source '{$dbSource->name}' added, but connection test failed: {$testResult['message']}");
        }
    }

    public function testConnection(Request $request, DatabaseSource $database): JsonResponse|RedirectResponse
    {
        $result = $database->testConnection();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'status' => $database->status,
                'last_tested_at' => $database->last_tested_at?->diffForHumans() ?? 'Just now',
            ]);
        }

        if ($result['success']) {
            return back()->with('success', "Connection test passed: {$result['message']}");
        }

        return back()->with('error', "Connection test failed: {$result['message']}");
    }

    public function destroy(DatabaseSource $database): RedirectResponse
    {
        $name = $database->name;
        $database->delete();

        return redirect()->route('databases.index')
            ->with('success', "Database '{$name}' has been deleted.");
    }
}
