<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\View;
use Auth;
use Session;
use Image;
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

class ProdukController extends Controller
{
    public function shop(Request $request){
        $s = $request->input('s');
        $produk = Produk::search($s)->paginate(12);
        $produk = Produk::orderBy('id_produk','DESC')->search($s)->paginate(12);
        $produk = Produk::inRandomOrder()->search($s)->paginate(12);
        $kategori = Kategori::with('kategori')->where(['sub_kat'=>1])->get();
        $subkategori = Kategori::with('kategori')->where(['sub_kat'=>2])->get();
        
        return view('content.produk', compact('produk','kategori','subkategori','s'));
    }

    public function akse()
    {
        $subkategori = Kategori::with('kategori')->where(['sub_kat'=>2])->get();

        View::composer(
            ['profile', 'dashboard'],
            'App\Http\View\Composers\MyViewComposer'
        );
    }

    public function kategori_produk($url = null)
    {
        $countCategory = Kategori::where(['url'=>$url])->count();
        if($countCategory==0){
            abort(404);
        }

        //Display Categories or Sub Categories in left Sidebar of Home Page
        $kategori = Kategori::with('kategori')->where(['sub_kat'=>1])->get();
        $subkategori = Kategori::with('kategori')->where(['sub_kat'=>2])->get();
        $categoryDetails = Kategori::where(['url'=> $url])->first();

        if($categoryDetails->sub_kat==1){
         //if url is Main category url
            $produk = Produk::where(['id_kategori'=>$categoryDetails->id_kategori])->get();
            $produk = json_decode(json_encode($produk));
        }
        else{
            //if url is sub category url
            $produk = Produk::where(['id_kategori'=>$categoryDetails->id_kategori])->get();
        }

        return view('content.kategori_produk', compact('kategori','subkategori','categoryDetails','produk')); 
    }

    public function detailproduk($id=null)
    {
        $detail_produk = Produk::where('id_produk',$id)->first();
        $detail_produk = json_decode(json_encode($detail_produk));
        $total_stok = Produk::where('id_produk',$id)->sum('stok');
        $relateProduk=Produk::where([['id_produk','!=',$id],['id_kategori',$detail_produk->id_kategori]])->get();
        $kategori = Kategori::with('kategori')->where(['sub_kat'=>1])->get();
        $subkategori = Kategori::with('kategori')->where(['sub_kat'=>2])->get();

        return view('content.detail_produk', compact('detail_produk','kategori','subkategori','total_stok','relateProduk'));
    }

}