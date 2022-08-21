<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;

class StatusController extends Controller
{
    public function add(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'board_id' => 'required|exists:boards,id',
            ]);
            if ($validate->fails()) {
                return $this->sendError("Bad request", $validate->messages()->toArray());
            }

            $status = new Status();
            $status->name = $request->get("name");
            $status->board_id = $request->get("board_id");

            $status->save();

            return $this->sendResponse($status->toArray(), Response::HTTP_CREATED);
        } catch (Exception $exception) {
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getAll(Request $request)
    {
        try {
            $statuses = Status::query();

            $results = [
                'data' => $statuses->items()
            ];

            return $this->sendResponse($results);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function get($id)
    {
        try {
            $status = Status::find($id);

            if (!$status) {
                return $this->sendError('status not found!', [], Response::HTTP_NOT_FOUND);
            }

            return $this->sendResponse($status->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $status = Status::find($id);

            if (!$status) {
                return $this->sendError('status not found!', [], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'board_id' => 'nullable|exists:boards,id'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');
            $board_id = $request->get('board_id');


            $status->name = $name;
            $status->board_id = $$board_id;
            $status->save();

            return $this->sendResponse($status->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}