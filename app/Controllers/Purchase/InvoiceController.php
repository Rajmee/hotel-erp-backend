<?php

namespace  App\Controllers\Purchase;

use App\Helpers\Helper;

use App\Auth\Auth;
use App\Models\Purchase\Invoice;    //Table===========>  supplier_inv
use App\Models\Purchase\InvoiceItem;    //Table ======>  supplier_inv_item
use App\Models\Purchase\Supplier;       //Table ======>  supplier

use App\Models\Inventory\InventoryItem;       //Table ======>  Inventory Item

use Carbon\Carbon;

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;
use App\Models\Users\ClientUsers;
use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

/**Seeding tester */
use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;


//use Fzaninotto\Faker\Src\Faker\Factory;
//use Fzaninotto\Src\Faker;
use Faker\Factory;
use Faker;

/**Seeding tester */
class InvoiceController
{

    protected $customResponse;

    protected $validator;

    protected $params;
    protected $responseMessage;
    protected $outputData;
    protected $success;
    protected $user;

    /** Invoice ini */
    public $invoice;
    public $invoiceItem;
    private $faker;
    public $supplier;
    private $inventory;

    private $helper;

    public function __construct()
    {
        $this->customResponse = new CustomResponse();
        //Model Instance
        $this->invoice = new Invoice();
        $this->invoiceItem = new InvoiceItem();
        $this->supplier = new Supplier();

        $this->inventory = new InventoryItem();
        /*Model Instance END */
        $this->validator = new Validator();
        $this->user = new ClientUsers();
        $this->responseMessage = "";
        $this->outputData = [];
        $this->success = false;
        $this->faker = Factory::create();

        $this->helper = new Helper;
    }
 
    public function go(Request $request, Response $response)
    {
        $this->params = CustomRequestHandler::getAllParams($request);
        $action = isset($this->params->action) ? $this->params->action : "";

        $this->user = Auth::user($request);

        switch ($action) {
            case 'test':
                $this->run();
                break;
            case 'createSupplierInvoice':
                $this->createSupplierInvoice($request);
                break;
            case 'createSupplierInvoiceItem':
                $this->createSupplierInvoiceItem();
                break;
            case 'getAllSupplierInvoice':
                $this->getAllSupplierInvoice();
                break;
            case 'getInvoiceByID':
                $this->getInvoiceByID();
                break;
            case 'updateInvoice':
                $this->updateInvoice();
                break;
            case 'deleteInvoice':
                $this->deleteInvoice();
                break;
            case 'getInvoiceNumber':
                $this->getInvoiceNumber();
                break;
            case 'getInvoiceDetails':
                $this->getInvoiceDetails();
                break;
            case 'getItemDetailsByID':
                $this->getItemDetailsByID();
                break;
            case 'getInvoiceDetailsBySupplierID':
                $this->getInvoiceDetailsBySupplierID();
                break;
            case 'viewSupplierLedger':
                $this->viewSupplierLedger();
                break;
            case 'getIdByInvId':
                $this->getIdByInvId();
                break;
            case 'getInvDetailsBySupplierId':
                $this->getInvDetailsBySupplierId();
                break;
            default:
                $this->responseMessage = "Invalid request!";
                return $this->customResponse->is400Response($response, $this->responseMessage);
        }

        if (!$this->success) {
            return $this->customResponse->is400Response($response, $this->responseMessage, $this->outputData);
        }

        return $this->customResponse->is200Response($response, $this->responseMessage, $this->outputData);
    }/**Getting ID by INV ID */


    public function getInvDetailsBySupplierId(){

        $res = $this->helper->getInvDetailsBySupplierId('supplier_invoice_item',$this->params->supplier_invoice_id);
        $this->responseMessage = "Invoice Details Fetched Successfully!";
        $this->outputData = $res;
        $this->success = true;
    }

    public function getIdByInvId(){
        $result = $this->helper->getIdByInvoiceID('supplier_invoice', $this->params->invoice_id);
        $this->responseMessage = "Supplier ID fetched Successfully!";
        $this->outputData = $result[0]->id;
        $this->success = true;
    }


