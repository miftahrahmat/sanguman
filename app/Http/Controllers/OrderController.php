<?php

namespace App\Http\Controllers;

use App\Http\Requests\BecomeChefRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\TakeLogRequest;
use App\Http\Requests\LapRequest;
use App\Models\Chef;
use App\Models\Order;
use App\Models\Portion;
use App\Models\TakeLog;
use App\Models\lapor;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function index(){

        $orders = Order::latest()->paginate(5);

        $portions = Portion::whereBetween('created_at', [
            Carbon::now()->startOfDay(), Carbon::now()->endOfDay()
        ])->paginate(5);

        $portionss = Portion::whereBetween('created_at', [
            Carbon::now()->startOfDay(), Carbon::now()->endOfDay()
        ])->paginate(5);

        $takelogs = TakeLog::latest()->paginate(5);

        $myorder = request()->user()->portions()->whereDate('created_at',  Carbon::now())->first();

        $myportion = request()->user()->portions()->whereDate('created_at', Carbon::now())->first();

        $mychef = request()->user()->chefs()->whereDate('created_at', Carbon::now())->first();

        $chef = Chef::whereDate('created_at', Carbon::now())->first();
        $chef = ($chef) ? $chef->user->name : null;

        $log = TakeLog::whereDate('created_at',Carbon::now())->first();
        $log = $log ? $log : null;

        $myuser = auth()->user()->email_verified_at;
        $myuser = $myuser ? $myuser : null;

        $koki = Chef::whereDate('created_at', Carbon::now())->get();

        $pesanan = Portion::whereBetween('created_at', [
            Carbon::now()->startOfDay(), Carbon::now()->endOfDay()
        ])->get();
        $pesanan = $pesanan ? $pesanan : null;

        $port = request()->user()->portions()->whereDate('created_at',  Carbon::now())->get();

        $lapo = lapor::whereBetween('created_at', [
            Carbon::now()->startOfHour(), Carbon::now()->endOfHour("60")
        ])->get();
        $lapo = $lapo ? $lapo : null;

        return view('orders.index',compact('orders','portions','takelogs','myorder','mychef','myportion','portionss','chef','log','myuser','koki','pesanan', 'port', 'lapo'))->with('i', (request()->input('page', 2) - 1) * 1);

    }

     public function store(OrderRequest $request,Order $order)
    {
        $input = $request->validated();

        $order = Order::create($input);

        $port  = Portion::create([
            'user_id' => auth()->user()->id,
            'order_id' => $order->id,
            'portion' => $order->total_portion,
        ]);

        return redirect('orders')->with('success','Pesanan Telah diBuat');

    }

    public function update(OrderRequest $request)
    {
      $portion = request()->user()->portions()->whereDate('created_at', Carbon::now())->first();
      $portion->update([
        'portion' =>  $request->get('total_portion')
      ]);

        return redirect('orders')->with('success','Pesan Berhasil di Update');
    }

    public function becomeChef(BecomeChefRequest $request)
    {
        $input = $request->validated();

        Chef::create($input);

       return redirect('orders')->with('Selamat','Anda Jadi Tukang Masak');

    }

    public function masak(TakeLogRequest $request)
    {
        $log = $request->validated();
        TakeLog::create($log);

        return redirect()->to(url('orders'))->with('message','anda bisa masak sekarang');

    }

    public function log()
    {
        $portions = Portion::latest()->paginate(5);
        return view('orders.takeLog',compact('portions'))->with('i', (request()->input('page', 2) - 1) * 1);
    }

    public function takeLog(TakeLogRequest $request)
    {

        $take = $request->validated();
        $portions = $take['portion_id'];

        foreach ($portions as $index => $portion) {
            $portion = Portion::find($take['portion_id'][ $index ]);

            if ($portion->portion - $take['portion'][ $index ] >=0) {
                $portion->decrement('portion',$take['portion'][ $index ]);
            }else{
                return back()->with('error','Pengambilan porsi salah !!');
            }
        }

        return redirect('orders')->with('log','Log Pesanan telah disimpan');
    }

    public function laporan(Request $request)
    {
        $chef = Chef::whereDate('created_at', Carbon::now())->first();
        $chef = ($chef) ? $chef->user->name : null;
        $lap = lapor::create(['portion' => $request->portion, 'user_id' => auth()->user()->id]);

        return redirect('orders')->with('lap', 'Success, Pesanan akan berkurang setelah chef '.$chef.' meng-cofirm laporan anda');
    }
}
