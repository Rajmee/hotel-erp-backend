<?php
namespace App\Helpers;

use Illuminate\Database\Capsule\Manager as DB;

class Helper {

    public function additionNumber($a, $b){
        return $a + $b;
    }//1
    public function getLastID($table){
        // $getLastId = DB::raw('select id from'+$table)->orderBy('id','DESC')->get()->first();
        //var_dump($getLastId->id);
        // $getLastId = DB::raw('select id from'+$table')->orderBy('id','DESC')->get()->first();
        // $getLastId = DB::raw('select * from '.$table.' orderBy("id","DESC")');
        // $getLastId = DB::raw('select * from '.$table.' orderBy("id","DESC")');
        // $getLastId = DB::statement('select * from '.$table.' order by id DESC');
        // $getLastId = DB::select( DB::raw("SELECT * FROM '$table' WHERE some_col = '$someVariable'") );

        // $getLastId = DB::select( DB::raw("SELECT `id` FROM $table ORDER BY `id` DESC FETCH FIRST 1 ROWS ONLY"));    //Working...
        // SELECT max(id) FROM tableName
        $getLastId = DB::select( DB::raw("SELECT AUTO_INCREMENT as id FROM information_schema.TABLES WHERE TABLE_NAME = '$table'"));    //Working...
        //  dd($getLastId);
        // DB::statement("your query")
        // DB::select('select * from users where id = ?', [1]);
        // var_dump($getLastId[0]->id);
        echo !!!$getLastId[0]->id;
        if(!!!$getLastId[0]->id){
            $getLastId = 1;
        }
        else{
            $getLastId = $getLastId[0]->id;
        }
        
        return $getLastId;
    }
    public function getItem($params,$table,$condition){
        $getItem = DB::select( DB::raw("SELECT $params FROM $table WHERE `id`=$condition"));
        return $getItem;
    }
    public function getLastSupplierAccountBalance ($table,$supplier_id){
        $getLastBalance = DB::select( DB::raw("SELECT `balance` FROM $table WHERE `supplier_id` = $supplier_id ORDER BY `id` DESC FETCH FIRST 1 ROWS ONLY"));
        return $getLastBalance;
    }
    public function getIdByInvoiceID($table, $invoice_id){
        $getResult = DB::select( DB::raw("SELECT `id` FROM $table WHERE `supplier_invoice` = '$invoice_id'"));
        // $getResult = DB::select( DB::raw("SELECT AUTO_INCREMENT as id FROM information_schema.TABLES WHERE TABLE_NAME = $table"));
        return $getResult;
    }

    public function getInvDetailsBySupplierId($table, $supplierID){
        $getResult = DB::select( DB::raw("SELECT `id` FROM `supplier_invoice` WHERE `supplier_invoice` = '$supplierID'"));
        if(!$getResult){
            return null;
        }else{
            $supplierInvoiceId = $getResult[0]->id;
        }
        $getResult = DB::select( DB::raw("SELECT * FROM `supplier_invoice_item` WHERE `supplier_invoice_id` = $supplierInvoiceId"));
        return $getResult;
    }
}

