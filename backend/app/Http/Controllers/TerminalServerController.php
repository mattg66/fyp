<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Node;
use App\Models\Rack;
use App\Models\TerminalServer;
use App\Models\ToR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerminalServerController extends Controller
{
    public function create(Request $request)
    {
        $this->validate($request, [
            'label' => 'required|max:50',
            'model' => 'required|max:50',
            'username' => 'required|max:50',
            'password' => 'required|max:50',
            'ip' => 'required|ip',
            'rack_id' => 'max:50',
        ]);

        if (TerminalServer::where('ip', $request->ip)->first() != null) {
            return response()->json([
                'message' => 'Terminal Server with this IP already exists',
            ], 400);
        }

        $rack = null;
        if ($request->has('rack_id') && $request->rack_id != null) {
            $rack = Rack::find(intval($request->rack_id));
            if ($rack == null) {
                return response()->json([
                    'message' => 'Rack not found',
                ], 404);
            } else if ($rack->terminalServer() != null) {
                return response()->json([
                    'message' => 'Rack already has a Terminal Server',
                ], 400);
            }
        }
        $ts = new TerminalServer();
        $ts->label = $request->label;
        $ts->model = $request->model;
        $ts->username = $request->username;
        $ts->password = $request->password;
        $ts->ip = $request->ip;
        $ts->save();

        if ($request->has('rack_id') && $request->rack_id != null) {
            $rack->terminalServer()->save($ts);
        }
        $ts = TerminalServer::with('rack')->find($ts->id);
        return response()->json([
            $ts,
        ], 201);
    }
    public function getAll(Request $request)
    {
        if ($request->has('withoutRack')) {
            if ($request->has('rackId')) {
                $ts = TerminalServer::where('rack_id', null)->orWhere('rack_id', '=', $request->rackId)->get();
                return response()->json($ts, 200);
            } else {
                $ts = TerminalServer::where('rack_id', null)->get();
                return response()->json($ts, 200);
            }
        } else {
            $ts = TerminalServer::with('rack')->get();
            return response()->json($ts, 200);
        }
    }
    public function getById($id)
    {
        $ts = TerminalServer::with('rack')->find($id);
        if ($ts == null) {
            return response()->json([
                'message' => 'Terminal Server not found',
            ], 404);
        }
        return response()->json($ts, 200);
    }
    public function updateById(Request $request, $id)
    {
        $this->validate($request, [
            'label' => 'max:50',
            'model' => 'max:50',
            'username' => 'max:50',
            'password' => 'max:50',
            'ip' => 'ip',
            'rack_id' => 'numeric',
        ]);
        DB::beginTransaction();
        $ts = TerminalServer::find($id);
        if ($ts == null) {
            return response()->json([
                'message' => 'Terminal Server not found',
            ], 404);
        }
        if ($request->has('rack_id')) {
            $rack = Rack::find($request->rack_id);
            if ($rack == null) {
                return response()->json([
                    'message' => 'Rack not found',
                ], 404);
            } else if ($rack->terminalServer() !== null) {
                return response()->json([
                    'message' => 'Rack already has a Terminal Server',
                ], 400);
            } 
            else {
                $rack->terminalServer()->save($ts);
            }
        }
        if ($request->has('label')) {
            $ts->label = $request->label;
        }
        if ($request->has('model')) {
            $ts->model = $request->model;
        }
        if ($request->has('username')) {
            $ts->username = $request->username;
        }
        if ($request->has('password')) {
            $ts->password = $request->password;
        }
        if ($request->has('ip')) {
            if ($ts->ip !== $request->ip && TerminalServer::where('ip', $request->ip)->first() != null) {
                return response()->json([
                    'message' => 'Terminal Server with this IP already exists',
                ], 400);
            }
            $ts->ip = $request->ip;
        }
        $ts->save();
        DB::commit();
        return response()->json($ts, 200);
    }
    public function deleteById($id)
    {
        $ts = TerminalServer::find($id);
        if ($ts == null) {
            return response()->json([
                'message' => 'Terminal Server not found',
            ], 404);
        }
        $ts->delete();
        return response()->json([
            'message' => 'Terminal Server deleted',
        ], 200);
    }
}
