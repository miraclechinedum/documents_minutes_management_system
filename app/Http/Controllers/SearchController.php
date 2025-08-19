<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Minute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // 'all', 'documents', 'minutes'
        
        $documents = collect();
        $minutes = collect();

        if (!empty($query)) {
            if ($type === 'all' || $type === 'documents') {
                $documents = $this->searchDocuments($query);
            }
            
            if ($type === 'all' || $type === 'minutes') {
                $minutes = $this->searchMinutes($query);
            }
        }

        return view('search.index', compact('query', 'type', 'documents', 'minutes'));
    }

    private function searchDocuments(string $query)
    {
        $searchResults = Document::search($query)->get();
        
        // Filter results based on user permissions
        return $searchResults->filter(function ($document) {
            return Auth::user()->canViewDocument($document);
        });
    }

    private function searchMinutes(string $query)
    {
        $searchResults = Minute::search($query)->get();
        
        // Filter results based on user permissions
        return $searchResults->filter(function ($minute) {
            return $minute->canViewBy(Auth::user());
        });
    }

    public function api(Request $request)
    {
        $query = $request->get('q', '');
        $limit = min($request->get('limit', 10), 50);
        
        if (empty($query)) {
            return response()->json([
                'documents' => [],
                'minutes' => [],
            ]);
        }

        $documents = $this->searchDocuments($query)->take($limit);
        $minutes = $this->searchMinutes($query)->take($limit);

        return response()->json([
            'documents' => $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'reference_number' => $document->reference_number,
                    'url' => route('documents.show', $document),
                    'created_at' => $document->created_at->format('M j, Y'),
                ];
            }),
            'minutes' => $minutes->map(function ($minute) {
                return [
                    'id' => $minute->id,
                    'body' => substr($minute->body, 0, 100),
                    'document_title' => $minute->document->title,
                    'url' => route('documents.show', $minute->document) . '#minute-' . $minute->id,
                    'created_at' => $minute->created_at->format('M j, Y'),
                ];
            }),
        ]);
    }
}