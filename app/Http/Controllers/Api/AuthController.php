<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Models\LogAuth;
use App\Models\ActionLog;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.action')->only(['register', 'updateUser', 'destroyUser']);
    }
    public function getLogAuth()
    {
        $logs = LogAuth::all();
       
        if(count($logs) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'The log auth data retrieved successfully',
                'data' => $logs
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'Log Auth is Empty',
            'data' => null
        ], 404);
    }

    public function getLast5LogAuth()
    {
        $logs = LogAuth::orderBy('created_at', 'desc')->take(5)->get();
        if(count($logs) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'The log auth data retrieved successfully',
                'data' => $logs
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'Log Auth is Empty',
            'data' => null
        ], 404);
    }
    
    public function getLogAction()
    {
        $logs = ActionLog::all();
        
        if(count($logs) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'The action log data retrieved successfully',
                'data' => $logs
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'User data is Empty',
            'data' => null
        ], 404);
    }
    public function getLogActionByAuthUser()
    {
        $authUserId = Auth::id();
        $logs = ActionLog::where('id_user', $authUserId)->get();
        if(count($logs) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'The action log of authenticated user retrieved successfully',
                'data' => $logs
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'User data is Empty',
            'data' => null
        ], 404);
    }

    public function getAllUser()
    {
        $users = User::all();
        if(count($users) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'The users data successfully show',
                'data' => $users
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'User data is Empty',
            'data' => null
        ], 404);
    }
    public function getAllUserExceptSelf()
    {
        $authUserId = Auth::id();

        //Retrieve all users except the authenticated user
        $users = User::where('id', '!=', $authUserId)->get();
        if(count($users) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'The users data successfully show',
                'data' => $users
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'User data is Empty',
            'data' => null
        ], 404);
    }

    public function authCheck() {
        if (Auth::check()) {
            // User is logged in, retrieve the authenticated user
            $user = Auth::user();
            return response()->json([
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => $user
            ]);
        } else {
            // User is not logged in
            return response()->json([
                'success' => false,
                'message' => 'User is not logged in',
                'data' => null
            ], 401); // Unauthorized status code
        }
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

        if(is_null($user)){
            return response()->json([
                'status' => 'failed',
                'message' => "Item with ID $id not found",
                'data' => null
            ], 404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'name' => 'required|max:60',
            'email' => [
                'required',
                'email:rfc,dns',
                Rule::unique('users')->ignore($id),
            ],
            'role' => 'required',
            // 'password' => 'required',
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }

        $user->name = $updateData['name'];
        $user->email = $updateData['email'];
        $user->role = $updateData['role'];
        // $user->password = Hash::make($updateData['password']);

        if($user->save()){
            return response()->json([
                'status' => 'success',
                'message' => "Item with ID $id updated successfully",
                'data' => $user
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Failed to update item with ID $id",
                'data' => null
            ], 500);
        }
    }

    public function changeIsActive(Request $request, $id)
    {
        $user = User::find($id);
        if(is_null($user)){
            return response()->json([
                'status' => 'failed',
                'message' => "Item with ID $id not found",
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        $updateData['is_active'] = filter_var($updateData['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $validate = Validator::make($updateData, [
            'is_active' => 'required|boolean',
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }

        $user->is_active = $updateData['is_active'];

        if($user->save()){
            return response()->json([
                'status' => 'success', 
                'message' => 'User status updated successfully',
                'is_active' => $user->is_active
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Failed to update item with ID $id",
            ], 500);
        }
    }

    public function destroyUser($id)
    {
        $user = User::find($id);

        if(is_null($user)){
            return response([
                'message' => "Item data with ID $id Not Found",
                'data' => null
            ], 404);
        }

        if($user->delete()){
            return response([
                'message' => "Delete Item data with ID $id Success",
                'data' => $user
            ], 200);
        }

        return response([
            'message' => "Delete Item data with ID $id Failed",
            'data' => $user
        ], 400);
    }

    public function register(Request $request)
    {
        $registrationData = $request->all(); //mengambil seluruh data input dan menyimpan dalam variabel  registrationData
        $registrationData['is_active'] = true;
        $validate = Validator::make($registrationData, [
            'name' => 'required|max:60',
            'email' => 'required|email:rfc,dns|unique:users',
            'role' => 'required',
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            return response([
                'success' => false,
                'message' => $validate->errors()
            ], 400);
        }


        $registrationData['password'] = bcrypt($request->password); //untuk meng-enkripsi password

        $user = User::create($registrationData);

        return response([
            'success' => true,
            'message' => 'Register Success',
            'user' => $user
        ], 200);
    }
    
    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        $credentials = $request->only('email', 'password');
        
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }
        $user = Auth::user();
        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is inactive'
            ], 403); // 403 Forbidden status code
        }
    
        
        $token = $user->createToken('authToken')->accessToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Authenticated',
            'data' => [
                'user' => $user,
                'token_type' => 'Bearer',
                'access_token' => $token,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        if (Auth::user()) {
            $user = Auth::user()->token();
            $user->revoke();
            return response([
                'message' => 'Logout success',
            ], 200);
        } else {
            return response([
                'message' => 'Unable to Logout',
            ]);
        }
    }
}