    /**Generating Supplier Ledger By Month & ID */
    public function viewSupplierLedger(){
        $supplierID = $this->params->supplier_id;
        $dateFrom = date($this->params->date_from);
        $dateTo = date($this->params->date_to);

        // $arr[] = array(
        //     "supplier_id"=>$supplierID,
        //     "date_from" => $dateFrom,
        //     "date_to" => $dateTo
        // );

        $result = DB::table('account_supplier')
        ->where('supplier_id',$supplierID)
        // ->where('created_at :> ', $dateFrom)
        // ->where('created_at :<', $dateTo )
        // ->whereBetween('created_at', ['NOW() - INTERVAL 30 DAY', 'NOW()'] )
        // ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->select('*')
        // ->toSql();
        ->get();

        $this->responseMessage = "Supplier Ledger fetched Successfully!";
        $this->outputData = $result;
        $this->success = true;
    }


    /**Generating ledger by ID and Month range */
    public function viewSupplierLedger(){
        // $supplierID = $this->params->supplier_id;
        // $dateFrom = $this->params->date_from;
        // $dateTo = $this->params->date_to;

        $this->responseMessage = "Supplier Ledger Fetched Successfully!";
        $this->outputData = $this->params;
        $this->success = true;
    }

    /**Get Invoice Details By Supplier ID */

    public function getInvoiceDetailsBySupplierID(){
        // $getInvDetails = DB::select(DB::raw(""));
        $getInvDetails = $this->invoice
        // ->join('supplier_invoice_item','supplier_invoice.local_invoice','=','supplier_invoice_item.local_invoice')
        ->join('supplier_invoice_item','supplier_invoice.local_invoice','supplier_invoice_item.local_invoice')
        ->select('supplier_invoice.*',DB::raw("count('supplier_invoice_item.local_invoice') as total_item"))
        ->groupBy('supplier_invoice_item.local_invoice')
        ->where('supplier_invoice.supplier_id','=',$this->params->supplier_id)
        ->get();
        // $getInvDetails = DB::table('supplier_invoice')
        // ->join('supplier_invoice_item','supplier_invoice.local_invoice','=','supplier_invoice_item.local_invoice')
        // ->select('supplier_invoice.local_invoice','supplier_invoice_item.*')
        // ->where('supplier_invoice.supplier_id','=',$this->params->supplier_id)
        // ;

        //$a = DB::table($getInvDetails)->get();
        
        // $getInvDetails = DB::table(DB::raw('supplier_invoice','supplier_invoice_item')
        // ->select('supplier_invoice.local_invoice')
        // ->where('supplier_invoice.supplier_id','=',$this->params->supplier_id)
        // );


        $this->responseMessage = "Supplier Invoice Updated Successfully!";
        $this->outputData = $getInvDetails;
        $this->success = true;
    }

    /**Getting Item Details By ID */
    public function getItemDetailsByID(){
        $local_invoice = $this->invoice
        ->select('local_invoice')
        ->where(["id"=>$this->params->id])
        ->get();
       /**Getting Invoice number from supplier_invoice table */
        $localInvoice = $local_invoice->toArray();
        $localInvoice = $localInvoice[0]['local_invoice'];
       /**Getting Invoice number from supplier_invoice table */
        $invoiceItem = $this->invoiceItem
        ->select('*')
        ->where(["local_invoice"=>$localInvoice])
        ->get();

        $this->responseMessage = "Item Details Fetched Successfully!";
        // $this->outputData = $this->params;
        $this->outputData = $invoiceItem;
        $this->success = true;
    }

    /**Delete supplier */

