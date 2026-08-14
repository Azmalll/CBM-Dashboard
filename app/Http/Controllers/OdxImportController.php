<?php

namespace App\Http\Controllers;

use App\Services\OdxImportService;
use Illuminate\Http\Request;

class OdxImportController extends Controller
{
    public function create()
    {
        return view('odx-import.create');
    }

    public function store(Request $request, OdxImportService $odxImportService)
    {
        $request->validate([
            'odx_file' => [
                'required',
                'file',
                'max:51200',
            ],
        ]);

        $result = $odxImportService->import(
            $request->file('odx_file')
        );

        return redirect()
            ->route('odx-import.create')
            ->with('success', $result['message']);
    }
}