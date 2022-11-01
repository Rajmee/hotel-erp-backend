<?php

namespace  App\Controllers\Purchase;

use App\Auth\Auth;
use App\Models\Purchase\Supplier;
use App\Models\Purchase\AccountSupplier;
use App\Models\Users\ClientUsers;
use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

/**Seeding tester */
use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;


//Helper Function 
use App\Helpers\Helper;
//use Fzaninotto\Faker\Src\Faker\Factory;
//use Fzaninotto\Src\Faker;
use Faker\Factory;
use Faker;

/**Seeding tester */
class SupplierController
{

    protected $customResponse;

    protected $validator;

    protected $params;
    protected $responseMessage;
    protected $outputData;
    protected $success;
    protected $user;

    /** Supplier ini */
    public $supplier;
    public $accountSupplier;
    private $faker;

    //Helper
    private $helper;


    public function __construct()
    {
        $this->customResponse = new CustomResponse();
        //Model Instance
        $this->supplier = new Supplier();
        $this->accountSupplier = new AccountSupplier();
        $this->user = new ClientUsers();
        /*Model Instance END */
        $this->validator = new Validator();
        //Helper
         $this->helper = new Helper;
        //Helper
        $this->responseMessage = "";
        $this->outputData = [];
        $this->success = false;
        $this->faker = Factory::create();
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
            case 'createSupplier':
                $this->createSupplier($request);
                break;
            case 'getAllSupplier':
                $this->getAllSupplier();
                break;
            case 'getSupplierByID':
                $this->getSupplierByID();
                break;
            case 'updateSupplier':
                $this->updateSupplier();
                break;
            case 'delete':
                $this->deleteSupplier();
                break;
            default:
                $this->responseMessage = "Invalid request!";
                return $this->customResponse->is400Response($response, $this->responseMessage);
        }

        if (!$this->success) {
            return $this->customResponse->is400Response($response, $this->responseMessage, $this->outputData);
        }

