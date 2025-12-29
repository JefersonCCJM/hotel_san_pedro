<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class AuditLogManager extends Component
{
    use WithPagination;

    public $event = '';
    public $user_id = '';
    public $users = [];
    public $eventOptions = [];

    protected $queryString = [
        'event' => ['except' => ''],
        'user_id' => ['except' => ''],
    ];

    public function mount()
    {
        if (!Auth::user()->can('manage_roles')) {
            abort(403);
        }
        $this->users = User::all();

        // Only show events that actually exist in the database, so the filter always "works".
        $this->eventOptions = AuditLog::query()
            ->select('event')
            ->whereNotNull('event')
            ->where('event', '!=', '')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->values()
            ->toArray();
    }

    public function updatedEvent()
    {
        $this->resetPage();
    }

    public function updatedUserId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::with('user')->latest();

        if ($this->event) {
            $query->where('event', $this->event);
        }

        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        }

        $logs = $query->paginate(20);

        return view('livewire.audit-log-manager', [
            'logs' => $logs,
            'eventOptions' => $this->eventOptions,
        ])->extends('layouts.app')->section('content');
    }
}

