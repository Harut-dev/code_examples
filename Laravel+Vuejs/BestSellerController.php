<?php

namespace App\Http\Controllers;

use App\Http\Requests\FetchDataRequest;
use App\Product;
use App\ProductTranslation;

class BestSellerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getBestSellerProducts(FetchDataRequest $request)
    {
      $page = $request->input('page');
      $rowsPerPage = $request->input('rowsPerPage');
      $sortBy = $request->input('sortBy');
      $descending = $request->input('descending');
      $language_id = $request->input('language_id');
      $direction = $descending == 1 ? 'asc' : 'desc';
      $offset = ($page - 1) * $rowsPerPage;

      $productIds = Product::where('discount_id', '!=', null)->pluck('id')->all();
      if (empty($productIds)) {
        return response()->json(['items' => [], 'totalItems' => 0]);
      }
      $queryItem = ProductTranslation::whereIn('product_id', $productIds)->where('language_id', $language_id);

      $totalItems = $queryItem->count();

      // checking order column for products table
      $productsTableName = Product::getTableName();
      $productTranslationsTableName = ProductTranslation::getTableName();

      // products table
      $columns = \Schema::getColumnListing($productsTableName);
      if (in_array($sortBy, $columns)) {
        $queryItem->join($productsTableName, $productTranslationsTableName . '.product_id', '=', $productsTableName . '.id')
          ->orderBy($sortBy, $direction);
      }

      $queryItem
        ->with(['product' => function ($q) {
          $q->select('id', 'user_id', 'status', 'image','price', 'discount_id', 'discounts_price');
          $q->with(['user' => function ($q) {
            $q->select('id', 'name');
          }]);
          $q->with(['discount' => function ($q) {
            $q->select('id', 'percentage','amount');
          }]);
          $q->with(['images' => function ($q) {
            $q->select('product_id', 'image');
          }]);
        }])
        ->offset($offset)
        ->limit($rowsPerPage);

      // product_translations table
      $columns = \Schema::getColumnListing($productTranslationsTableName);
      if (in_array($sortBy, $columns)) {
        $queryItem->orderBy($sortBy, $direction);
      }
      $items = $queryItem->get([$productTranslationsTableName . '.*']);

      return response()->json(['items' => $items, 'totalItems' => $totalItems]);
    }

}
