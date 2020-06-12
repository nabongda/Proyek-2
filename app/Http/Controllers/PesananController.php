<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Auth;
use Session;
use Image;
use DB;
use App\User;
use App\Produk;
use App\Produk_Detail;
use App\Kategori;
use App\Keranjang;
use App\AlamatDelivery;
use App\Detail_Pesanan;

class PesananController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function checkout(Request $request)
    {
        $id_users = Auth::user()->id;
        $user_email = Auth::user()->email;
        $detail_user = User::find($id_users);
        $userCart = DB::table('keranjang')->where(['id_users'=>$id_users])->get();
        $alamat = DB::table('users')->select('provinsi', 'kabkot', 'alamat')->where('id', $id_users);

        $shippingCount = AlamatDelivery::where('id_users',$id_users)->count();
        $detail_shipping = array();
        if($shippingCount>0){
            $detail_shipping = AlamatDelivery::where('id_users',$id_users)->first();
        }
        
        $session_id = Session::get('session_id');
        DB::table('keranjang')->where(['session_id'=>$session_id])->update(['id_users'=>$id_users]);
        
        if($request->isMethod('post')){
            $data = $request->all();

            if(empty($data['billing_nama']) ||empty($data['billing_no_hp'])
            ||empty($data['billing_provinsi']) ||empty($data['billing_kabkot']) 
            ||empty($data['billing_alamat']) ||empty($data['shipping_nama'])
            ||empty($data['shipping_no_hp']) ||empty($data['shipping_provinsi'])
            ||empty($data['shipping_kabkot']) ||empty($data['shipping_alamat'])){
            return redirect()->back()->with('flash_message_error','Harap isi semua data untuk melanjutkan');
            }
            //Update User Details
            User::where('id',$id_users)->update(['nama'=>$data['billing_nama'],'no_hp'=>$data['billing_no_hp'],
            'provinsi'=>$data['billing_provinsi'],'kabkot'=>$data['billing_kabkot'],'alamat'=>$data['billing_alamat']]);

            if($shippingCount>0){
                AlamatDelivery::where('id_users',$id_users)->update(['nama'=>$data['shipping_nama'],'no_hp'=>$data['shipping_no_hp'],
                'provinsi'=>$data['shipping_provinsi'],'kabkot'=>$data['shipping_kabkot'],'alamat'=>$data['shipping_alamat'],
                'catatan'=>$data['shipping_catatan']]);
            }
            else{
            $shipping = new AlamatDelivery;
            $shipping->id_users = $id_users;
            $shipping->nama = $data['shipping_nama'];
            $shipping->no_hp = $data['shipping_no_hp'];
            $shipping->provinsi = $data['shipping_provinsi'];
            $shipping->kabkot = $data['shipping_kabkot'];
            $shipping->alamat = $data['shipping_alamat'];
            $shipping->catatan = $data['shipping_catatan'];
            $shipping->save();
            }

            $produkorder = DB::table('keranjang')->select('id_produk', 'qty')->where('id_users', $id_users)->first();
            date_default_timezone_set('Asia/Jakarta');
            $date_time = date('Y-m-d H:i:s');
            $invoice = mt_rand(10000,99999);

            $pesananCount = Detail_Pesanan::where('id',$id_users)->count();
            $detail_pesanan = array();
            if($pesananCount>0){
                $detail_pesanan = Detail_Pesanan::where('id',$id_users)->first();
            }

            $pesanan = new Detail_Pesanan;
            $pesanan->id = $id_users;
            $pesanan->id_produk = $produkorder->id_produk;
            $pesanan->qty = $data['qty'];
            $pesanan->jumlah_bayar = $data['jumlah_total'];
            $pesanan->date_time = $date_time;
            $pesanan->invoice = $invoice;
            $pesanan->kabkot = $data['shipping_kabkot'];
            $pesanan->provinsi = $data['shipping_provinsi'];
            $pesanan->alamat = $data['shipping_alamat'];
            $pesanan->status = "Belum Bayar";
            $pesanan->save();

            $hapuskeranjang = Keranjang::where('id_users',$id_users)->delete();

            $stokproduk = Produk::where('id_produk', $produkorder->id_produk)->first();
            $stokbaru = $stokproduk->stok - $produkorder->qty;
            Produk::where('id_produk', $produkorder->id_produk)->update(['stok'=>$stokbaru]);

            return redirect()->action('PesananController@orderReview');
        }

        foreach($userCart as $key =>$product){
            $productDetails = Produk::where('id_produk',$product->id_produk)->first();
            $userCart[$key]->image = $productDetails->image;
        }

        foreach($userCart as $key =>$product){
            $nama_produk = Produk::where('id_produk',$product->id_produk)->first();
            $userCart[$key]->nama_produk = $nama_produk->nama_produk;
        }

        foreach($userCart as $key =>$product){
            $kode_produk = Produk::where('id_produk',$product->id_produk)->first();
            $userCart[$key]->kode_produk = $kode_produk->kode_produk;
        }

        foreach($userCart as $key =>$product){
            $harga = Produk::where('id_produk',$product->id_produk)->first();
            $userCart[$key]->harga = $harga->harga;
        }

        return view('content.checkout')->with(compact('detail_user','alamat','detail_shipping','userCart'));
    }

    public function orderReview(Request $request)
    {
        
        //$time = Carbon::now();
        $id_users = Auth::user()->id;
        $user_email = Auth::user()->email;
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

        //dd($waktu);
        if(!empty($detail_pesanan)){
            date_default_timezone_set('Asia/Jakarta');
            $time = Carbon::createFromFormat('Y-m-d H:i:s', $detail_pesanan->date_time);
            $coba = $time->diffInDays($time->copy()->addDay());
            $waktu = Carbon::parse($time)->subDays(0)->diffForHumans();
            $test = explode(" ",$waktu);
            if($test[0]=="1"&&$test[1]=="hari"){
            //if($waktu>="1 hari yang lalu"){
                $stokproduk = Produk::where('id_produk', $detail_pesanan->id_produk)->first();
                $stokbaru = $stokproduk->stok + $detail_pesanan->qty;
                Produk::where('id_produk', $detail_pesanan->id_produk)->update(['stok'=>$stokbaru]);

                $status="Pesanan Dibatalkan";
                Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

                // DB::table('detail_pesanan')->where('id_pesanan',$detail_pesanan->id_pesanan)->delete();
                // DB::table('riwayat_keranjang')->where('id_users',$detail_pesanan->id)->delete();

                return view('content.draftps')->with(compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
            }
            else{
                return view('content.draftps')->with(compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
            }
        }
        else{
            return view('content.ps')->with(compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        }
         
    }

    public function belumbayar()
    {
        $id_users = Auth::user()->id;
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

        return redirect()->action('PesananController@orderReview');
        //return view('content.status_bb', compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        //return redirect(route('pesanan_saya'));
    }

    public function menunggukonfirmasi()
    {
        $id_users = Auth::user()->id;
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

        $status="Menunggu Konfirmasi";
        Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

        return redirect()->action('PesananController@orderReview');

        //return view('content.draftps')->with(compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        //return view('content.status_mk', compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        //return redirect(route('pesanan_saya'));
    }

    // public function batalkanPesanan()
    // {
    //     $id_users = Auth::user()->id;
    //     $detail_pesanan = Detail_Pesanan::where('id',$id_users)->first();
    //     $detail_pesanan =json_decode(json_encode($detail_pesanan));
    //     $detail_user = User::where('id',$id_users)->first();
    //     $detail_shipping = AlamatDelivery::where('id_users',$id_users)->first();
    //     $detail_shipping =json_decode(json_encode($detail_shipping));
    //     $session_id = DB::table('riwayat_keranjang')->select('session_id')->where(['id_users'=>$id_users])->get();

    //     $userCart = DB::table('riwayat_keranjang')->where(['id_users'=>$id_users], ['session_id'=>$session_id])->get();

    //     foreach($userCart as $key =>$product){
    //         $nama_produk = Produk::where('id_produk',$product->id_produk)->first();
    //         $userCart[$key]->nama_produk = $nama_produk->nama_produk;
    //     }
    
    //     foreach($userCart as $key =>$product){
    //         $harga = Produk::where('id_produk',$product->id_produk)->first();
    //         $userCart[$key]->harga = $harga->harga;
    //     }

    //     $stokproduk = Produk::where('id_produk', $detail_pesanan->id_produk)->first();
    //     $stokbaru = $stokproduk->stok + $detail_pesanan->qty;
    //     Produk::where('id_produk', $detail_pesanan->id_produk)->update(['stok'=>$stokbaru]);

    //     $status="Pesanan Dibatalkan";
    //     Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

    //     //return redirect()->action('PesananController@orderReview');
    //     return view('content.status_bp', compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
    //     //return redirect(route('pesanan_saya'));
    // }

    public function batalkanPesanan(Request $request)
    {
        
        //$time = Carbon::now();
        $id_users = Auth::user()->id;
        $user_email = Auth::user()->email;
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

        $stokproduk = Produk::where('id_produk', $detail_pesanan->id_produk)->first();
        $stokbaru = $stokproduk->stok + $detail_pesanan->qty;
        Produk::where('id_produk', $detail_pesanan->id_produk)->update(['stok'=>$stokbaru]);

        $status="Pesanan Dibatalkan";
        Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

        DB::table('detail_pesanan')->where('id_pesanan',$detail_pesanan->id_pesanan)->delete();
        DB::table('riwayat_keranjang')->where('id_users',$detail_pesanan->id)->delete();

        //return view('content.draftps')->with(compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        return redirect()->action('PesananController@orderReview');
    }

    public function pesanandikemas()
    {
        $id_users = Auth::user()->id;
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

        $status="Pesanan Dikemas";
        Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

        return redirect()->action('PesananController@orderReview');
        //return view('content.status_kms', compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        //return redirect(route('pesanan_saya'));
    }

    public function pesanandikirim()
    {
        $id_users = Auth::user()->id;
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

        $status="Pesanan Dikirim";
        Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

        //return redirect()->action('PesananController@orderReview');
        return view('content.status_krm', compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        //return redirect(route('pesanan_saya'));
    }

    public function pesananditerima()
    {
        $id_users = Auth::user()->id;
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

        $status="Pesanan Diterima";
        Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

        return redirect()->action('PesananController@orderReview');
        //return view('content.status_trm', compact('detail_user', 'detail_shipping', 'detail_pesanan', 'userCart'));
        //return redirect(route('pesanan_saya'));
    }

    public function pesananditerim()
    {
        $id_users = Auth::user()->id;
        $detail_pesanan = Detail_Pesanan::where('id',$id_users)->first();
        $detail_pesanan =json_decode(json_encode($detail_pesanan));

        // $lama = 1;
        // $hapus1 = DB::table('detail_pesanan')->where(['id'=>$id_users], [DATEDIFF(CURDATE(), 'date_time') > $lama])->delete();
        // $hapus2 = DB::table('transaksi')->where(['id_pesanan'=>$hapus1->id_pesanan], [DATEDIFF(CURDATE(), 'date_time') > $lama])->delete();
        // $hapus3 = DB::table('riwayat_keranjang')->where(['id_users'=>$id_users], [DATEDIFF(CURDATE(), 'tgl_perubahan') > $lama])->delete();
        $time = Carbon::createFromFormat('Y-m-d H:i:s', $detail_pesanan->date_time);
        $coba = $time->diffInDays($time->copy()->addDay());
        $waktu = Carbon::parse($time)->subDays(0)->diffForHumans();

        if($waktu>="1 hari yang lalu"){
            dd('hapus');
        } else{
            dd('ga bisa apus');
        }


        $status="Pesanan Diterima";
        Detail_Pesanan::where(['id'=>$id_users])->update(['status'=>$status]);

        //return redirect()->action('PesananController@orderReview');
        return redirect(route('pesanan_saya'));
    }
    
}
