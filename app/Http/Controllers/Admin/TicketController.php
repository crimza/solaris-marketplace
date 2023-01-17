<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tickets\Message;
use App\Models\Tickets\Ticket;
use Illuminate\Http\Request;

class TicketController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        if(!policy(Ticket::class)->index($request->user())) {
            abort(403);
        }

        $tickets = Ticket::applySearchFilters($request)
            ->with(['author', 'messages'])
            ->orderBy('last_message_at', 'DESC')
            ->paginate(20);
        // получаем все свои тикеты для счетчиков
        $counters = Ticket::getCounters();

        return view('admin.tickets.index', [
            'counters' => $counters,
            'tickets' => $tickets
        ]);
    }

    public function delete(Request $request, $ticketId)
    {
        if(!policy(Ticket::class)->destroy($request->user())) {
            abort(403);
        }

        if (!$ticket = Ticket::where('id', '=', $ticketId)->first())
            return redirect('/ticket')->with('flash_warning', 'Тикет не найден.');

        // удаляем все комменты
        Message::where('ticket_id', '=', $ticket->id)->delete();

        // удаляем тикет
        $ticket->delete();

        return redirect('/admin/ticket')->with('flash_success', 'Тикет удален.');
    }

    public function delete_msg(Request $request, $ticketId, $msgId)
    {
        if (!$ticket = Ticket::select('id')->where('id', '=', $ticketId)->first())
            return redirect('/ticket')->with('flash_warning', 'Тикет не найден.');

        if (!$msg = Message::where('id', '=', $msgId)->where('ticket_id', '=', $ticket->id)->first())
            return redirect('/ticket')->with('flash_warning', 'Сообщение не найдено.');

        if(!policy(Ticket::class)->destroyMessage($request->user(), $msg)) {
            abort(403);
        }

        $msg->delete();

        return redirect('/ticket/' . $ticket->id . '/view')->with('flash_success', 'Сообщение удалено.');
    }
}
