<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketAddRequest;
use App\Models\Tickets\File;
use App\Models\Tickets\Message;
use App\Models\Tickets\Ticket;
use App\Role;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class TicketController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');

        // check user can delete ticket message
        $this->middleware(function ($request, $next) {
            if (Auth::check() && Auth::user()->hasRoles([Role::Admin, Role::SecurityService, Role::SeniorModerator])) {
                View::share('canDelete', true);
            }

            return $next($request);
        });

        View::share('page', 'tickets');
    }

    // список тикетов
    public function index(Request $request)
    {
        $user = Auth::user();
        $tickets = Ticket::owned($user->id)
            ->applySearchFilters($request)
            ->orderBy('last_message_at', 'DESC')
            ->paginate(20);

        return view('tickets.index', ['tickets' => $tickets]);
    }

    // форма создания тикета
    public function add_view(Request $request)
    {
        if(!$request->user()->can('create-ticket')) {
            abort(403);
        }

        return view('tickets.add');
    }

    // логика добавления тикета
    public function add(TicketAddRequest $request)
    {
        if(!$request->user()->can('create-ticket')) {
            abort(403);
        }

        // тикет
        $ticket_data = $request->only(['title', 'category']);
        $ticket_data['user_id'] = $request->user()->id;
        $ticket_data['created_at'] = Carbon::now();
        $ticket_data['updated_at'] = null;
        $ticket_data['last_message_at'] = Carbon::now();

        if ($ticket = Ticket::create($ticket_data)) {
            // сообщение тикета
            $message = Message::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'text' => $request->get('message'),
                'created_at' => Carbon::now()
            ]);

            // файлы
            if ($request->ticketImages) {
                foreach ($request->ticketImages as $ticketImage) {
                    File::create([
                        'user_id' => $request->user()->id,
                        'ticket_id' => $ticket->id,
                        'message_id' => $message->id,
                        'url' => $ticketImage,
                        'created_at' => Carbon::now()
                    ]);
                }
            }

            return redirect('/ticket/' . $ticket->id . '/view')->with('flash_success', 'Ваше обращение создано. Скоро с вами свяжутся наши сотрудники.');
        }

        return redirect()->back()->withInput()->with('flash_error', 'Не получилось создать обращение. Попробуйте ещё раз.');
    }

    // просмотр тикета
    public function view($ticketId)
    {
        $user = Auth::user();

        if (!$ticket = Ticket::where('id', '=', $ticketId)->first()) {
            return redirect('/ticket')->with('flash_warning', 'Тикет не найден.');
        }

        if (!$user->isAdmin() && $user->id !== $ticket->user_id) {
            return redirect('/ticket')->with('flash_warning', 'Нет доступа к этому тикету.');
        }

        $counters = $user->isAdmin() ? Ticket::getCounters() : null;

        $messages = $ticket->messages()
            ->with(['author', 'files'])
            ->orderBy('id', 'DESC')
            ->paginate(10);

        return view('tickets.view', [
            'ticket' => $ticket,
            'messages' => $messages,
            'counters' => $counters,
        ]);
    }

    public function comment(TicketAddRequest $request, $ticketId)
    {
        $user = Auth::user();

        if (!$ticket = Ticket::find($ticketId)) {
            return redirect('/ticket')->with('flash_warning', 'Обращение не найдено.');
        }

        // нельзя комментить не свои тикеты
        if (!$user->isAdmin() && $user->id !== $ticket->user_id) {
            return redirect('/ticket')->with('flash_warning', 'Нет доступа к этому обращению.');
        }

        // нельзя комментить закрытые тикеты
        if (!$user->isAdmin() && $ticket->closed) {
            return redirect('/ticket')->with('flash_warning', 'Обращение закрыто.');
        }

        $message = Message::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'text' => $request->get('message') ?: '',
            'created_at' => Carbon::now()
        ]);

        if ($request->ticketImages) {
            foreach ($request->ticketImages as $ticketImage) {
                File::create([
                    'user_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'message_id' => $message->id,
                    'url' => $ticketImage,
                    'created_at' => Carbon::now()
                ]);
            }
        }

        $ticket->last_message_at = Carbon::now();
        $ticket->save();

        return redirect('/ticket/' . $ticket->id . '/view')->with('flash_success', 'Сообщение добавлено.');
    }

    public function toggle_status($ticketId)
    {
        $user = Auth::user();

        if (!$ticket = Ticket::where('id', '=', $ticketId)->first()) {
            return redirect('/ticket')->with('flash_warning', 'Обращение не найдено.');
        }

        // закрывать могут оба, открывать только админ
        if ($ticket->closed && !$user->isAdmin()) {
            return redirect('/ticket')->with('flash_warning', 'Только администратор может открыть обращение.');
        }

        $ticket->closed = !$ticket->closed;
        $ticket->save();

        return redirect('/ticket/' . $ticket->id . '/view')->with('flash_success', 'Статус обращения переключен.');
    }
}