    public function deleteInvoice(){

        //   $invoiceItem = $this->invoiceItem->where('');
        $local_invoice = $this->invoice
        ->select('local_invoice')
        ->where(["id"=>$this->params->id])
        ->get();
        // dd ($local_invoice->toArray());

        /**Getting Invoice number from supplier_invoice table */
        $localInvoice = $local_invoice->toArray();
        $localInvoice = $localInvoice[0]['local_invoice'];
        /**Getting Invoice number from supplier_invoice table */

        // $invoiceItem = $this->invoiceItem
        // ->select('*')
        // ->where(["local_invoice"=>$localInvoice])
        // ->get();

      $invoiceItem = $this->invoiceItem->where('local_invoice', $localInvoice)->delete();
      $invoice = $this->invoice->where('local_invoice', $localInvoice)->delete();

        // $department = $this->departments->where(["id"=>$this->params->department_id])->delete();
        // if(!$supplier){
        //     $this->success = false;
        //     $this->responseMessage = "Couldn't remove successfully, Please contact with Admin.";
        //     return;
        // }

        $this->responseMessage = "Supplier removed successfully!!";
        // $this->outputData["Inv: "] = $invoice;
        $this->outputData[] = array(
            "invoice_item"=>$invoiceItem,
            "invoice"=>$invoice
        );
        // $this->outputData['ID: '] = $this->params->supplier_id;
        //$this->outputData['creator'] = $department->creator;
        $this->success = true;
    }

    /**Updating Supplier -------------------> frontend: purchase/invoice/update/[id].tsx */
    public function updateInvoice(){
        $invoice = $this->invoice->where('status', 1)->find($this->params->supplier_invoice_id);

        if(!$invoice){
            $this->success = false;
            $this->responseMessage = "Item not found!";
            return;
        }
        
        // $this->validator->validate($request, [
        //     "date"=>v::notEmpty(),
        //  ]);
 
        // if ($this->validator->failed()) {
        //     $this->success = false;
        //     $this->responseMessage = $this->validator->errors;
        //     return;
        // }
        
        $invoice = $this->params->invoice;
        $deletedInvoice = $this->params->deletedInvoice;
        $newInvoice = $this->params->newInvoice;

        $now = Carbon::now();

        $count = count($invoice);
        $deletedCount = count($deletedInvoice);
        $newCount = count($newInvoice);
        
        if($count == 0){
            $this->success = false;
            $this->responseMessage = 'Add atleast one item';
            return; 
        }
        
        $supplierID = $this->params->supplierID;
        $supplier_invoice_id = $this->params->supplier_invoice_id;
        $status = $this->params->deletedInvoicestatus;

        $updatedlInvoice = $this->invoice
        ->where('id',$this->params->supplier_invoice_id)
        ->update([
            'total_amount' => $this->params->totalAmount,
            'remarks' => $this->params->totalRemarks,
            'local_invoice' => $this->params->localInvoice,
            'created_at' => $this->params->inv_date,
            'supplier_name' => $this->params->supplierName,
            'supplier_id' => $this -> params ->supplierID,
            'supplier_invoice' => $this -> params->inv_id, 
            ]);
        
        for($i =0; $i < $count; $i++){
        // foreach ($invoice as $value) {
        // dd($value);
        $editedInvoice = $this->invoiceItem
        ->where('id',$this->params->invoice[$i]['id'])
        ->update([
            // 'item_name' => $this->params->invoice[$i]['itemName'],
            'qty' => $this->params->invoice[$i]['itemCode'],
            'item_id' => $this->params->invoice[$i]['itemId'],
            'qty' => $this->params->invoice[$i]['item_qty'],
            'remarks' => $this->params->invoice[$i]['item_remarks'],
            'created_at' => $this->params->invoice[$i]['created_at'],
            'unit_price' => $this->params->invoice[$i]['unitPrice'],
            'status' => $this->params->invoice[$i]['status']
            ]);
        }

        $this->responseMessage = "Supplier Invoice Updated Successfully!";
        $this->outputData = $editedInvoice;
        $this->success = true;
        
        for ($l= 0; $l< $deletedCount; $l++){
            $editedDeletedInvoice = $this->invoiceItem
            ->where('id',$this->params->deletedInvoice[$l]['id'])
            ->update(['status' => 0]);
            }
                
        if($deletedCount){
            for($j=0; $j < $deletedCount; $j++){
                $deletedInvoiceItem = $this->invoiceItem->where('id', $deletedInvoice[$j]['id'])->delete();
                }
            }
            
        if($newInvoice){
            for($k =0; $k < $newCount; $k++){
            $newAddedInvoice = $this->invoiceItem
            ->insert([
                // 'item_name' => $this->params->newInvoice[$k]['itemName'],
                // 'supplier_name' => $this->params ->newInvoice[$k]['supplierName'],
                'local_invoice' => $this->params ->newInvoice[$k]['local_invoice'],
                'item_id' => $this->params ->newInvoice[$k]['itemId'],
                'supplier_invoice_id' => $this->params ->newInvoice[$k]['supplier_id'],
                'qty' => $this->params ->newInvoice[$k]['qty'],
                'unit_price' => $this->params ->newInvoice[$k]['unitPrice'],
                'created_at' => $this->params ->newInvoice[$k]['date'],
                'remarks' => $this->params ->newInvoice[$k]['item_remarks'],
                'status' => $this->params ->newInvoice[$k]['status'],
                ]);
            }
        }
    }

