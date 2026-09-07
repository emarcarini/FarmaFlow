<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\BotRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BotRuleController extends Controller
{
    /**
     * Listar todas as regras do bot.
     */
    public function index(): JsonResponse
    {
        $rules = BotRule::orderBy('priority', 'asc')->orderBy('id', 'desc')->get();
        return response()->json($rules);
    }

    /**
     * Adicionar uma nova regra para o bot.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'title' => ['nullable', 'string', 'max:100'],
        ]);

        $rule = BotRule::create([
            'title' => $request->input('title'),
            'content' => trim($request->input('content')),
            'is_active' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'rule' => $rule]);
        }

        return back()->with('success', 'Regra adicionada com sucesso ao assistente!');
    }

    /**
     * Alternar ativação de uma regra.
     */
    public function toggle(BotRule $botRule): JsonResponse|RedirectResponse
    {
        $botRule->update(['is_active' => !$botRule->is_active]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $botRule->is_active]);
        }

        return back()->with('success', 'Status da regra atualizado!');
    }

    /**
     * Excluir uma regra.
     */
    public function destroy(BotRule $botRule): JsonResponse|RedirectResponse
    {
        $botRule->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Regra removida com sucesso!');
    }
}
