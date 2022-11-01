<?php

namespace  App\Controllers\Inventory;

use App\Auth\Auth;
use App\Models\Inventory\ConsumptionVoucher;
use App\Models\Inventory\InventoryItem;
use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;
use Carbon\Carbon;
use App\Validation\Validator;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class VoucherController
{

    protected $customResponse;

    protected $validator;

    protected $params;
    protected $responseMessage;
    protected $outputData;
    protected $success;
    protected $user;

    public function __construct()
    {
        $this->customResponse = new CustomResponse();
        $this->consumptionVouchers = new ConsumptionVoucher();
        $this->items = new InventoryItem();
        $this->validator = new Validator();

        $this->responseMessage = "";
        $this->outputData = [];
        $this->success = false;
    }

    public function go(Request $request, Response $response)
    {
        $this->params = CustomRequestHandler::getAllParams($request);
        $action = isset($this->params->action) ? $this->params->action : "";

        $this->user = Auth::user($request);

        switch ($action) {
            case 'createVoucher':
                $this->createVoucher($request, $response);
                break;
            case 'getAllVouchers':
                $this->getAllVouchers($request, $response);
                break;
            case 'getVoucherInfo':
                $this->getVoucherInfo($request, $response);
                break;
            case 'updateVoucher':
                $this->updateVoucher($request, $response);
                break;
            case 'deleteVoucher':
                $this->deleteVoucher($request, $response);
                break;
            case 'getItemByCode':
                $this->getItemByCode($request, $response);
                break;
            case 'getCodeByItem':
                $this->getCodeByItem($request, $response);
                break;
            case 'getItemByCategory':
                $this->getItemByCategory($request, $response);
                break;
            default:
                $this->responseMessage = "Invalid request!";
                return $this->customResponse->is400Response($response, $this->responseMessage);
                break;
        }

        if (!$this->success) {
            return $this->customResponse->is400Response($response, $this->responseMessage, $this->outputData);
        }

        return $this->customResponse->is200Response($response, $this->responseMessage, $this->outputData);
    }


    public function createVoucher(Request $request, Response $response)
    {
        $this->validator->validate($request, [
            "date"=>v::notEmpty(),
         ]);
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }

        $vouchers = $this->params->vouchers;
        $now = Carbon::now();

        $count = count($vouchers);

        if($count == 0){
            $this->success = false;
            $this->responseMessage = 'Add atleast one item';
            return; 
        }

        $qty = 0;
        for($i =0; $i < $count; $i++){
            $qty = $qty+$vouchers[$i]['item_qty'];

            $item = $this->items->where('status',1)->find($vouchers[$i]['itemId']);
            $old_qty = $item->qty;
            $new_qty = $vouchers[$i]['item_qty'];
            if($new_qty > $old_qty){
                $this->success = false;
                $this->responseMessage = sprintf("You can not consume '%s' more than %d",$item->name,$old_qty);
                return; 
            }

        }

        $date = $now->format('ym');
        $last_voucher = $this->consumptionVouchers->select('id')->orderBy('id', 'DESC')->first();
        $voucher_id = $last_voucher->id + 1;
        if($voucher_id == null){
            $voucher_id = 1;
        }
        $voucher_number = sprintf("ICV-%s000%d",$date,$voucher_id);
        
        $voucher = $this->consumptionVouchers->create([
           "voucher_number" => $voucher_number,
           "remarks" => $this->params->totalRemarks,
           "voucher_date" => $this->params->date,
           "total_item" => $count,
           "total_item_qty" => $qty,
           "edit_attempt"=> 0,
           "created_by" => $this->user->id,
           "status" => 1,
        ]);

        $voucherList = array();
        $itemHistory = array();

        for($j =0; $j < $count; $j++){

            $item = $this->items->where('status',1)->find($vouchers[$j]['itemId']);
            $old_qty = $item->qty;
            $new_qty = $vouchers[$j]['item_qty'];
            $final_qty = $old_qty - $new_qty;

            $editedItem = $item->update([
                "qty" => $final_qty,
             ]);
           
           $voucherList[] = array(
            'consumption_voucher_id' => $voucher->id,
            'inventory_item_id' => $vouchers[$j]['itemId'],
            'qty' => $vouchers[$j]['item_qty'],
            'remarks' => $vouchers[$j]['remarks'],
            'created_by' => $this->user->id,
            'status' => 1
           );

           $itemHistory[] = array(
            'inventory_item_id' => $vouchers[$j]['itemId'],
            'edit_attempt' => 0,
            'note' => 'Item Consumed',
            'reference' => $voucher->id,
            'ref_type' => 'consumption_voucher',
            'action_by' => $this->user->id,
            'old_qty' => $old_qty,
            'affected_qty' => $new_qty,
            'new_qty' =>  $final_qty,
            'status' => 1
           );

        }

        DB::table('consumption_voucher_items')->insert($voucherList);
        DB::table('inventory_item_history')->insert($itemHistory);

        $this->responseMessage = "New Category created successfully";
        //$this->outputData = $voucher_id->id;
        $this->success = true;
    }

    public function getAllVouchers(Request $request, Response $response)
    {
        $vouchers = $this->consumptionVouchers->with(['creator','updator'])->where('status',1)->orderBy('id','desc')->get();

        $this->responseMessage = "Voucher list fetched successfully";
        $this->outputData = $vouchers;
        $this->success = true;
    }

    public function getVoucherInfo(Request $request, Response $response)
    {
        if(!isset($this->params->voucher_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $voucher = $this->consumptionVouchers->find($this->params->voucher_id);

        if($voucher->status == 0){
            $this->success = false;
            $this->responseMessage = "Voucher missing!";
            return;
        }

        if(!$voucher){
            $this->success = false;
            $this->responseMessage = "Voucher not found!";
            return;
        }

        $voucher_list = DB::table('consumption_vouchers')
        ->join('consumption_voucher_items','consumption_vouchers.id','=','consumption_voucher_items.consumption_voucher_id')
        ->join('inventory_items','inventory_items.id','=','consumption_voucher_items.inventory_item_id')
        ->select('consumption_voucher_items.id','inventory_items.id as itemCode','inventory_items.code as itemCodeName',
                 'inventory_items.id as itemId','inventory_items.name as itemName',
                 'consumption_voucher_items.remarks',
                 'consumption_voucher_items.qty as item_qty',
                 )
        ->where('consumption_voucher_items.status','=',1)
        ->where('consumption_vouchers.id','=',$this->params->voucher_id)
        ->get();

        $this->responseMessage = "Voucher info fetched successfully";
        $this->outputData = $voucher;
        $this->outputData['voucher_list'] = $voucher_list;
        $this->success = true;
    }

    public function updateVoucher(Request $request, Response $response)
    {
        $voucher = $this->consumptionVouchers->where('status',1)->find($this->params->voucher_id);

        if(!$voucher){
            $this->success = false;
            $this->responseMessage = "Item not found!";
            return;
        }

        $this->validator->validate($request, [
            "date"=>v::notEmpty(),
         ]);
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }

        $vouchers = $this->params->vouchers;
        $deletedVouchers = $this->params->deletedVouchers;

        $now = Carbon::now();

        $count = count($vouchers);
        $deletedCount = count($deletedVouchers);

        if($count == 0){
            $this->success = false;
            $this->responseMessage = 'Add atleast one item';
            return; 
        }

        $qty = 0;
        for($i =0; $i < $count; $i++){
            $qty = $qty+$vouchers[$i]['item_qty'];

            $item = $this->items->where('status',1)->find($vouchers[$i]['itemId']);
            $old_qty = $item->qty;
            $new_qty = $vouchers[$i]['item_qty'];
            if($new_qty > $old_qty){
                $this->success = false;
                $this->responseMessage = sprintf("You can not consume '%s' more than %d",$item->name,$old_qty);
                return; 
            }
            if($new_qty == 0){
                $this->success = false;
                $this->responseMessage = sprintf("You can not consume '%s' 0 quantity",$item->name);
                return;
            }
        }

        $editedVoucher = $voucher->update([
           "remarks" => $this->params->totalRemarks,
           "voucher_date" => $this->params->date,
           "total_item" => $count,
           "total_item_qty" => $qty,
           "updated_by" => $this->user->id,
        ]);

        $old_voucher_item = DB::table('consumption_voucher_items')->where('consumption_voucher_id', $this->params->voucher_id)->where('status',1)->get();

        $old_item_history = DB::table('inventory_item_history')->where('reference', $this->params->voucher_id)->where('status',1)->get();

        $old_voucher_count = count($old_voucher_item);

        $insertedVoucher = array();
        $insertItemHistory = array();

        // for($k = 0; $k < $old_voucher_count ; $k++){
            for($l = 0; $l < $count; $l++){

                $item = $this->items->where('status',1)->find($vouchers[$l]['itemId']);
                $old_qty = $item->qty;
                $old_edited_qty = $old_voucher_item[$l]->qty;
                $new_qty = $vouchers[$l]['item_qty'];

                $diff =  $old_edited_qty - $new_qty; 

                $final_qty = $old_qty + ($diff);
                
                if($old_edited_qty != $new_qty){
                    $editedItem = $item->update([
                        "qty" => $final_qty,
                    ]);
                }

                if($old_voucher_item[$l]->inventory_item_id == $vouchers[$l]['itemId']){
                   
                    $voucherUpdated = DB::table('consumption_voucher_items')
                                ->where('id', $old_voucher_item[$l]->id)
                                ->update(['consumption_voucher_id' => $this->params->voucher_id,
                                          'inventory_item_id' => $vouchers[$l]['itemId'],
                                          'qty' => $vouchers[$l]['item_qty'],
                                          'remarks' => $vouchers[$l]['remarks'],
                                          'updated_by' => $this->user->id
                                        ]);

                    if($old_edited_qty != $new_qty){
                    // $historyUpdated = DB::table('inventory_item_history')
                    //             ->where('id', $old_item_history[$l]->id)
                    //             ->update([
                    //                         'inventory_item_id' => $vouchers[$l]['itemId'],
                    //                         'old_qty' => $old_qty,
                    //                         'affected_qty' => $new_qty,
                    //                         'new_qty' =>  $final_qty,
                    //                         'updated_by' => $this->user->id,
                    //                         'updated_at' => $now->format('Y-m-d H:i:s'),
                    //                     ]);

                    $insertItemHistory[] = array(
                        'inventory_item_id' => $vouchers[$l]['itemId'],
                        'type' => 'consumption_voucher',
                        'reference' => $this->params->voucher_id,
                        'ref_type' => null,
                        'action_by' => $this->user->id,
                        'old_qty' => $old_qty,
                        'affected_qty' => $diff,
                        'new_qty' =>  $final_qty,
                        'status' => 1
                       );

                    }
                }
                else{
                    $insertedVoucher[] = array(
                        'consumption_voucher_id' => $this->params->voucher_id,
                        'inventory_item_id' => $vouchers[$l]['itemId'],
                        'qty' => $vouchers[$l]['item_qty'],
                        'remarks' => $vouchers[$l]['remarks'],
                        'created_by' => $this->user->id,
                        'updated_by' => $this->user->id,
                        'updated_at' => $now->format('Y-m-d H:i:s'),
                        'status' => 1
                    );

                    $insertItemHistory[] = array(
                    'inventory_item_id' => $vouchers[$l]['itemId'],
                    'type' => 'consumption_voucher',
                    'reference' => $this->params->voucher_id,
                    'ref_type' => null,
                    'action_by' => $this->user->id,
                    'old_qty' => $old_qty,
                    'affected_qty' => $new_qty,
                    'new_qty' =>  $final_qty,
                    'status' => 1
                   );
                }

            }
        //}
        DB::table('consumption_voucher_items')->insert($insertedVoucher);
        DB::table('inventory_item_history')->insert($insertItemHistory);

        $updateItemHistory = array();

        if($deletedCount > 0){

            for($m = 0; $m < $deletedCount; $m++){

                $item = $this->items->where('status',1)->find($deletedVouchers[$m]['itemId']);
                $old_voucher = DB::table('consumption_voucher_items')->where('inventory_item_id', $deletedVouchers[$m]['itemId'])->where('status',1)->get();

                $old_qty = $item->qty;
                $new_qty = $old_voucher[$m]->qty;

                $final_qty = $old_qty + $new_qty;
                
                $editedItem = $item->update([
                    "qty" => $final_qty,
                ]);
                
                $voucherDeleted = DB::table('consumption_voucher_items')
                                    ->where('inventory_item_id', $deletedVouchers[$m]['itemId'])
                                    ->update(['status' => 0,
                                              'updated_by' => $this->user->id
                                            ]);

                // $historyUpdated = DB::table('inventory_item_history')
                //                     ->where('inventory_item_id', $deletedVouchers[$m]['itemId'])
                //                     ->update(['status' => 0,
                //                                 'updated_by' => $this->user->id
                //                             ]);

                $updateItemHistory[] = array(
                    'inventory_item_id' => $deletedVouchers[$m]['itemId'],
                    'type' => 'consumption_voucher',
                    'reference' => $this->params->voucher_id,
                    'ref_type' => null,
                    'action_by' => $this->user->id,
                    'old_qty' => $old_qty,
                    'affected_qty' => $new_qty,
                    'new_qty' =>  $final_qty,
                    'status' => 1
                   );
                
            }
        }

        DB::table('inventory_item_history')->insert($updateItemHistory);

        $this->responseMessage = "New Voucher Updated successfully";
        $this->outputData = $deletedVouchers;
        $this->success = true;
    }

    public function deleteVoucher()
    {
        if(!isset($this->params->voucher_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $voucher = $this->consumptionVouchers->find($this->params->voucher_id);

        if(!$voucher){
            $this->success = false;
            $this->responseMessage = "Voucher not found!";
            return;
        }

        $voucher_items = DB::table('consumption_voucher_items')->where('consumption_voucher_id', $this->params->voucher_id)->where('status',1)->get();
        $deletedItemHistory = array();

        for($i = 0; $i < $voucher->total_item; $i++){
            $item = $this->items->where('status',1)->find($voucher_items[$i]->inventory_item_id);
            $old_qty = $item->qty;
            $new_qty = $voucher_items[$i]->qty;
            $final_qty = $old_qty + $new_qty;
                
            $editedItem = $item->update([
                "qty" => $final_qty,
            ]);

            $deletedItemHistory[] = array(
                'inventory_item_id' => $voucher_items[$i]->inventory_item_id,
                'type' => 'consumption_voucher',
                'reference' => $this->params->voucher_id,
                'ref_type' => null,
                'action_by' => $this->user->id,
                'old_qty' => $old_qty,
                'affected_qty' => $new_qty,
                'new_qty' =>  $final_qty,
                'status' => 1
               );
        }
        DB::table('inventory_item_history')->insert($deletedItemHistory);
        
        $deletedVoucher = $voucher->update([
            "remarks" => 'Canceled',
            "status" => 0,
         ]);
 
         $this->responseMessage = "Voucher Deleted successfully";
         $this->outputData = $deletedVoucher;
         $this->success = true;
    } 

    public function getItemByCode(Request $request, Response $response)
    {
        $items = $this->items->where('status',1)->find($this->params->id);

        $this->responseMessage = "Item list fetched successfully";
        $this->outputData = $items;
        $this->success = true;
    }

    public function getCodeByItem(Request $request, Response $response)
    {
        $items = $this->items->find($this->params->id);

        $this->responseMessage = "Item list fetched successfully";
        $this->outputData = $items;
        $this->success = true;
    }

    public function getItemByCategory()
    {
        $items = $this->items->where('inventory_category_id',$this->params->id)->where('status',1)->get();

        $this->responseMessage = "Item list fetched successfully";
        $this->outputData = $items;
        $this->success = true;
    }
    
}
