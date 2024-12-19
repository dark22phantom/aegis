<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

use App\Models\User;
use App\Mail\UserEmail;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'created_at' => now()
        ]);

        // send email to queue
        Mail::to($user->email)->cc(env('MAIL_USERNAME'))->queue(new UserEmail($user));

        // return response
        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at,
        ], 201);
    }

    public function list(Request $request)
    {
        $perPage = 10;
        $page = $request->input('page', 1);
        $searchFilter = $request->input('search');

        // get sortBy
        $sortBy = $request->input('sortBy', 'created_at');
        $availableSortBy = ['created_at', 'email', 'name'];
        if ($sortBy && !in_array($sortBy, $availableSortBy)) {
            $sortBy = 'created_at';
        }

        // get sortOrder
        $sortOrder = $request->input('sortOrder', 'desc');
        $availableSortOrder = ['asc', 'desc'];
        if ($sortOrder && !in_array($sortOrder, $availableSortOrder)) {
            $sortOrder = 'desc';
        }

        // set query
        $query = User::select('id', 'email', 'name', 'created_at')
            ->withCount('orders');
        if ($searchFilter) {
            $query->where(function ($query) use ($searchFilter) {
                $query->where('name', 'like', '%' . $searchFilter . '%')
                    ->orWhere('email', 'like', '%' . $searchFilter . '%');
            });
        }
        $query->orderBy($sortBy, $sortOrder);
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'page' => $users->currentPage(),
            'users' => $users->items(),
        ], 200);
    }
}
