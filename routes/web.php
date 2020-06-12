<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::resource('homepage', 'HomepageController');
Route::get('/', function () {
    return view('content.home');
})->name('home');

//Produk
Route::get('produk', 'ProdukController@shop')->name('produk');
Route::get('/produk/{url}','ProdukController@kategori_produk')->name('kategori');
Route::get('/detail_produk/{id}','ProdukController@detailproduk')->name('detail_produk');

Route::get('kontak', function () {
    return view('content.kontak');
})->name('kontak');

//Keranjang
Route::match(['get','post'],'/keranjang', 'KeranjangController@keranjang')->name('keranjang');
Route::match(['get','post'],'/add-keranjang', 'KeranjangController@addtokeranjang')->name('add_keranjang');
Route::get('keranjang/delete-product/{id}','KeranjangController@deleteKeranjangProduk');
Route::get('/keranjang/update-quantity/{id}/{qty}','KeranjangController@updateqtycart');

//Pesanan
Route::match(['get','post'],'checkout','PesananController@checkout')->name('checkout');
Route::match(['get','post'],'pesanan_saya','PesananController@orderReview')->name('pesanan_saya');
Route::match(['get','post'],'konfirmasi_bayar','KonfbayarController@simpan')->name('konfirmasi_bayar');
// Route::match(['get','post'],'belum_bayar','PesananController@belumbayar')->name('belum_bayar');
Route::match(['get','post'],'menunggu_konfirmasi','PesananController@menunggukonfirmasi')->name('menunggu_konfirmasi');
Route::match(['get','post'],'batalkan_pesanan','PesananController@batalkanPesanan')->name('batalkan_pesanan');
// Route::match(['get','post'],'pesanan_dikemas','PesananController@pesanandikemas')->name('pesanan_dikemas');
// Route::match(['get','post'],'pesanan_dikirim','PesananController@pesanandikirim')->name('pesanan_dikirim');
Route::match(['get','post'],'pesanan_diterima','PesananController@pesananditerima')->name('pesanan_diterima');

//Profile Pembeli
Route::get('profile', 'UsersController@users')->name('profile');
Route::match(['get','post'],'/edit-profile', 'UsersController@editusers')->name('edit_profile');
Route::get('/edit-image', 'UsersController@showupdateimage');
Route::post('/edit-image', 'UsersController@updateimage')->name('edit_image');
Route::get('/edit-password', 'UsersController@showpassword');
Route::post('/edit-password', 'UsersController@editpassword')->name('edit_password');
Route::get('/upload', 'UsersController@upload');
Route::post('/upload/proses', 'UsersController@proses_upload')->name('upload_proses');


Route::get('login-admin', function(){
    return view('content.login');
})->name('loginadmin');

// Route::post('user-login', 'AuthController@postLogin')->name('postlogin');

// Route::get('daftar', 'AuthController@getDaftar')->name('daftar');

// Route::post('daftar', 'AuthController@postDaftar')->name('postdaftar');

// Route::get('/home_user', 'AuthController@index')->name('home_user')->middleware('verified');

Auth::routes(['verify' => true]);

Route::get('/home', 'HomeController@index');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');
