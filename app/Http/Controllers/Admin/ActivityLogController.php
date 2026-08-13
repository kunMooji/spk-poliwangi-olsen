<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Jejak perubahan data master.
 *
 * Hanya dibaca — tidak ada tombol ubah maupun hapus. Catatan yang dapat
 * disunting tidak lagi berguna sebagai bukti telusur.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->input('subject'), fn ($query, $subject) => $query->where('subject_type', $subject))
            ->when($request->input('action'), fn ($query, $action) => $query->where('action', $action))
            ->when($request->input('user'), fn ($query, $user) => $query->where('user_id', $user))
            ->when($request->input('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->input('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latestFirst()
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'subjects' => ActivityLog::SUBJECT_LABELS,
            'actions' => ActivityLog::ACTION_LABELS,
            'admins' => User::query()->admin()->orderBy('name')->get(),
            'total' => ActivityLog::query()->count(),
        ]);
    }
}
