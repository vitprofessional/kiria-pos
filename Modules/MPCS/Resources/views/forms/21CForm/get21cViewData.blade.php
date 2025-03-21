

                @php
                
                $todayTotalQty = 0;
                $todayTotalAmount = 0;
                //get category data to query product list
                $catData = \App\Category::where(['name'=>'Fuel'])->first();
                $subCatData = \App\Category::where(['name'=>$categoryName,'parent_id'=>$catData->id])->first();
                $productData = \App\Product::where(['category_id'=>$catData->id,'sub_category_id'=>$subCatData->id])->first();

                $columnName = $colKey;
                $qtyToday    = 0;
                $amountToday = 0;

                $qtyPrevDay    = 0;
                $amountPrevDay = 0;

                
                //current date data show here
                if($columnName == 'today'):
                    if($productData):
                        $qtyData = \App\PurchaseLine::where(['product_id'=>$productData->id])->whereDate('created_at', '=', date('Y-m-d'))->get();
                    else:
                        $qtyData = '';
                    endif;
                    if($qtyData):
                        $qtyToday = $qtyData->sum('quantity');
                        $purchase_price = $qtyData->sum('purchase_price');
                        $amountToday = round($purchase_price*$qtyToday,2);
                    endif;
                endif;

                //previous date data show here
                
                if($columnName == 'previous_day'):
                    if($productData):
                        $qtyData = \App\PurchaseLine::where(['product_id'=>$productData->id])->whereDate('created_at', '=', date('Y-m-d'))->get();
                    else:
                        $qtyData = '';
                    endif;
                    if($qtyData):
                        $qtyToday = $qtyData->sum('quantity');
                        $purchase_price = $qtyData->sum('purchase_price');
                        $amountToday = round($purchase_price*$qtyToday,2);
                    endif;
                endif;

                //previous date data show here

                if($columnName == 'total'):
                    if($productData):
                        $qtyData = \App\PurchaseLine::where(['product_id'=>$productData->id])->whereDate('created_at', '=', date('Y-m-d'))->get();
                    else:
                        $qtyData = '';
                    endif;
                    if($qtyData):
                        $qtyToday = $qtyData->sum('quantity');
                        $purchase_price = $qtyData->sum('purchase_price');
                        $amountToday = round($purchase_price*$qtyToday,2);
                    endif;
                endif;
            @endphp