    /**Getting supplier by ID */

    public function getInvoiceByID() {
        $invoice = $this->invoice->select("*")
        ->where(["id"=>$this->params->id])->get();
        if(!$invoice){
            $this->success = false;
            $this->responseMessage = "Supplier Invoice not found!";
            return;
        }

        // $this->validator->validate($request, [
        //     "role_id"=>v::notEmpty(),
        // ]);

        $this->responseMessage = "Supplier Invoice Fetched Successfully!";
        $this->outputData = $invoice;
        $this->success = true;
    }

    /**Getting Supplier List */
    public function getAllSupplierInvoice(){
        //  $getAllInvoice = $this->invoice->all();

        /**Working section eloquent
          
         $getAllInvoice = $this->invoice
         ->join('supplier', 'supplier.id', '=', 'supplier_invoice.supplier_id')
         ->join('supplier_invoice_item','supplier_invoice_item.local_invoice','=','supplier_invoice.local_invoice')
         ->groupBy("supplier_invoice.local_invoice")
         ->select('supplier.*', 'supplier_invoice.*', DB::raw('count(supplier_invoice_item.item_id) as total_item'))
        // ->toSql();
        ->get();
          
         
         */
        $getAllInvoice = DB::select(DB::raw("SELECT si.id, 
		(select s.name from supplier s where si.supplier_id = s.id) as name,
        si.supplier_invoice,
        si.total_amount, 
        si.local_invoice, 
        si.created_by, 
        si.total_item_qty,
        (select COUNT(`sii`.item_id) from managebeds.supplier_invoice_item sii WHERE
        sii.supplier_invoice_id = si.id
        ) as total_item
        from supplier_invoice si"));
         /**
          * SELECT si.supplier_name, si.local_invoice, si.created_by, si.total_item_qty,
        (select COUNT(`sii`.local_invoice) from managebeds.supplier_invoice_item sii WHERE
        sii.local_invoice = si.local_invoice
        ) as total_item
        from supplier_invoice si
          */

        // $getAllItemsInvoice = $this->invoiceItem
        // ->join('supplier_invoice','supplier_invoice.local_invoice','=','supplier_invoice_item.local_invoice')
        // ->select(DB::raw('count(supplier_invoice_item.item_name) as total_item'),'supplier_invoice.local_invoice')
        // ->groupBy('supplier_invoice_item.local_invoice')
        // ->get();

        if(!$getAllInvoice){
            $getAllInvoice = DB::select(DB::raw("SELECT si.id, si.supplier_name,si.supplier_invoice,si.total_amount, si.local_invoice, si.created_by, si.total_item_qty
            from supplier_invoice si"));
            $this->success = true;
            $this->responseMessage = "Supplier Invoice Data not found!";
            return;
        }
        $this->responseMessage = "Supplier Invoice Data fetched Successfully!";
        // $this->outputData['getAllItemInvoice'] = $getAllItemsInvoice;
        $this->outputData = $getAllInvoice;
        $this->success = true;
    }

    /**Getting Invoice Details For --------------- purchase/invoice/update */
    public function getInvoiceDetails(){
        if(!isset($this->params->supplier_invoice_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $inv = $this->invoice->find($this->params->supplier_invoice_id);

        if($inv->status == 0){
            $this->success = false;
            $this->responseMessage = "Invoice missing!";
            return;
        }

        if(!$inv){
            $this->success = false;
            $this->responseMessage = "Invoice not found!";
            return;
        }

        $inv_list = DB::table('supplier_invoice')
        ->join('supplier_invoice_item','supplier_invoice.id','=','supplier_invoice_item.supplier_invoice_id')
        ->join('inventory_items','inventory_items.id','=','supplier_invoice_item.item_id')
        ->select('supplier_invoice.local_invoice','supplier_invoice.invoice_date','supplier_invoice_item.supplier_invoice_id','supplier_invoice_item.id','supplier_invoice.supplier_id','inventory_items.id as itemCode','inventory_items.code as itemCodeName',
        'inventory_items.id as itemId','inventory_items.name as itemName',
        'supplier_invoice_item.qty as item_qty', 'supplier_invoice.remarks as common_remarks',
        'supplier_invoice_item.remarks as item_remarks', 'supplier_invoice.created_at',
        'supplier_invoice_item.unit_price as unitPrice',
        'supplier_invoice.total_amount as totalAmount', 'supplier_invoice_item.status as status')
        ->where('supplier_invoice_item.status','=', 1)
        ->where('supplier_invoice.id','=', $this->params->supplier_invoice_id)
        ->get();

        $this->responseMessage = "Supplier Invoice Details fetched Successfully!";
        $this->outputData = $inv_list;
        $this->success = true;
    }
    /**Getting Invoice Details For --------------- Updpurchase/invoice/update */

    /**Create Supplier Invoice Item inv-item.tsx ---------- page frontend*/
    public function createSupplierInvoiceItem(){

            // $this->invoice = DB::table('supplier_inv_item')->insert([
            //     'inv_id' => $this->params->inv_id,
            //     'supplier_id' => $this->params->supplier_id,
            //     'supplier_name' => $this->params->supplier_name,
            //     'item_id' => $this->params->item_id,
            //     'qty' => $this->params->qty,
            //     'purchase_rate' => $this->params->purchase_rate,
            //     'previous_purchase_rate' => $this->previous_purchase_rate,
            //     'isReturned' => $this->params->isReturned,
            //     'returned_qty' => $this->params->returned_qty,
            //     'return_amount' => $this->params->return_amount,
            //     'status' => 1,
            // ]);

        /**supplier_inv_item table */
        $invoiceList = array();
        $supplierInv = array();
        $amount = 0;
        $qty = 0;
        //$supplierID = "";
        $lastSupplierInvoiceID = $this->helper->getLastID('supplier_invoice');
        $lastSupplierInvoiceID += 1;

        foreach ($this->params->invoice as $key => $value) {

             $supplierID = $this->invoice->where(["supplier_id"=>$value['supplierID']])->first();

            #==============> Test
             //$suplierID = $this->supplier->find($value['supplierID']);
            //$sID = $supplierID? "Found" : "Not Matched";
            //echo $sID."\n";
            //echo $value['supplierID'getInvoiceNumber]."\n";
            //continue;
            //die;
            //var_dump($value["supplierID"]);
            #<============== Test
        /**supplier_inv table ======> */


        #======================> Updating Inventory Table
            // $inventoryList = array();
            // $inventoryList[] = $this->inventory
            //     ->where(["id"=> $value["itemId"]])"invoice": [
            //     {
            //         "id": 1,
            //         "unitPrice": "400.00",
            //         "qty": "5",
            //         "total": 2000,
            //         "itemId": 4,
            //         "itemName": "Towel2",
            //         "remarks": "rtgffrg"
            //     }
            // ],
            // "status": true,
            // "supplierInv": "2",
            // "totalRemarks": "dfdf",
            // "inv_date": "2022-09-13",
            // "supplierID": 7,
            // "supplierName": "Kaycee Heathcote"
            //     ->update([
            //         'qty' => DB::raw ('qty +'.$value["qty"]),
            //     ]);
        #======================> Updating Inventory Table End

            #### Checking if exists supplier
            $now = Carbon::now();

            // if(!$supplierID){
                /** If not exist */
                // $supplierInv[] = array(
                //     'inv_number' => $this->faker->bankAccountNumber,
                //     'supplier_id' => $value["supplierID"],
                //     'inv_number' => $value["inv_id"],
                //     'supplier_inv_number' => $value["supplierInv"],
                //     'amount' => 0,
                //     'isReturned' => 0,"invoice": [
                //     {
                //         "id": 1,
                //         "unitPrice": "400.00",
                //         "qty": "5",
                //         "total": 2000,
                //         "itemId": 4,
                //         "itemName": "Towel2",
                //         "remarks": "rtgffrg"
                //     }
                // ],
                // "status": true,
                // "supplierInv": "2",
                // "totalRemarks": "dfdf",
                // "inv_date": "2022-09-13",
                // "supplierID": 7,
                // "supplierName": "Kaycee Heathcote"
                //     'total_item_qty' => 0,
                //     'return_type' => '',
                //     'return_amount' => 0,
                //     'status' => 0,
                // );
                // }
                // else{
                /**
                 * Product::where('product_id', $product->id)
                    ->update([
                    'count'=> DB::raw('count+1'),
                    'last_count_increased_at' => Carbon::now()
                    ]);
                    Product::where('id',$id)
                    ->increment('count', 1, ['increased_at' => Carbon::now()]);
                 */
                /** If exist supplier */
                //var_dump( $value["isReturn"]);
                //continue;


                //    Product::where('product_id', $product->id)
                //         ->update([
                //         'count'=> DB::raw('count+1'),
                //         'last_count_increased_at' => Carbon::now()
                //         ]);

                //         Product::where('id',$id)
                //         ->increment('count', 1, ['increased_at' => Carbon::now()]);



                /** If exist supplier*/
                // $supplier = $this->supplier
                // ->where(["id"=>$this->params->id])
                // ->update([
                //     'amount' => $this->params->amount,
                //     'total_item_qty'supplier_inv' => $this->params->total_item_qty,
                //     'isReturned' => $this->params->isReturned,
                //     'return_type' => $this->params->return_type,
                //     'return_amount' => $this->params->return_amount,
                //     'status' => $this->params->status,
                // ]);



                    // if(!$value["isReturn"]){

                    /** ==========> Operation old purchase cost /qty <=============== */
                    // dd($value["itemId"]);

                    $oldItem = $this->helper->getItem("qty,unit_cost","inventory_items", $value["itemId"]);

                    // dd($oldItem);
                    // dd($key);

                    $oldQty = $oldItem[0]->qty;
                    $oldPrice = $oldItem[0]->unit_cost;

                    $purchasedQty = $value["qty"];
                    $purchaseRate = $value["unitPrice"];

                    $newRate = ($oldPrice * $oldQty) + ($purchaseRate * $purchasedQty);
                    $newQty = $oldQty + $purchasedQty;

                    $newPrice = $newRate / $newQty;

                    // echo "Old qty: ".$oldQty. ", New Qty: ".$purchasedQty." Old Price: ".$oldPrice.", New Price:".$purchaseRate."\n";
                    // var_dump($newPrice );
                    /** ===========> Operation old purchase cost /qty <==============================*/
                    //$oldPrice = $this->helper->getItem("unit_cost,unit_cost","inventory_items",$value["itemId"]);
                    //echo $oldQty[0]->unit_cost;


                    $inventory = $this->inventory
                    ->where(["id"=>$value["itemId"]])
                    ->update([
                        // 'amount' => DB::raw('amount +'.$value["total"]),
                        'qty' => DB::raw ('qty +'.$value["qty"]),
                        'unit_cost' => $newPrice,
                        // 'isReturned' => $value["isReturned"],
                        // 'return_type' => $value["return_type"],
                        // 'return_amount' => $value["return_amount"],
                        // 'status' => $value["status"],
                    ]);


                // }
                //  var_dump($value["qty"]);
                  //var_dump($invoiceItem);
                // continue;
            // }

        /** <======= supplier_inv table */
        /**supplier_invoice table ========> */
        

        //<============ supplier_invoice_item table ================>
            $invoiceList[] = array(
                'status' => $this->params->status,
                // 'local_invoice' => $this->params->localInv,
                // 'supplier_id' => $this->params->supplierID,
                'created_at' => $this->params->inv_date,
                // 'supplier_name' => $this->params->supplierName,
                // 'item_id' => $value["id"],
                'unit_price' => $value["unitPrice"],
                'previous_purchase_rate' => $oldPrice,
                'qty' => $value["qty"],
                'previous_qty' => $oldQty,
                // '' => $value["total"],
                'item_id' => $value["itemId"],
                'supplier_invoice_id' => $lastSupplierInvoiceID,
                // 'item_name' => $value["itemName"],
                'remarks' => $value["remarks"],
                'created_by' => $this->user->id,
                // 'discount_rate' => $value["discountRate"],
                // 'discount_percent' => $value["discountPercent"],
            );
            $amount += $value["total"];
            $qty += $value["qty"];
            //$supplierID = $value["supplierID"];
            //echo $value["supplierName"];

        }   //End loop

        // var_dump($this->params->inv_id);


        //supplier_invoice'
        $supplierInv[] = array(
            //'inv_number' => $this->faker->bankAccountNumber,
            // 'supplier_name' => $this->params->supplierName,
            'supplier_id' => $this->params->supplierID,
            'local_invoice' => $this->params->localInv,
            'status' => $this->params->status,
            'created_at' => $this->params->inv_date,
            'supplier_invoice' => $this->params->inv_id,
            'total_amount' => $amount,
            'invoice_date' => $this->params->inv_date,
            // 'discount_rate' => $this->params->discountPrice,
            // 'discount_percent' => $this->params->discountPercent,
            // 'due' => $this->params->due,
            // 'paid' => $this->params->paid,
            // 'remarks' => $value["remarks"],
            'remarks' => $this->params->totalRemarks,
            // '' => $value["total"],
            // 'item_id' => $value["id"],
            'total_item_qty' => $qty,
            'created_by' => $this->user->id,
        );

        $val = $this->helper->getLastSupplierAccountBalance('account_supplier',$this->params->supplierID);

        $accountSupplier[] = array(
            'supplier_id'=>$this->params->supplierID,
            'invoice_id' => $this->params->inv_id,
            'inv_type' => "purchase",
            'debit' => 0.00,
            'credit' => $amount,
            'balance' => $val[0]->balance + $amount,
            'note' => "Due for purchase",
            'status' => 1,
            'created_by' => $this->user->id,

        );

        /**DB pushing */

        DB::table('supplier_invoice_item')->insert($invoiceList);

        DB::table('supplier_invoice')->insert($supplierInv);

        /**Account Supplier Insertion  */
        DB::table('account_supplier')->insert($accountSupplier);


        //var_dump($invoiceList);
        // $res = array_map(
        //     fn ($length) => $length * $length,
        //     $lengths
        // );

         /** <==== supplier_inv table */


        $this->responseMessage = "Supplier Invoice Item Created Successfully!";
        // $this->outputData =  $supplierInv;
        $this->outputData =  $invoiceList;
        $this->success = true;
    }


    /**Create Supplier Invoice Item inv-item.tsx ---------- page frontend*/

    /**Getting Invoice Number */
    public function getInvoiceNumber(){
        // $getLastId = $this->invoice->orderBy(DB::raw("CONVERT(inv_number, CHAR)"),'DESC')->get()->first();
        $getLastId = $this->invoice->orderBy('id','DESC')->get()->first();
        //var_dump($getLastId->id);

        if(!!!$getLastId->id){
            $getLastId = 1;
        }
        else{
            $getLastId = $getLastId->id;
        }

        $this->responseMessage = "Last Invoice ID Fetched Successfully!";
        $this->outputData = $getLastId;
        // $this->outputData = $getLastId->inv_number;
        $this->success = true;
    }

    /**Creating supplier no needed XXXXXXXXXXXXXXX ======================= */
    public function createSupplierInvoice(){

        // if(!isset($this->params)){
        //     $this->success = false;
        //     $this->responseMessage = "Parameter missing";
        //     return;
        // }

        # =====> Validation Start
        // $this->validator->validate($request, [
        //     "item_id"=>v::notEmpty(),
        //     "qty"=>v::notEmpty(),
        //     "status"=>v::notEmpty(),
        //  ]);
         //var_dump($this->validator);
        //  if ($this->validator->failed()) {
        //     $this->success = false;
        //     $this->responseMessage = $this->validator->errors;
        //     return;
        // }
        # =====> Validation End


        $this->invoice = DB::table('supplier_invoice')->insert([
            'invoice_number' => $this->params->invoice_number,
            'supplier_id' => $this->params->supplier_id,
            'supplier_invoice' => $this->params->invoice_ref,
            'remarks' => $this->params->invoice_remarks,
            'total_amount' => $this->params->amount,
            'total_item_qty' => $this->params->total_item_qty,
            'isReturned' => $this->params->isReturned,
            'return_type' => $this->params->return_type,
            'return_amount' => $this->params->return_amount,
            'status' => 1,
        ]);

        $this->responseMessage = "Supplier Invoice Created Successfully!";
        $this->outputData =  $invoice;
        // $this->outputData =  $this->invoice;
        $this->success = true;
    }
    /**Creating supplier no needed XXXXXXXXXXXXXXX ======================= */

    /**Faker Test */
    public function run (){



        // $this->invoice = DB::table('supplier_inv')->insert([
        //     'inv_number' => $this->faker->buildingNumber,
        //     'supplier_id' => $this->faker->buildingNumber,
        //     'supplier_inv_number' => $this->faker->numberBetween($min = 1, $max = 9),
        //     'remarks' => $this->faker->text,
        //     'amount' => $this->faker->text,
        //     'total_item_qty' => $this->faker->text,
        //     'isReturned' => $this->faker->text,
        //     'return_type' => $this->faker->text,
        //     'return_amount' => $this->faker->text,

        // $array = ["type1", "type2", "type3"];
        // $randomType = Arr::random($array);

        // $this->invoice = DB::table('supplier_inv')->insert([
        //     'inv_number' => $this->faker->randomNumber,
        //     'supplier_id' => $this->faker->randomDigit,
        //     'supplier_inv_number' => $this->faker->randomDigit,
        //     'remarks' => $this->faker->text,
        //     'amount' => $this->faker->randomDigit,
        //     'total_item_qty' => $this->faker->randomDigit,
        //     'isReturned' => $this->faker->boolean,
        //     'return_type' => $randomType,
        //     'return_amount' => $this->faker->randomDigit,

        //     'status' => 1,
        // ]);

        // $this->supplier = DB::table('supplier')->insert([
        //     'name' => Str::random(10),
        //     'email' => Str::random(10).'@gmail.com',
        //     'country_id' => rand(10,1000),
        //     'type' => $randomType,
        //     'bank_acc_number' => Str::random(5).rand(100,10000),
        //     'bank_name' => Str::random(8),
        //     'tax_id' => Str::random(3).rand(1000,100000),
        //     'address' => Str::random(3).rand(1000,100000),
        //     'contact_number' => rand(1000000,100000000),
        //     'status' => 1,
        // ]);


        // generate data by accessing properties
        //$res = $this->faker->name;
        // 'Lucy Cechtelar';
        //echo $this->faker->address;
        // "426 Jordy Lodge
        // Cartwrightshire, SC 88120-6700"
        //echo $this->faker->text;
        //var_dump($this->faker);
        //die();


        // $this->responseMessage = "Ok";
        // $this->outputData =  $this->helper->additionNumber(1,2);
        // $this->success = true;

        // $invoiceList = array();
        // $inventoryList = array();

        // foreach ($this->params->invoice as $key => $value) {

        //     $inventoryList[] = $this->inventory
        //     ->where(["id"=> $value["itemId"]])
        //     ->update([
        //         'qty' => DB::raw ('qty +'.$value["qty"]),
        //     ]);

        //      //var_dump($inventoryList);

        // }

        //$getAllInventory = $this->inventory->all();
        // $oldQty = $this->helper->getItem("qty,unit_cost","inventory_items","3");

        // echo $oldQty[0]->qty;
        // dd ($oldQty);
        // echo $oldQty[0]->unit_cost;
        
        $this->responseMessage = "Ok";
        // $this->outputData =  $this->helper->additionNumber(1,2);
        // $val = $this->helper->getLastSupplierAccountBalance('account_supplier','23');
        // $this->outputData =  $val[0]->balance;
        $this->outputData =  $this->params;
        $this->success = true;
    }
}
