<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OrderManager extends Controller
{
    function showCheckout(){
        return view('checkout');
    }

    function checkoutPost(Request $request){
        $request->validate([
            'address' => 'required',
            'pincode' => 'required',
            'phone' => 'required',
        ]);

        $cartItems = DB::table("cart")->select("product_id", DB::raw("count(*) as quantity"))
            ->join('products', 'cart.product_id', '=', 'products.id')
            ->select("cart.product_id", DB::raw("count(*) as quantity"), "products.price")
            ->where("cart.user_id", \Illuminate\Support\Facades\Auth::user()->id)
            ->groupBy("cart.product_id", "products.price")
            ->get();

        if($cartItems->isEmpty()){
            return redirect(route('cart.show'))->with('error', 'Cart is empty!');
        }

        $productIds = [];
        $quantities = [];
        $totalPrice = 0;

        foreach($cartItems as $cartItem){
            $productIds[] = $cartItem->product_id;
            $quantities[] = $cartItem->quantity;
            $totalPrice += $cartItem->price * $cartItem->quantity;
        }

        $order= new Orders();
        $order->user_id = \Illuminate\Support\Facades\Auth::user()->id;
        $order->address = $request->address;
        $order->pincode = $request->pincode;
        $order->phone = $request->phone;
        $order->product_id = json_encode($productIds);
        $order->total_price = $totalPrice;
        $order->quantity = json_encode($quantities);
        if($order->save()){
            DB::table('cart')->where('user_id', \Illuminate\Support\Facades\Auth::user()->id)->delete();
            return redirect(route('cart.show'))->with('success', 'Order placed successfully!');
        }
        return redirect(route('cart.show'))->with('error', 'Something went wrong!');
    }

    function showProductCheckout($id) {
        $product = \App\Models\Products::findOrFail($id);
        return view('checkout', ['product' => $product, 'quantity' => request('quantity', 1)]);
    }

    function checkoutProductPost(Request $request, $id) {
        $request->validate([
            'address' => 'required',
            'pincode' => 'required',
            'phone' => 'required',
            'quantity' => 'required|numeric|min:1'
        ]);

        $product = \App\Models\Products::findOrFail($id);
        
        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Not enough stock available.');
        }

        $order = new Orders();
        $order->user_id = \Illuminate\Support\Facades\Auth::user()->id;
        $order->address = $request->address;
        $order->pincode = $request->pincode;
        $order->phone = $request->phone;
        $order->product_id = json_encode([$id]);
        $order->total_price = $product->price * $request->quantity;
        $order->quantity = json_encode([$request->quantity]);

        if($order->save()){
            // Decrease the stock
            $product->stock -= $request->quantity;
            $product->save();
            
            return redirect()->route('products.details', $product->slug)->with('success', 'Order placed successfully!');
        }
        return back()->with('error', 'Something went wrong!');
    }
}
