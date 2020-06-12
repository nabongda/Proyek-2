<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Konfbayar;
use Auth;
use Session;
use DB;
use App\User;
use App\Produk;
use App\Produk_Detail;
use App\Kategori;
use App\Keranjang;
use App\ProfilePembeli;
use App\AlamatDelivery;
use App\Cart;
use App\Detail_Pesanan;

class KonfbayarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function simpan(Request $request)
    {
        $id_users = Auth::user()->id;
        $pesanan = DB::table('detail_pesanan')->where('id', $id_users)->first();
        $userCart = DB::table('riwayat_keranjang')->where(['id_users'=>$id_users])->get();
        $detail_pesanan = Detail_Pesanan::where('id',$id_users)->first();
        $detail_pesanan =json_decode(json_encode($detail_pesanan));
        $detail_user = User::where('id',$id_users)->first();
        $detail_shipping = AlamatDelivery::where('id_users',$id_users)->first();
        $detail_shipping =json_decode(json_encode($detail_shipping));
        $session_id = DB::table('riwayat_keranjang')->select('session_id')->where(['id_users'=>$id_users])->get();

        $userCart = DB::table('riwayat_keranjang')->where(['id_users'=>$id_users], ['session_id'=>$session_id])->get();

        foreach($userCart as $key =>$product){
            $nama_produk = Produk::where('id_produk',$product->id_produk)->first();
            $userCart[$key]->nama_produk = $nama_produk->nama_produk;
        }
    
        foreach($userCart as $key =>$product){
            $harga = Produk::where('id_produk',$product->id_produk)->first();
            $userCart[$key]->harga = $harga->harga;
        }

        if($request->isMethod('post')){


            $date_time = date('Y-m-d');

            $bukti = $request->file('bukti_bayar');
            $nameOriginal = $bukti->getClientOriginalName();
            $path = 'img/Foto Bukti Transfer/';
            $bukti-> move($path,$nameOriginal);
            $save = new Konfbayar;
            $save->id_pesanan=$pesanan->id_pesanan;
            $save->bank_asal=$request->bank;
            $save->nama_pemilik_rek=$request->namarek;
            $save->date_time=$pesanan->date_time;
            $save->jumlah_bayar=$pesanan->jumlah_bayar;
            $save->bukti_bayar=$nameOriginal;
            $save->save();

            $status="Menunggu Konfirmasi";
            Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);
            
            return redirect()->action('PesananController@orderReview');
            //return view('content.draftps')->with(compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));

        }

        return view('content.konfirmasi_bayar')->with(compact('pesanan'));
    }
}
