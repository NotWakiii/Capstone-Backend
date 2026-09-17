<?php

namespace App\Http\Controllers;

use App\Models\Strand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StrandController extends Controller
{
    public function index()
    {
        $strands = Strand::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $strands,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:strands,name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $strand = Strand::create([
            'name' => strtoupper(
                trim($validated['name'])
            ),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Strand created successfully.',
            'data' => $strand,
        ], 201);
    }

    public function show($id)
    {
        $strand = Strand::find($id);

        if (!$strand) {
            return response()->json([
                'success' => false,
                'message' => 'Strand not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $strand,
        ]);
    }

    public function update(Request $request, $id)
    {
        $strand = Strand::find($id);

        if (!$strand) {
            return response()->json([
                'success' => false,
                'message' => 'Strand not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'strands',
                    'name'
                )->ignore($strand->id),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $strand->update([
            'name' => strtoupper(
                trim($validated['name'])
            ),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Strand updated successfully.',
            'data' => $strand->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $strand = Strand::find($id);

        if (!$strand) {
            return response()->json([
                'success' => false,
                'message' => 'Strand not found.',
            ], 404);
        }

        $name = $strand->name;

        $strand->delete();

        return response()->json([
            'success' => true,
            'message' => "Strand {$name} deleted successfully.",
        ]);
    }
}
