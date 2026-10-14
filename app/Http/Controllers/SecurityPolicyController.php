<?php

namespace App\Http\Controllers;

use App\Models\DatabaseSource;
use App\Models\SecurityPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityPolicyController extends Controller
{
    public function edit(DatabaseSource $database): View
    {
        $policy = $database->securityPolicy ?: SecurityPolicy::firstOrCreate(
            ['database_source_id' => $database->id],
            [
                'whitelist_tables' => [],
                'masked_columns' => [],
                'max_query_limit' => 50,
                'is_active' => true,
            ]
        );

        // Fetch live available tables and columns from the database
        $availableTables = $database->fetchTables();
        $availableColumns = $database->fetchColumns();
        $maskedColumnsJson = json_encode($policy->masked_columns ?: [
            ['column' => 'password', 'mask_type' => 'full_redact'],
            ['column' => 'email', 'mask_type' => 'partial_mask'],
        ]);

        return view('policies.edit', compact('database', 'policy', 'availableTables', 'availableColumns', 'maskedColumnsJson'));
    }

    public function update(Request $request, DatabaseSource $database): RedirectResponse
    {
        $validated = $request->validate([
            'whitelist_tables' => 'nullable|array',
            'whitelist_tables.*' => 'string',
            'masked_columns' => 'nullable|array',
            'masked_columns.*.column' => 'required|string',
            'masked_columns.*.mask_type' => 'required|in:full_redact,partial_mask',
            'max_query_limit' => 'required|integer|min:1|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $policy = $database->securityPolicy ?: new SecurityPolicy(['database_source_id' => $database->id]);

        // Clean up masked columns (filter out empty entries)
        $cleanMaskedColumns = [];
        if (!empty($validated['masked_columns'])) {
            foreach ($validated['masked_columns'] as $mc) {
                if (!empty(trim($mc['column'] ?? ''))) {
                    $cleanMaskedColumns[] = [
                        'column' => trim($mc['column']),
                        'mask_type' => $mc['mask_type'],
                    ];
                }
            }
        }

        $policy->fill([
            'whitelist_tables' => $validated['whitelist_tables'] ?? [],
            'masked_columns' => $cleanMaskedColumns,
            'max_query_limit' => $validated['max_query_limit'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $policy->save();

        return redirect()->route('policies.edit', $database)
            ->with('success', 'Security policy and data masking rules updated successfully!');
    }
}