        return $this->customResponse->is200Response($response, $this->responseMessage, $this->outputData);
    }

    /**Delete supplier */
    public function deleteSupplier(){
        // $supplier = $this->supplier->where('id', $this->params->id)->delete();   //working remove
        // $supplier = $this->supplier->where(["id"=>$this->params->department_id])->delete();
        $supplier = $this->supplier
        ->where(["id"=>$this->params->id])
        ->update([
            'status'=> 0
        ]);

        if(!$supplier){
            $this->success = false;
            $this->responseMessage = "Couldn't remove successfully, Please contact with Admin.";
            return;
        }

        $this->responseMessage = "Supplier removed successfully!!";
        //$this->outputData = $this->params->department;
        //$this->outputData['creator'] = $department->creator;
        $this->success = true;
    }

    /**Updating Supplier */
    public function updateSupplier(){
        $supplier = $this->supplier
        ->where(["id"=>$this->params->id])
        ->update([
            'name'=> $this->params->name,
            'country_name'=> $this->params->country_name,
            'type'=> $this->params->type,
            'bank_acc_number'=> $this->params->default_bank_account,
            'bank_name' =>  $this->params->bank_name,
            // 'tax_id'=> $this->params->tax_id,
            'address'=> $this->params->address,
            'opening_balance'=> $this->params->opening_balance,
            'contact_number'=> $this->params->contact_number,
            'description'=> $this->params->description,
            'status'=> $this->params->status
        ]);
       

        $this->responseMessage = "Supplier Fetched Successfully!";
        $this->outputData = $supplier;
        $this->success = true;
    }

    /**Getting supplier by ID */

    public function getSupplierByID() {
        $supplier = $this->supplier
        // ->sum('supplier_invoice.total_amount as amount')
        // ->select("supplier.*","sum(supplier_invoice.total_amount)")
        ->select("supplier.*", DB::raw('SUM(supplier_invoice.total_amount) as amount'))
        // ->select("supplier.*")
        // ->select("supplier.*","supplier_invoice.total_amount as amount")
        ->join('supplier_invoice', 'supplier_invoice.supplier_id','=','supplier.id')
        ->where(["supplier.id"=>$this->params->id])
        ->get();
        // ->groupBy('supplier_invoice.supplier_id');
        // $supplier = DB::table('supplier_invoice')
        //             ->join('suplier','suplier.id','=','supplier_invoice.supplier_id')
        //             ->select('supplier_id as suplierId',DB::raw('SUM(total_amount) as amount'))
        //             ->get()
        //             ->groupBy('supplier_id');
        // dd($supplier['id']);



        // echo is_null($supplier[0]->id);
        // echo($supplier);
        // echo(is_null($supplier['id']));
        // die;
        if(is_null($supplier[0]->id)){
            $supplier = $this->supplier
            ->select('*')
            ->where(["supplier.id"=>$this->params->id])
            ->get();
            $supplier[0]["amount"] = 0;
        }

        if(!$supplier){
            $supplier =$this->supplier
            ->select('*')
            ->where(["supplier.id"=>$this->params->id])->get();

            if(!$supplier){
                $this->success = true;
                $this->responseMessage = "Supplier not found!";
                return;
            }
            $this->success = true;
            $this->responseMessage = "Supplier Data fetched!";
        }

        // $this->validator->validate($request, [
        //     "role_id"=>v::notEmpty(),
        // ]);

        $this->responseMessage = "Supplier Fetched Successfully!";
        $this->outputData = $supplier;
        $this->success = true;
    }

    /**Getting Supplier List */

    public function getAllSupplier(){
        // $getAllSupplier = $this->supplier->all();

        /**
         $getAllSupplier = $this->supplier
        ->join('org_users','org_users.id','=','supplier.created_by')
        ->join('supplier_invoice','supplier_invoice.supplier_id','=','supplier.id')
        // ->join('supplier_invoice','supplier_invoice.supplier_id','=','supplier.id','full outer')
        // ->crossjoin('supplier_invoice','supplier_invoice.supplier_id','=','supplier.id')
        ->select('org_users.name as username', 'supplier.*',DB::raw('count(supplier_invoice.local_invoice) as total_invoice'))
        ->groupBy('supplier_invoice.supplier_id')
        ->get();
         */

        // $c = DB::select(DB::raw("SELECT * FROM supplier"));

        // $a = $this->supplier
        // ->rightjoin('org_users','org_users.id','=','supplier.created_by')
        // ->select('org_users.name as username');
        // // join('supplier_invoice', 'supplier_invoice.supplier_id','=','supplier.id');
        // $b = $this->supplier
        // ->leftjoin('supplier_invoice','supplier_invoice.supplier_id','=','supplier.id')
        // ->select('supplier.*', DB::raw('count(supplier_invoice.local_invoice) as total_invoice'))
        // ->groupBy('supplier_invoice.supplier_id');


        // $getAllSupplier = $c->union($b);

        $getAllSupplier = DB::select(DB::raw("SELECT supplier.id, supplier.name, supplier.balance, supplier.status,
        (SELECT COUNT(supplier_invoice.id) FROM supplier_invoice 
        WHERE supplier_invoice.supplier_id=supplier.id) AS total_invoice,  
        (SELECT org_users.name FROM org_users WHERE org_users.id=supplier.created_by) 
        AS createdBy FROM supplier")); //Full outer join Query is ok but not suitable for mysql
        // $getAllSupplier = DB::select(DB::raw("SELECT org_users.name as username, supplier.*, count(supplier_invoice.local_invoice) as total_invoice  FROM `supplier`, `supplier_invoice`, `org_users` FULL OUTER JOIN `supplier_invoice` ON `supplier_invoice.supplier_id` = `supplier.id` FULL OUTER JOIN `org_users` ON `org_users.id` = `supplier.created_by` GROUP BY `supplier_invoice.supplier_id`")); //Full outer join Query is ok but not suitable for mysql
        
        // $getAllSupplier = DB::select(DB::raw("SELECT org_users.name as username, supplier.*, 
        // count(supplier_invoice.local_invoice) as total_invoice
        // FROM `supplier`, `supplier_invoice`, `org_users`
        // LEFT OUTER JOIN `supplier_invoice` ON `supplier_invoice.supplier_id` = `supplier.id` 
        // UNION ALL
        // RIGHT OUTER JOIN `supplier_invoice` ON `supplier_invoice.supplier_id` = `supplier.id`

        // LEFT OUTER JOIN `org_users` ON `org_users.id` = `supplier.created_by`
        // UNION ALL
        // RIGHT OUTER JOIN `supplier_invoice` ON `supplier_invoice.supplier_id` = `supplier.id`
        // GROUP BY `supplier_invoice.supplier_id`"));

        // DB::query()
        // ->fromSub(
        // DB::table('supplier')
        //     ->select([
        //         '*'
        //     ])
        //     ->union(
        //         DB::table('supplier_invoice')
        //             ->select([
        //                 '*'
        //             ])
        //         ),
        //     'supplier_list'
        // )
        // ->join('org_users','org_users.id','=','supplier_list.created_by')
        // ->join('supplier_invoice','supplier_invoice.supplier_id','=','supplier_list.id')
        // ->select(['org_users.name as username', 'supplier_list.*',DB::raw('count(supplier_invoice.local_invoice) as total_invoice')])
        // ->select(['supplier_list.*'])
        // ->get();


        // $getAllSupplier = $getAllSupplier->union('supplier');
        if(!$getAllSupplier){
            $this->success = false;
            $this->responseMessage = "Supplier Data not found!";
            return;
        }
        $this->responseMessage = "Supplier Data fetched Successfully!";
        // $this->outputData = $this->params;
        $this->outputData = $getAllSupplier;
        $this->success = true;
    }

    /**Creating supplier */
    public function createSupplier(Request $request){
        if(!isset($this->params)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        # =====> Validation Start
        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "status"=>v::notEmpty(),
         ]);
         //var_dump($this->validator);
         if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }
        # =====> Validation End


         $this->supplier = $this->supplier->insert([
            "name" => $this->params->name,
            "country_name" => $this->params->country_name,
            "type" => $this->params->type,
            "bank_acc_number" => $this->params->default_bank_account,
            "bank_name" => $this->params->bank_name,
            "tax_id" => $this->params->tax_id,
            "address" => $this->params->address,
            "contact_number" => $this->params->contact_number,
            "status" => $this->params->status,
            "opening_balance" => $this->params->opening_balance,
            "balance" => $this->params->opening_balance,
         ]);

         /**Getting Supplier ID supplier table */
            // $localInvoice = $local_invoice->toArray();
            // $localInvoice = $localInvoice[0]['local_invoice'];
            $getLastID = $this->helper->getLastID('supplier');
            // dd($idr);
            // var_dump($idr);
            // echo($idr);
            // $this->responseMessage = "Supplier Created Successfully!";
            // $this->outputData = $idr;
            // $this->success = true;
            // die();
        /**Getting Supplier ID supplier table */
        // echo $idr;
        // die();


         $this->accountSupplier = $this->accountSupplier
         ->insert([
            "supplier_id" => $getLastID,
            "invoice_id" => $this->params->invoice_id,
            "inv_type" => "opening_balance",
            "note" => "Supplier created with opening balance",
            "debit" => $this->params->opening_balance,
            "credit" => 0.00,
            "balance" => $this->params->opening_balance,
            "created_by" => $this->user->id,
            "status" => $this->params->status,
         ]);

        $this->responseMessage = "Supplier Created Successfully!";
        $this->outputData =  $this->params;
        // $this->outputData =  $idr;
        $this->success = true;
    }


    /**Faker Test */

    public function run (){
        $array = ["Regular", "Temporary", "Company"];
        $randomType = Arr::random($array);

        $this->supplier = DB::table('supplier')->insert([
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'country_name' => $this->faker->country,
            'type' => $randomType,
            'bank_acc_number' => $this->faker->bankAccountNumber,
            'bank_name' => Str::random(8),
            'tax_id' => $this->faker->numberBetween($min = 100000, $max = 900000),
            'address' => $this->faker->address,
            'contact_number' => $this->faker->phoneNumber,
            'status' => 1,
        ]);


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

        $this->responseMessage = "Ok";
        $this->outputData =  $this->supplier;
        $this->success = true;
    }
}